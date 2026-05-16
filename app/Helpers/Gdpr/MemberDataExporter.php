<?php

namespace App\Helpers\Gdpr;

use App\Helpers\Database;
use App\Helpers\Encryption;
use App\Models\TenantAccessLogModel;
use PDO;
use RuntimeException;
use ZipArchive;

/**
 * GDPR art. 20 RODO — kompletny ZIP eksport danych czlonka.
 *
 * Generuje strukture:
 *
 *   data/
 *     profile.json
 *     payments.json
 *     trainings.json
 *     tournaments.json
 *     events.json
 *     medical.json
 *     consents.json
 *     communications.json
 *     achievements.json
 *     rankings.json
 *     licenses.json
 *     body_metrics.json
 *     notification_prefs.json
 *     gdpr_requests.json
 *   documents/         (PDF z member_documents, zaswiadczenia, umowy)
 *   photos/            (zdjecie profilowe + photo_path)
 *   manifest.json      (lista plikow + SHA-256 + metadane)
 *   README.txt         (human-readable po PL)
 *
 * Bezpieczenstwo:
 *   - KAZDY SELECT scoped per club_id (defense-in-depth multi-tenant).
 *   - Encrypted fields (pesel, email, phone) odszyfrowane przed dump.
 *   - Plik zapisany w storage/gdpr_exports/{club_id}/{request_id}.zip
 *     (poza /public/ — file serving przez controller).
 *   - chmod 0600 na pliku.
 *
 * Wyjatki: rzuca RuntimeException przy bledach FS / DB.
 */
class MemberDataExporter
{
    /** Format ISO 8601 dla wszystkich datetime w eksportach. */
    private const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /** JSON flags: pretty + UTF-8 + slash unescaped. */
    private const JSON_FLAGS = JSON_PRETTY_PRINT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES;

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    /**
     * Glowna metoda: generuje kompletny ZIP.
     *
     * @param int $memberId  id czlonka
     * @param int $requestId id wpisu w gdpr_requests
     * @param int $clubId    id klubu (cross-tenant guard)
     * @return string Absolutna sciezka do utworzonego ZIP.
     * @throws RuntimeException
     */
    public function export(int $memberId, int $requestId, int $clubId): string
    {
        // 1. Cross-tenant guard — member.club_id MUSI sie zgadzac.
        $member = $this->fetchMember($memberId, $clubId);
        if ($member === null) {
            throw new RuntimeException(sprintf(
                'Cross-tenant guard: member_id=%d nie nalezy do club_id=%d.',
                $memberId,
                $clubId
            ));
        }

        // 2. Prepare output dir per-club (poza webrootem).
        $dir = $this->ensureExportDir($clubId);
        $zipPath = sprintf('%s/%d.zip', $dir, $requestId);

        // Jesli stary plik istnieje (regenerate) — usun.
        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        // 3. Build ZIP.
        $zip = new ZipArchive();
        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            throw new RuntimeException('Nie udalo sie utworzyc pliku ZIP: ' . $zipPath . ' (code=' . $opened . ')');
        }

        $manifestFiles = [];
        $generatedAt = date(self::DATETIME_FORMAT);
        $clubName = $this->fetchClubName($clubId);

        // --- data/*.json ---
        $manifestFiles[] = $this->addJson($zip, 'data/profile.json',         $this->decryptMember($member, $clubId));
        $manifestFiles[] = $this->addJson($zip, 'data/payments.json',        $this->fetchPayments($memberId, $clubId));
        $manifestFiles[] = $this->addJson($zip, 'data/trainings.json',       $this->fetchTrainings($memberId, $clubId));
        $manifestFiles[] = $this->addJson($zip, 'data/tournaments.json',     $this->fetchTournaments($memberId, $clubId));
        $manifestFiles[] = $this->addJson($zip, 'data/events.json',          $this->fetchEvents($memberId, $clubId));
        $manifestFiles[] = $this->addJson($zip, 'data/medical.json',         $this->fetchMedical($memberId, $clubId));
        $manifestFiles[] = $this->addJson($zip, 'data/consents.json',        $this->fetchConsents($memberId, $clubId));
        $manifestFiles[] = $this->addJson($zip, 'data/communications.json',  $this->fetchCommunications($memberId, $clubId));
        $manifestFiles[] = $this->addJson($zip, 'data/achievements.json',    $this->fetchAchievements($memberId, $clubId));
        $manifestFiles[] = $this->addJson($zip, 'data/rankings.json',        $this->fetchRankings($memberId, $clubId));
        $manifestFiles[] = $this->addJson($zip, 'data/licenses.json',        $this->fetchLicenses($memberId, $clubId));
        $manifestFiles[] = $this->addJson($zip, 'data/body_metrics.json',    $this->fetchBodyMetrics($memberId, $clubId));
        $manifestFiles[] = $this->addJson($zip, 'data/notification_prefs.json', $this->fetchNotificationPrefs($memberId, $clubId));
        $manifestFiles[] = $this->addJson($zip, 'data/gdpr_requests.json',   $this->fetchGdprRequests($memberId, $clubId));

        // --- documents/*.pdf ---
        foreach ($this->fetchDocumentPaths($memberId, $clubId) as $docPath) {
            $entry = $this->addBinaryFile($zip, $docPath, 'documents/');
            if ($entry !== null) {
                $manifestFiles[] = $entry;
            }
        }

        // --- photos/*.jpg ---
        if (!empty($member['photo_path'])) {
            $entry = $this->addBinaryFile($zip, $member['photo_path'], 'photos/');
            if ($entry !== null) {
                $manifestFiles[] = $entry;
            }
        }

        // --- README.txt ---
        $readme = $this->buildReadme($memberId, $clubId, $clubName, $generatedAt);
        $manifestFiles[] = $this->addRaw($zip, 'README.txt', $readme);

        // --- manifest.json (na koniec — ma checksumy wszystkich poprzednich) ---
        $manifest = [
            'gdpr_export'    => [
                'club_id'      => $clubId,
                'club_name'    => $clubName,
                'member_id'    => $memberId,
                'request_id'   => $requestId,
                'generated_at' => $generatedAt,
                'format'       => 'ClubDesk GDPR ZIP v1',
                'rodo_article' => 'art. 20 RODO (prawo do przenoszenia danych)',
                'disclaimer'   => 'Plik zawiera dane osobowe wrazliwe (pesel, email, adres). '
                                . 'Przechowuj go bezpiecznie i nie udostepniaj osobom trzecim.',
                'expires_at_note' => 'Plik pozostaje dostepny w portalu przez 7 dni od wygenerowania.',
            ],
            'files' => $manifestFiles,
        ];
        $this->addRaw($zip, 'manifest.json', $this->jsonPretty($manifest));

        $zip->close();

        if (!is_file($zipPath)) {
            throw new RuntimeException('ZIP nie zostal zapisany na dysk: ' . $zipPath);
        }

        // Chmod 0600 (rw owner only).
        @chmod($zipPath, 0600);

        // Audit log (best-effort, nigdy nie crashuje).
        try {
            (new TenantAccessLogModel())->logBypass(
                'members',
                'read',
                __FILE__,
                __LINE__,
                self::class,
                'info',
                sprintf('GDPR export ZIP wygenerowany request=%d member=%d club=%d size=%d',
                    $requestId, $memberId, $clubId, filesize($zipPath) ?: 0)
            );
        } catch (\Throwable) {
            // pass
        }

        return $zipPath;
    }

    // ============================================================
    // Helpers — fetch (kazda metoda ma WHERE club_id = ?)
    // ============================================================

    private function fetchMember(int $memberId, int $clubId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM members WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$memberId, $clubId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function fetchClubName(int $clubId): string
    {
        try {
            $stmt = $this->pdo->prepare("SELECT name FROM clubs WHERE id = ? LIMIT 1");
            $stmt->execute([$clubId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (string)($row['name'] ?? 'KlubSportowy');
        } catch (\Throwable) {
            return 'KlubSportowy';
        }
    }

    /**
     * Odszyfruj pola wrazliwe przed dump do JSON.
     * Po odszyfrowaniu UWALNIAMY plaintext z pamieci (nigdy nie cachujemy).
     */
    private function decryptMember(array $member, int $clubId): array
    {
        $encryptedFields = ['pesel', 'email', 'phone'];
        if (Encryption::isConfigured()) {
            foreach ($encryptedFields as $field) {
                if (!empty($member[$field])) {
                    $plain = Encryption::decrypt((string)$member[$field]);
                    if ($plain !== null) {
                        $member[$field] = $plain;
                    }
                }
            }
        }

        // Usun pola, ktorych NIE chcemy w eksporcie (haszowane dublety, hasla, tokeny TOTP).
        $stripFields = [
            'pesel_hash', 'email_hash', 'phone_hash',
            'portal_password', 'totp_secret', 'totp_confirmed_at',
        ];
        foreach ($stripFields as $f) {
            unset($member[$f]);
        }

        return $member;
    }

    private function fetchPayments(int $memberId, int $clubId): array
    {
        return $this->safeFetchAll(
            "SELECT * FROM payments WHERE member_id = ? AND club_id = ? ORDER BY payment_date DESC",
            [$memberId, $clubId]
        );
    }

    private function fetchTrainings(int $memberId, int $clubId): array
    {
        // Frekwencja na treningach (training_attendees + trainings join scope club_id).
        return $this->safeFetchAll(
            "SELECT ta.id, ta.training_id, ta.member_id, ta.status, ta.note, ta.created_at,
                    t.name AS training_name, t.start_time, t.end_time, t.location
             FROM training_attendees ta
             JOIN trainings t ON t.id = ta.training_id
             WHERE ta.member_id = ? AND t.club_id = ?
             ORDER BY t.start_time DESC",
            [$memberId, $clubId]
        );
    }

    private function fetchTournaments(int $memberId, int $clubId): array
    {
        // event_entries to udzialy w wydarzeniach typu tournament + wyniki.
        return $this->safeFetchAll(
            "SELECT ee.*, e.name AS event_name, e.start_date, e.event_type
             FROM event_entries ee
             JOIN events e ON e.id = ee.event_id
             WHERE ee.member_id = ? AND e.club_id = ? AND e.event_type = 'tournament'
             ORDER BY e.start_date DESC",
            [$memberId, $clubId]
        );
    }

    private function fetchEvents(int $memberId, int $clubId): array
    {
        return $this->safeFetchAll(
            "SELECT ee.*, e.name AS event_name, e.start_date, e.event_type
             FROM event_entries ee
             JOIN events e ON e.id = ee.event_id
             WHERE ee.member_id = ? AND e.club_id = ?
             ORDER BY e.start_date DESC",
            [$memberId, $clubId]
        );
    }

    private function fetchMedical(int $memberId, int $clubId): array
    {
        // member_medical_exams — zaznaczamy w README ze to dane wrazliwe.
        return $this->safeFetchAll(
            "SELECT mme.* FROM member_medical_exams mme
             JOIN members m ON m.id = mme.member_id
             WHERE mme.member_id = ? AND m.club_id = ?
             ORDER BY mme.exam_date DESC",
            [$memberId, $clubId]
        );
    }

    private function fetchConsents(int $memberId, int $clubId): array
    {
        return $this->safeFetchAll(
            "SELECT * FROM member_consents WHERE member_id = ? AND club_id = ?
             ORDER BY granted_at DESC",
            [$memberId, $clubId]
        );
    }

    private function fetchCommunications(int $memberId, int $clubId): array
    {
        // Otrzymane emaile z kolejki (jesli `to_email` zgadza sie z czlonkiem) + messages.
        $out = [
            'emails' => [],
            'messages' => [],
        ];

        // Emaile: scope per club_id.
        $out['emails'] = $this->safeFetchAll(
            "SELECT id, subject, to_email, to_name, status, template_type, created_at, sent_at
             FROM email_queue
             WHERE club_id = ?
               AND to_email IN (
                   SELECT m.email FROM members m WHERE m.id = ? AND m.club_id = ? AND m.email IS NOT NULL
               )
             ORDER BY created_at DESC
             LIMIT 1000",
            [$clubId, $memberId, $clubId]
        );

        // Messages — najpierw scope po polu klubowym jesli istnieje.
        $out['messages'] = $this->safeFetchAll(
            "SELECT * FROM messages WHERE recipient_member_id = ? AND club_id = ?
             ORDER BY created_at DESC LIMIT 1000",
            [$memberId, $clubId]
        );

        return $out;
    }

    private function fetchAchievements(int $memberId, int $clubId): array
    {
        return $this->safeFetchAll(
            "SELECT ma.*, a.name AS achievement_name, a.description AS achievement_description
             FROM member_achievements ma
             LEFT JOIN achievements a ON a.id = ma.achievement_id
             WHERE ma.member_id = ? AND ma.club_id = ?
             ORDER BY ma.awarded_at DESC",
            [$memberId, $clubId]
        );
    }

    private function fetchRankings(int $memberId, int $clubId): array
    {
        return $this->safeFetchAll(
            "SELECT sr.* FROM sport_rankings sr
             JOIN members m ON m.id = sr.member_id
             WHERE sr.member_id = ? AND m.club_id = ?",
            [$memberId, $clubId]
        );
    }

    private function fetchLicenses(int $memberId, int $clubId): array
    {
        return $this->safeFetchAll(
            "SELECT ml.* FROM member_licenses ml
             JOIN members m ON m.id = ml.member_id
             WHERE ml.member_id = ? AND m.club_id = ?",
            [$memberId, $clubId]
        );
    }

    private function fetchBodyMetrics(int $memberId, int $clubId): array
    {
        return $this->safeFetchAll(
            "SELECT bm.* FROM body_metrics bm
             JOIN members m ON m.id = bm.member_id
             WHERE bm.member_id = ? AND m.club_id = ?
             ORDER BY bm.measured_at DESC",
            [$memberId, $clubId]
        );
    }

    private function fetchNotificationPrefs(int $memberId, int $clubId): array
    {
        return $this->safeFetchAll(
            "SELECT mnp.* FROM member_notification_prefs mnp
             JOIN members m ON m.id = mnp.member_id
             WHERE mnp.member_id = ? AND m.club_id = ?",
            [$memberId, $clubId]
        );
    }

    private function fetchGdprRequests(int $memberId, int $clubId): array
    {
        return $this->safeFetchAll(
            "SELECT id, request_type, status, requested_at, confirmed_at,
                    processed_at, reason, notes
             FROM gdpr_requests
             WHERE member_id = ? AND club_id = ?
             ORDER BY requested_at DESC",
            [$memberId, $clubId]
        );
    }

    /**
     * Lista absolutnych sciezek do dokumentow czlonka (PDF, zaswiadczenia).
     * Defensive: tabela moze nie istniec — zwroc [].
     */
    private function fetchDocumentPaths(int $memberId, int $clubId): array
    {
        $rows = $this->safeFetchAll(
            "SELECT file_path FROM member_documents
             WHERE member_id = ? AND club_id = ?",
            [$memberId, $clubId]
        );

        $paths = [];
        foreach ($rows as $row) {
            $path = (string)($row['file_path'] ?? '');
            if ($path === '') continue;
            // Jesli sciezka relatywna — prepend ROOT_PATH.
            if ($path[0] !== '/') {
                $path = ROOT_PATH . '/' . ltrim($path, '/');
            }
            $paths[] = $path;
        }
        return $paths;
    }

    // ============================================================
    // Helpers — ZIP write
    // ============================================================

    /**
     * Dodaj plik JSON do ZIP, zwroc manifest entry.
     *
     * @return array{name:string, size:int, sha256:string}
     */
    private function addJson(ZipArchive $zip, string $entryName, mixed $data): array
    {
        $content = $this->jsonPretty($data);
        $zip->addFromString($entryName, $content);
        return [
            'name'   => $entryName,
            'size'   => strlen($content),
            'sha256' => hash('sha256', $content),
        ];
    }

    /**
     * Dodaj raw string (np. README.txt, manifest.json).
     *
     * @return array{name:string, size:int, sha256:string}
     */
    private function addRaw(ZipArchive $zip, string $entryName, string $content): array
    {
        $zip->addFromString($entryName, $content);
        return [
            'name'   => $entryName,
            'size'   => strlen($content),
            'sha256' => hash('sha256', $content),
        ];
    }

    /**
     * Dodaj plik binarny (PDF / JPG) z dysku do ZIP.
     *
     * @return array{name:string, size:int, sha256:string}|null null jesli plik nie istnieje
     */
    private function addBinaryFile(ZipArchive $zip, string $absolutePath, string $entryPrefix): ?array
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        // Validate path is within ROOT_PATH (path traversal guard).
        $real = realpath($absolutePath);
        $rootReal = realpath(ROOT_PATH);
        if ($real === false || $rootReal === false || !str_starts_with($real, $rootReal)) {
            return null;
        }

        $entryName = $entryPrefix . basename($absolutePath);
        $zip->addFile($absolutePath, $entryName);
        $size = @filesize($absolutePath);
        $sha  = @hash_file('sha256', $absolutePath);
        return [
            'name'   => $entryName,
            'size'   => $size !== false ? $size : 0,
            'sha256' => $sha !== false ? $sha : '',
        ];
    }

    // ============================================================
    // Helpers — misc
    // ============================================================

    private function jsonPretty(mixed $data): string
    {
        $json = json_encode($data, self::JSON_FLAGS);
        if ($json === false) {
            throw new RuntimeException('json_encode failed: ' . json_last_error_msg());
        }
        return $json;
    }

    private function ensureExportDir(int $clubId): string
    {
        $base = ROOT_PATH . '/storage/gdpr_exports';
        $dir  = $base . '/' . $clubId;
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0750, true) && !is_dir($dir)) {
                throw new RuntimeException('Nie udalo sie utworzyc katalogu: ' . $dir);
            }
        }
        return $dir;
    }

    /**
     * SELECT z catchem PDOException (jesli tabela nie istnieje w danej instalacji).
     */
    private function safeFetchAll(string $sql, array $params): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException) {
            return [];
        }
    }

    private function buildReadme(int $memberId, int $clubId, string $clubName, string $generatedAt): string
    {
        $r  = "Eksport danych RODO (art. 20 — prawo do przenoszenia danych)\n";
        $r .= str_repeat('=', 64) . "\n\n";
        $r .= "Wygenerowano:   {$generatedAt}\n";
        $r .= "Klub:           {$clubName} (ID {$clubId})\n";
        $r .= "Czlonek ID:     {$memberId}\n";
        $r .= "Wygasa:         za 7 dni od daty wygenerowania (link w portalu)\n\n";

        $r .= "ZAWARTOSC ARCHIWUM\n";
        $r .= str_repeat('-', 64) . "\n";
        $r .= "data/profile.json            - Twoje dane osobowe (imie, nazwisko, kontakt,\n";
        $r .= "                               adres, pesel, data urodzenia). UWAGA: zawiera\n";
        $r .= "                               dane wrazliwe — przechowuj bezpiecznie.\n";
        $r .= "data/payments.json           - Historia wszystkich Twoich platnosci (skladki,\n";
        $r .= "                               wpisowe, oplaty za zawody, etc.).\n";
        $r .= "data/trainings.json          - Frekwencja na treningach klubowych.\n";
        $r .= "data/tournaments.json        - Udzial w turniejach + wyniki sportowe.\n";
        $r .= "data/events.json             - Udzial w innych wydarzeniach klubowych.\n";
        $r .= "data/medical.json            - Badania lekarskie / orzeczenia o zdolnosci.\n";
        $r .= "                               UWAGA: dane medyczne — wrazliwa kategoria PII.\n";
        $r .= "data/consents.json           - Historia udzielonych przez Ciebie zgod RODO.\n";
        $r .= "data/communications.json     - Otrzymane e-maile + wiadomosci od klubu.\n";
        $r .= "data/achievements.json       - Zdobyte odznaki i osiagniecia.\n";
        $r .= "data/rankings.json           - Pozycje w rankingach sportowych.\n";
        $r .= "data/licenses.json           - Posiadane licencje sportowe.\n";
        $r .= "data/body_metrics.json       - Pomiary ciala (jesli sa rejestrowane).\n";
        $r .= "data/notification_prefs.json - Twoje preferencje powiadomien.\n";
        $r .= "data/gdpr_requests.json      - Historia Twoich prosb GDPR.\n";
        $r .= "documents/*.pdf              - Dokumenty (umowy, zaswiadczenia, licencje).\n";
        $r .= "photos/*.jpg                 - Zdjecie profilowe.\n";
        $r .= "manifest.json                - Spis plikow + checksumy SHA-256.\n";
        $r .= "README.txt                   - Ten plik.\n\n";

        $r .= "FORMAT DANYCH\n";
        $r .= str_repeat('-', 64) . "\n";
        $r .= "  - Pliki JSON: UTF-8, pretty-print, ISO 8601 dla dat (YYYY-MM-DD HH:MM:SS).\n";
        $r .= "  - Mozesz otworzyc je w dowolnym edytorze tekstu, importowac do arkusza\n";
        $r .= "    kalkulacyjnego lub przesylac do innego systemu (przenoszenie danych).\n\n";

        $r .= "BEZPIECZENSTWO\n";
        $r .= str_repeat('-', 64) . "\n";
        $r .= "  - Archiwum zawiera dane wrazliwe (PESEL, dane medyczne, adres).\n";
        $r .= "  - Nie udostepniaj go osobom trzecim.\n";
        $r .= "  - Po pobraniu zaleca sie zaszyfrowanie pliku haslem (np. 7-Zip / VeraCrypt).\n";
        $r .= "  - Plik na serwerze klubu jest dostepny tylko dla Ciebie i wygasa po 7 dniach.\n\n";

        $r .= "PYTANIA?\n";
        $r .= str_repeat('-', 64) . "\n";
        $r .= "  Skontaktuj sie z administracja klubu ({$clubName}) lub Inspektorem Ochrony\n";
        $r .= "  Danych. Pelne uprawnienia w zakresie RODO opisane sa w dokumencie\n";
        $r .= "  Polityki Prywatnosci dostepnym w portalu (/legal/privacy).\n";

        return $r;
    }
}
