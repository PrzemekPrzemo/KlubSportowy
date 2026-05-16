<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Auth;
use App\Helpers\Database;
use App\Helpers\Encryption;

/**
 * Model for `club_ksef_config` — per-klub konfiguracja integracji z KSeF.
 *
 * Każda metoda jest sztywno scope'owana po `club_id` (multi-tenant safety).
 * Sekrety (api_token, cert_password) są szyfrowane przez encryptForClub.
 *
 * Audit log: każda zmiana konfiguracji jest logowana do `ksef_audit_log`
 * z user_id + ip_address + zwięzłym opisem ('what changed'). NIE logujemy
 * wartości sekretów do audit log — tylko fakt że zostały zmienione.
 *
 * Phase 1 scope: CRUD na configu + audit. Faza 2 doda metody pomocnicze
 * do invoice send / status check.
 */
final class ClubKsefConfigModel
{
    /**
     * @return array<string,mixed>|null
     */
    public function findForClub(int $clubId): ?array
    {
        $pdo = Database::pdo();
        $st  = $pdo->prepare("SELECT * FROM club_ksef_config WHERE club_id = ? LIMIT 1");
        $st->execute([$clubId]);
        $row = $st->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Zwraca config + wszystkie kluby (LEFT JOIN) — używane przez super admina
     * do listy wszystkich klubów z ich statusem KSeF.
     *
     * @return array<int, array<string,mixed>>
     */
    public function listAllClubs(bool $enabledOnly = false): array
    {
        $pdo  = Database::pdo();
        $sql  = "SELECT c.id AS club_id, c.name AS club_name,
                        kc.id AS config_id, kc.enabled, kc.mode, kc.nip,
                        kc.last_connection_test_at, kc.last_connection_test_status,
                        kc.last_connection_test_message
                   FROM clubs c
              LEFT JOIN club_ksef_config kc ON kc.club_id = c.id";
        if ($enabledOnly) {
            $sql .= "\n              WHERE kc.enabled = 1";
        }
        $sql .= "\n           ORDER BY c.name ASC";
        $st = $pdo->query($sql);
        return $st !== false ? $st->fetchAll() : [];
    }

    /**
     * Upsert configu klubu — wstawia wiersz jeśli nie istnieje, w innym
     * wypadku aktualizuje tylko przekazane pola (NULL = "nie zmieniaj").
     *
     * Sekrety: api_token i cert_password są szyfrowane PRZED zapisem.
     * Pusty string ("") jest traktowany jako "nie zmieniaj" — UI nigdy
     * nie wysyła zaszyfrowanego sekretu z powrotem, więc puste pole =
     * "zachowaj istniejący".
     *
     * @param array{
     *   nip?:?string, mode?:?string, api_token?:?string,
     *   cert_path?:?string, cert_password?:?string,
     *   authorized_subject_identifier?:?string,
     *   enabled?:?int,
     * } $data
     */
    public function upsert(int $clubId, array $data): void
    {
        $pdo      = Database::pdo();
        $existing = $this->findForClub($clubId);

        $cols   = ['club_id'];
        $values = [$clubId];

        // Mapuje plaintext → encrypted column. Puste/null = pomiń pole.
        if (!empty($data['nip'])) {
            $cols[]   = 'nip';
            $values[] = preg_replace('/\D/', '', (string)$data['nip']);
        }
        if (!empty($data['mode']) && in_array($data['mode'], ['test', 'prod'], true)) {
            $cols[]   = 'mode';
            $values[] = $data['mode'];
        }
        if (!empty($data['api_token'])) {
            $cols[]   = 'api_token_encrypted';
            $values[] = Encryption::encryptForClub((string)$data['api_token'], $clubId);
        }
        if (!empty($data['cert_path'])) {
            $cols[]   = 'cert_path';
            $values[] = (string)$data['cert_path'];
        }
        if (!empty($data['cert_password'])) {
            $cols[]   = 'cert_password_encrypted';
            $values[] = Encryption::encryptForClub((string)$data['cert_password'], $clubId);
        }
        if (!empty($data['authorized_subject_identifier'])) {
            $cols[]   = 'authorized_subject_identifier';
            $values[] = (string)$data['authorized_subject_identifier'];
        }
        if (isset($data['enabled'])) {
            $cols[]   = 'enabled';
            $values[] = (int)$data['enabled'] ? 1 : 0;
        }

        if ($existing === null) {
            $placeholders = implode(',', array_fill(0, count($cols), '?'));
            $sql = "INSERT INTO club_ksef_config (" . implode(',', $cols) . ") VALUES ({$placeholders})";
            $pdo->prepare($sql)->execute($values);
        } else {
            // UPDATE — pomiń club_id (klucz)
            $setCols = array_slice($cols, 1);
            $setVals = array_slice($values, 1);
            if (empty($setCols)) {
                return;
            }
            $set = implode(', ', array_map(static fn($c) => "{$c} = ?", $setCols));
            $sql = "UPDATE club_ksef_config SET {$set} WHERE club_id = ?";
            $pdo->prepare($sql)->execute([...$setVals, $clubId]);
        }
    }

    /**
     * Aktualizuje wynik ostatniego testu połączenia.
     */
    public function recordConnectionTest(int $clubId, bool $ok, string $message): void
    {
        $pdo = Database::pdo();
        // Insert blank row if missing — pozwala zapisać wynik testu nawet
        // jeśli klub nie wypełnił jeszcze formularza (np. test z poziomu
        // super admina).
        if ($this->findForClub($clubId) === null) {
            $pdo->prepare("INSERT INTO club_ksef_config (club_id) VALUES (?)")->execute([$clubId]);
        }
        $pdo->prepare(
            "UPDATE club_ksef_config
                SET last_connection_test_at = NOW(),
                    last_connection_test_status = ?,
                    last_connection_test_message = ?
              WHERE club_id = ?"
        )->execute([$ok ? 'ok' : 'failed', mb_substr($message, 0, 2000), $clubId]);
    }

    /**
     * Toggle enabled flag. Zwraca nową wartość.
     */
    public function toggleEnabled(int $clubId): int
    {
        $pdo = Database::pdo();
        $cur = $this->findForClub($clubId);
        $new = ($cur && (int)$cur['enabled'] === 1) ? 0 : 1;
        if ($cur === null) {
            $pdo->prepare("INSERT INTO club_ksef_config (club_id, enabled) VALUES (?, ?)")
                ->execute([$clubId, $new]);
        } else {
            $pdo->prepare("UPDATE club_ksef_config SET enabled = ? WHERE club_id = ?")
                ->execute([$new, $clubId]);
        }
        return $new;
    }

    /**
     * Zapisuje wpis do `ksef_audit_log`. Wartości sekretów NIE są logowane
     * — tylko nazwa pola które się zmieniło ('details' = "Token updated").
     */
    public function audit(int $clubId, string $action, ?string $details = null): void
    {
        $allowed = ['config_change','enabled','disabled','connection_test','token_set','cert_uploaded'];
        if (!in_array($action, $allowed, true)) {
            return;
        }
        $pdo = Database::pdo();
        $pdo->prepare(
            "INSERT INTO ksef_audit_log (club_id, action, user_id, details, ip_address)
                  VALUES (?, ?, ?, ?, ?)"
        )->execute([
            $clubId,
            $action,
            Auth::id(),
            $details,
            (string)($_SERVER['REMOTE_ADDR'] ?? ''),
        ]);
    }

    /**
     * Czy klub ma KSeF aktywne przez super admina (feature flag).
     */
    public function isEnabledForClub(int $clubId): bool
    {
        $pdo = Database::pdo();
        $st  = $pdo->prepare("SELECT enabled FROM club_ksef_config WHERE club_id = ? LIMIT 1");
        $st->execute([$clubId]);
        $row = $st->fetch();
        return $row !== false && (int)$row['enabled'] === 1;
    }

    /**
     * Walidacja NIP — 10 cyfr + opcjonalna suma kontrolna.
     */
    public static function validateNip(string $nip): bool
    {
        $nip = preg_replace('/\D/', '', $nip) ?? '';
        if (strlen($nip) !== 10) {
            return false;
        }
        // Reject obvious filler (same digit ×10)
        if (preg_match('/^(\d)\1{9}$/', $nip)) {
            return false;
        }
        // Suma kontrolna NIP (PL): waga dla pozycji 0..8: 6,5,7,2,3,4,5,6,7
        $weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
        $sum     = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $weights[$i] * (int)$nip[$i];
        }
        $check = $sum % 11;
        if ($check === 10) {
            return false;
        }
        return $check === (int)$nip[9];
    }
}
