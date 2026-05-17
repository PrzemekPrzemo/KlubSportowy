<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;
use PDO;

/**
 * Model wersjonowanych protokolow turniejowych (PDF + share link).
 *
 * Zwiazana migracja: database/migrations/093_tournament_protocols.sql
 *
 * Uwaga: NIE dziedziczymy z ClubScopedModel — protokol jest dostepny:
 *   1. cross-tenant przez public_share_slug (kontroler /protocols/{slug})
 *   2. cross-tenant przez CLI (republish_finished_tournaments)
 * Multi-tenant izolacja jest egzekwowana na poziomie kontrolera (club_id
 * sprawdzany przed publikacja / regeneracja) lub naturalnie przez FK do
 * tournaments (kaskada delete).
 */
class TournamentProtocolModel
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::pdo();
    }

    /**
     * Najnowsza wersja protokolu dla turnieju (lub null gdy brak).
     */
    public function latestForTournament(int $tournamentId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tournament_protocols
              WHERE tournament_id = ?
              ORDER BY version DESC
              LIMIT 1"
        );
        $stmt->execute([$tournamentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Lookup po publicznym slug. Zwraca null gdy slug nie istnieje
     * lub gdy `public_share_enabled = 0` (defense in depth — zawsze
     * filtr w SQL, niezalezne od kontrolera).
     */
    public function findByPublicSlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM tournament_protocols
              WHERE public_share_slug = ? AND public_share_enabled = 1
              ORDER BY version DESC
              LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Sprawdz czy slug jest globalnie unikalny (cross-tenant).
     */
    public function slugExists(string $slug): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM tournament_protocols WHERE public_share_slug = ? LIMIT 1"
        );
        $stmt->execute([$slug]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Zarejestruj nowa wersje protokolu (insert).
     *
     * @param array{
     *   tournament_id:int, club_id:int, version:int, pdf_path:string,
     *   pdf_size_bytes?:int, pdf_hash?:string, public_share_slug?:?string,
     *   public_share_enabled?:int, auto_generated?:int
     * } $data
     */
    public function insertVersion(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tournament_protocols
                (tournament_id, club_id, version, pdf_path, pdf_size_bytes,
                 pdf_hash, public_share_slug, public_share_enabled, auto_generated)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            (int)$data['tournament_id'],
            (int)$data['club_id'],
            (int)$data['version'],
            (string)$data['pdf_path'],
            isset($data['pdf_size_bytes']) ? (int)$data['pdf_size_bytes'] : null,
            $data['pdf_hash'] ?? null,
            $data['public_share_slug'] ?? null,
            (int)($data['public_share_enabled'] ?? 0),
            (int)($data['auto_generated'] ?? 1),
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Update share flagi (toggle) na NAJNOWSZEJ wersji protokolu.
     * Zwraca true gdy doszlo do zmiany.
     */
    public function setShareEnabled(int $tournamentId, bool $enabled, ?string $slug = null): bool
    {
        $latest = $this->latestForTournament($tournamentId);
        if ($latest === null) return false;

        $newSlug = $slug ?? $latest['public_share_slug'];

        $stmt = $this->db->prepare(
            "UPDATE tournament_protocols
                SET public_share_enabled = ?, public_share_slug = ?
              WHERE id = ?"
        );
        $stmt->execute([
            $enabled ? 1 : 0,
            $newSlug,
            (int)$latest['id'],
        ]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Wszystkie wersje (audit trail) dla turnieju — porzadek malejacy.
     */
    public function versionsForTournament(int $tournamentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, version, generated_at, pdf_size_bytes, auto_generated
               FROM tournament_protocols
              WHERE tournament_id = ?
              ORDER BY version DESC"
        );
        $stmt->execute([$tournamentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ile turniejow `status='finished'` BEZ protokolu (do CLI fallback).
     * Optional --since filter.
     */
    public function finishedTournamentsWithoutProtocol(?string $sinceDate = null): array
    {
        $sql = "SELECT t.id, t.club_id, t.name, t.date_start
                  FROM tournaments t
             LEFT JOIN tournament_protocols tp ON tp.tournament_id = t.id
                 WHERE t.status = 'finished'
                   AND tp.id IS NULL";
        $params = [];
        if ($sinceDate !== null) {
            $sql .= " AND t.date_start >= ?";
            $params[] = $sinceDate;
        }
        $sql .= " ORDER BY t.date_start DESC, t.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
