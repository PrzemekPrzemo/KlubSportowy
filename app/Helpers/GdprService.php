<?php

namespace App\Helpers;

use App\Models\TenantAccessLogModel;
use ZipArchive;

/**
 * Centralna logika GDPR: anonimizacja czlonka + generowanie eksportu danych.
 *
 * Wszystkie metody wymagaja konkretnego (memberId, clubId) — defense-in-depth
 * przeciw cross-tenant operacjom. Logujemy do tenant_access_log.
 */
class GdprService
{
    /**
     * Anonimizuje dane czlonka (NULL out PII).
     *
     * Zachowuje rekord w `members` z `is_anonymized=1` zeby agregaty
     * (frekwencja, wyniki, statystyki klubu) pozostaly spojne.
     * Usuwa zgody, sesje push, tokeny API, custom field values z PII.
     *
     * @param int $memberId
     * @param int $clubId   Wymagany guard — anonimizujemy tylko jesli member.club_id == clubId.
     * @return bool         true jesli operacja sie powiodla.
     */
    public static function anonymizeMember(int $memberId, int $clubId): bool
    {
        $pdo = Database::pdo();

        $pdo->beginTransaction();
        try {
            // Cross-tenant guard
            $stmt = $pdo->prepare("SELECT id, club_id FROM members WHERE id = ? AND club_id = ? LIMIT 1");
            $stmt->execute([$memberId, $clubId]);
            $member = $stmt->fetch();
            if (!$member) {
                $pdo->rollBack();
                return false;
            }

            // 1) Anonimizuj PII w members
            $anonData = [
                'first_name'      => '[Usuniety]',
                'last_name'       => '[czlonek]',
                'pesel'           => null,
                'email'           => null,
                'phone'           => null,
                'address_street'  => null,
                'address_city'    => null,
                'address_postal'  => null,
                'birth_date'      => null,
                'photo_path'      => null,
                'portal_password' => null,
                'notes'           => null,
                'is_anonymized'   => 1,
                'anonymized_at'   => date('Y-m-d H:i:s'),
                'status'          => 'wykreslony',
            ];

            // Wykryj opcjonalne kolumny (rozne migracje moga ich nie miec)
            $optional = ['email_hash', 'totp_secret', 'totp_enabled', 'totp_confirmed_at', 'portal_last_login'];
            $existingCols = self::columnsOf($pdo, 'members');
            foreach ($optional as $col) {
                if (in_array($col, $existingCols, true)) {
                    if (in_array($col, ['totp_enabled'], true)) {
                        $anonData[$col] = 0;
                    } else {
                        $anonData[$col] = null;
                    }
                }
            }

            $cols = array_keys($anonData);
            $set  = implode(' = ?, ', array_map(fn($c) => "`{$c}`", $cols)) . ' = ?';
            $sql  = "UPDATE members SET {$set} WHERE id = ? AND club_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([...array_values($anonData), $memberId, $clubId]);

            // 2) Usun zgody RODO (ale zostawiamy historie w gdpr_requests dla audit)
            self::safeExec($pdo, "DELETE FROM member_consents WHERE member_id = ? AND club_id = ?", [$memberId, $clubId]);

            // 3) Usun tokeny push, sesje, mobile API tokens
            self::safeExec($pdo, "DELETE FROM push_subscriptions WHERE member_id = ?", [$memberId]);
            self::safeExec($pdo, "DELETE FROM member_api_tokens WHERE member_id = ?", [$memberId]);

            // 4) Custom field values (jesli sa - moga zawierac PII)
            self::safeExec($pdo, "DELETE FROM member_custom_field_values WHERE member_id = ?", [$memberId]);

            // 5) Kontakty awaryjne (PII innej osoby)
            self::safeExec($pdo, "DELETE FROM emergency_contacts WHERE member_id = ?", [$memberId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        // Audit log (poza transakcja)
        try {
            (new TenantAccessLogModel())->logBypass(
                'members',
                'delete',
                __FILE__,
                __LINE__,
                self::class,
                'critical',
                'GDPR anonymize member_id=' . $memberId . ' club_id=' . $clubId
            );
        } catch (\Throwable) {}

        return true;
    }

    /**
     * Buduje ZIP z eksportem danych czlonka (art. 20 RODO).
     *
     * Zawartosc:
     *   - profile.json     — wszystkie pola czlonka
     *   - payments.json    — historia platnosci
     *   - events.json      — udzialy w wydarzeniach
     *   - trainings.json   — frekwencja
     *   - consents.json    — historia zgod RODO
     *   - medical.json     — badania lekarskie
     *   - licenses.json    — licencje
     *   - rankings.json    — wyniki sportowe
     *   - README.txt       — opis + data wygenerowania
     *
     * @return string Absolutna sciezka do pliku ZIP.
     */
    public static function buildExportZip(int $memberId, int $clubId): string
    {
        $pdo = Database::pdo();

        // Cross-tenant guard
        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ? AND club_id = ? LIMIT 1");
        $stmt->execute([$memberId, $clubId]);
        $member = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$member) {
            throw new \RuntimeException('Member not found in club (cross-tenant guard).');
        }

        $dir = ROOT_PATH . '/storage/gdpr_exports';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $filename = sprintf('gdpr_export_member_%d_%s.zip', $memberId, date('Ymd_His'));
        $zipPath  = $dir . '/' . $filename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Nie udalo sie utworzyc pliku ZIP: ' . $zipPath);
        }

        $generatedAt = date('Y-m-d H:i:s');

        // 1) Profile
        $zip->addFromString('profile.json', self::jsonPretty($member));

        // 2) Payments
        $payments = self::safeFetchAll($pdo, "SELECT * FROM payments WHERE member_id = ? ORDER BY payment_date DESC", [$memberId]);
        $zip->addFromString('payments.json', self::jsonPretty($payments));

        // 3) Events
        $events = self::safeFetchAll(
            $pdo,
            "SELECT ee.*, e.name AS event_name, e.start_date
             FROM event_entries ee JOIN events e ON e.id = ee.event_id
             WHERE ee.member_id = ?
             ORDER BY e.start_date DESC",
            [$memberId]
        );
        $zip->addFromString('events.json', self::jsonPretty($events));

        // 4) Trainings (frekwencja)
        $trainings = self::safeFetchAll(
            $pdo,
            "SELECT ta.*, t.name AS training_name, t.start_time
             FROM training_attendees ta JOIN trainings t ON t.id = ta.training_id
             WHERE ta.member_id = ?
             ORDER BY t.start_time DESC",
            [$memberId]
        );
        $zip->addFromString('trainings.json', self::jsonPretty($trainings));

        // 5) Consents
        $consents = self::safeFetchAll(
            $pdo,
            "SELECT * FROM member_consents WHERE member_id = ? AND club_id = ?",
            [$memberId, $clubId]
        );
        $zip->addFromString('consents.json', self::jsonPretty($consents));

        // 6) Medical exams
        $medical = self::safeFetchAll($pdo, "SELECT * FROM member_medical_exams WHERE member_id = ?", [$memberId]);
        $zip->addFromString('medical.json', self::jsonPretty($medical));

        // 7) Licenses
        $licenses = self::safeFetchAll($pdo, "SELECT * FROM member_licenses WHERE member_id = ?", [$memberId]);
        $zip->addFromString('licenses.json', self::jsonPretty($licenses));

        // 8) Rankings
        $rankings = self::safeFetchAll($pdo, "SELECT * FROM sport_rankings WHERE member_id = ?", [$memberId]);
        $zip->addFromString('rankings.json', self::jsonPretty($rankings));

        // 9) Notification preferences
        $prefs = self::safeFetchAll($pdo, "SELECT * FROM member_notification_prefs WHERE member_id = ?", [$memberId]);
        $zip->addFromString('notification_prefs.json', self::jsonPretty($prefs));

        // 10) Body metrics (B5)
        $metrics = self::safeFetchAll($pdo, "SELECT * FROM body_metrics WHERE member_id = ?", [$memberId]);
        $zip->addFromString('body_metrics.json', self::jsonPretty($metrics));

        // 11) Historia prosb GDPR
        $gdprHistory = self::safeFetchAll($pdo, "SELECT id, request_type, status, requested_at, processed_at, reason FROM gdpr_requests WHERE member_id = ?", [$memberId]);
        $zip->addFromString('gdpr_requests.json', self::jsonPretty($gdprHistory));

        // README
        $readme  = "Eksport danych GDPR (art. 20 RODO)\n";
        $readme .= "========================================\n\n";
        $readme .= "Wygenerowano: {$generatedAt}\n";
        $readme .= "Member ID: {$memberId}\n";
        $readme .= "Club ID: {$clubId}\n\n";
        $readme .= "Zawartosc archiwum:\n";
        $readme .= "  profile.json            - dane czlonka (imie, nazwisko, kontakt, etc.)\n";
        $readme .= "  payments.json           - historia platnosci skladek\n";
        $readme .= "  events.json             - udzial w wydarzeniach\n";
        $readme .= "  trainings.json          - frekwencja na treningach\n";
        $readme .= "  consents.json           - udzielone zgody RODO\n";
        $readme .= "  medical.json            - badania lekarskie\n";
        $readme .= "  licenses.json           - licencje sportowe\n";
        $readme .= "  rankings.json           - wyniki i rankingi\n";
        $readme .= "  notification_prefs.json - preferencje powiadomien\n";
        $readme .= "  body_metrics.json       - pomiary ciala (jesli wprowadzone)\n";
        $readme .= "  gdpr_requests.json      - historia prosb GDPR\n\n";
        $readme .= "Pliki sa w formacie JSON (UTF-8). Mozesz je otworzyc w dowolnym\n";
        $readme .= "edytorze tekstu lub zaimportowac do arkusza kalkulacyjnego.\n\n";
        $readme .= "Plik wygasa po 7 dniach (auto-cleanup).\n";
        $zip->addFromString('README.txt', $readme);

        $zip->close();

        try {
            (new TenantAccessLogModel())->logBypass(
                'members',
                'read',
                __FILE__,
                __LINE__,
                self::class,
                'info',
                'GDPR export ZIP member_id=' . $memberId . ' file=' . $filename
            );
        } catch (\Throwable) {}

        return $zipPath;
    }

    /**
     * Czysci wygasniete pliki eksportu (cron lub on-access).
     */
    public static function pruneExpiredExports(): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query(
            "SELECT id, export_file_path FROM gdpr_requests
             WHERE export_file_path IS NOT NULL
               AND export_file_expires_at IS NOT NULL
               AND export_file_expires_at < NOW()"
        );
        $count = 0;
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $path = $row['export_file_path'];
            if ($path && is_file($path)) {
                @unlink($path);
            }
            $pdo->prepare("UPDATE gdpr_requests SET export_file_path = NULL WHERE id = ?")
                ->execute([(int)$row['id']]);
            $count++;
        }
        return $count;
    }

    // ----------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------

    private static function jsonPretty(mixed $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Wykonaj DELETE/UPDATE — ignoruj jesli tabela nie istnieje.
     */
    private static function safeExec(\PDO $pdo, string $sql, array $params): void
    {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        } catch (\PDOException) {
            // Tabela moze nie istniec w danej konfiguracji — ok
        }
    }

    private static function safeFetchAll(\PDO $pdo, string $sql, array $params): array
    {
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException) {
            return [];
        }
    }

    private static function columnsOf(\PDO $pdo, string $table): array
    {
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
            return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'Field');
        } catch (\PDOException) {
            return [];
        }
    }
}
