<?php

namespace App\Sports\Support;

use App\Models\ClubScopedModel;

/**
 * Generic base dla per-team agregatow meczowych (sport_<key>_match_stats).
 *
 * Konwencja:
 *   - $table   = 'sport_<key>_match_stats'
 *   - $matchTable = '<key>_matches' (FK do tej tabeli)
 *   - $statsColumns = pola numeryczne ktore sport zlicza (np. goals, fouls)
 *
 * Zapis: upsert per (match_id, team_side='home'|'away').
 * Wszystkie zapytania scoped po club_id (multi-tenant).
 */
abstract class TeamMatchStatsModel extends ClubScopedModel
{
    /** Tabela meczow ktorej dotyczy ten model (np. 'futsal_matches'). */
    protected string $matchTable = '';

    /** Lista kolumn statystycznych (int) zarzadzanych przez formularz. */
    protected array $statsColumns = [];

    /**
     * Sanitizuje array $_POST do wartosci INT >= 0 dla znanych kolumn.
     * Nieznane kolumny ignorowane (whitelist).
     */
    public function sanitize(array $input): array
    {
        $out = [];
        foreach ($this->statsColumns as $c) {
            $v = isset($input[$c]) ? (int)$input[$c] : 0;
            $out[$c] = max(0, $v);
        }
        return $out;
    }

    /** Upsert per (match_id, team_side). team_side ∈ {home, away}. */
    public function upsert(int $matchId, string $teamSide, array $stats): void
    {
        $teamSide = $teamSide === 'away' ? 'away' : 'home';
        $clubId   = $this->clubId();
        $stats    = $this->sanitize($stats);

        // Sprawdz czy istnieje wpis
        $stmt = $this->db->prepare(
            "SELECT id FROM `{$this->table}` WHERE match_id=? AND team_side=? AND club_id <=> ?"
        );
        $stmt->execute([$matchId, $teamSide, $clubId]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            $set    = implode(' = ?, ', array_map(fn($c) => "`{$c}`", array_keys($stats))) . ' = ?';
            $params = array_values($stats);
            $params[] = (int)$existing;
            if ($clubId !== null) {
                $params[] = $clubId;
                $this->db->prepare("UPDATE `{$this->table}` SET {$set} WHERE id=? AND club_id=?")
                         ->execute($params);
            } else {
                $this->db->prepare("UPDATE `{$this->table}` SET {$set} WHERE id=?")
                         ->execute($params);
            }
            return;
        }

        $cols  = ['match_id', 'club_id', 'team_side', ...array_keys($stats)];
        $vals  = [$matchId, $clubId, $teamSide, ...array_values($stats)];
        $holds = implode(', ', array_fill(0, count($cols), '?'));
        $sql   = "INSERT INTO `{$this->table}` (`" . implode('`, `', $cols) . "`) VALUES ({$holds})";
        $this->db->prepare($sql)->execute($vals);
    }

    /** Zwroc obie strony statystyk dla meczu (home + away). */
    public function forMatch(int $matchId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE match_id=? AND club_id <=> ? ORDER BY team_side"
        );
        $stmt->execute([$matchId, $this->clubId()]);
        $rows = $stmt->fetchAll();
        $out  = ['home' => null, 'away' => null];
        foreach ($rows as $r) {
            $out[$r['team_side']] = $r;
        }
        return $out;
    }

    /** Zwroc match z atomowa weryfikacja club_id. */
    public function findMatch(int $matchId): ?array
    {
        if (!preg_match('/^[a-z0-9_]+$/', $this->matchTable)) return null;
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->matchTable}` WHERE id=? AND club_id=?"
        );
        $stmt->execute([$matchId, $this->clubId()]);
        return $stmt->fetch() ?: null;
    }

    /** Lista kolumn (do widoku formularza). */
    public function statsColumns(): array
    {
        return $this->statsColumns;
    }
}
