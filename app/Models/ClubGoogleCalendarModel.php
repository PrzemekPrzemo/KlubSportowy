<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Encryption;

/**
 * Per-klub OAuth2 + sync state dla Google Calendar.
 *
 * Każdy klub łączy WŁASNE konto Google (Workspace lub Gmail). Tokeny
 * (access + refresh) szyfrowane AES-256-GCM przez App\Helpers\Encryption
 * — decrypt-on-read tylko gdy realnie używane do API call.
 *
 * Wzoruje się na ClubPaymentGatewayModel / ClubShippingProviderModel
 * (encrypted physical columns z sufiksem _enc).
 */
class ClubGoogleCalendarModel extends ClubScopedModel
{
    protected string $table = 'club_google_calendar';

    /**
     * Logical name → physical encrypted column.
     */
    private const ENCRYPTED_FIELDS = [
        'client_secret' => 'client_secret_enc',
        'access_token'  => 'access_token_enc',
        'refresh_token' => 'refresh_token_enc',
    ];

    /**
     * Konfiguracja dla AKTYWNEGO klubu z ClubContext (zdeszyfrowana).
     */
    public function findForClub(): ?array
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return null;
        }
        return $this->findForClubId($clubId);
    }

    /**
     * Konfiguracja dla wskazanego clubId (bypass scope) — używane przez
     * CLI cron runner i syncer.
     */
    public function findForClubId(int $clubId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM club_google_calendar WHERE club_id = ? LIMIT 1"
        );
        $stmt->execute([$clubId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return $this->decryptRow($row);
    }

    /**
     * Zwraca config gotowy do GoogleCalendarClient. Łączy per-klub
     * client_id/secret z globalnymi z config/google.php (fallback).
     *
     * @return array<string,mixed>|null
     */
    public function decryptedConfig(?int $clubIdOverride = null): ?array
    {
        $clubId = $clubIdOverride ?? $this->clubId();
        if ($clubId === null) {
            return null;
        }
        $row = $this->findForClubId($clubId);
        if (!$row) {
            return null;
        }

        $globalCfgFile = dirname(__DIR__, 2) . '/config/google.php';
        $globalCfg = is_file($globalCfgFile) ? require $globalCfgFile : [];

        // Variant A fallback: jeśli klub nie ma własnych OAuth credentials,
        // używamy globalnych z config/google.php (env vars).
        $clientId     = $row['client_id']     ?: ($globalCfg['client_id']     ?? '');
        $clientSecret = $row['client_secret'] ?: ($globalCfg['client_secret'] ?? '');

        return [
            'club_id'              => $clubId,
            'google_account_email' => $row['google_account_email'] ?? null,
            'calendar_id'          => $row['calendar_id'] ?: 'primary',
            'client_id'            => (string)$clientId,
            'client_secret'        => (string)$clientSecret,
            'access_token'         => $row['access_token']  ?? null,
            'refresh_token'        => $row['refresh_token'] ?? null,
            'token_expires_at'     => $row['token_expires_at'] ?? null,
            'sync_direction'       => $row['sync_direction'] ?? 'push',
            'is_active'            => (int)($row['is_active'] ?? 0),
            'last_sync_at'         => $row['last_sync_at'] ?? null,
            'last_sync_status'     => $row['last_sync_status'] ?? null,
            'timezone'             => (string)($globalCfg['timezone'] ?? 'Europe/Warsaw'),
            'scope'                => (string)($globalCfg['scope'] ?? 'https://www.googleapis.com/auth/calendar'),
        ];
    }

    /**
     * Insert/update — szyfruje wrażliwe pola, puste zostawia stare.
     *
     * @param array<string,mixed> $data
     */
    public function upsert(array $data): int
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            throw new \RuntimeException('upsert requires active ClubContext');
        }

        $existing = $this->findForClub();

        // Szyfruj wrażliwe pola → kolumny _enc
        foreach (self::ENCRYPTED_FIELDS as $logical => $physical) {
            if (array_key_exists($logical, $data)) {
                if ($data[$logical] !== '' && $data[$logical] !== null) {
                    $data[$physical] = Encryption::encrypt((string)$data[$logical]);
                }
                unset($data[$logical]);
            }
        }

        $data['club_id'] = $clubId;

        if ($existing) {
            $id = (int)$existing['id'];
            unset($data['club_id']);
            $this->update($id, $data);
            return $id;
        }
        return $this->insert($data);
    }

    /**
     * Update tokenów po OAuth exchange / refresh — używany bezpośrednio
     * przez syncer / controller. Pomija ClubContext (operuje po clubId).
     */
    public function updateTokens(
        int $clubId,
        string $accessToken,
        ?string $refreshToken,
        int $expiresIn,
        ?string $googleAccountEmail = null
    ): void {
        $sets   = [];
        $params = [];

        $sets[]   = '`access_token_enc` = ?';
        $params[] = Encryption::encrypt($accessToken);

        if ($refreshToken !== null && $refreshToken !== '') {
            $sets[]   = '`refresh_token_enc` = ?';
            $params[] = Encryption::encrypt($refreshToken);
        }

        $sets[]   = '`token_expires_at` = ?';
        $params[] = date('Y-m-d H:i:s', time() + max(0, $expiresIn - 30));

        if ($googleAccountEmail !== null && $googleAccountEmail !== '') {
            $sets[]   = '`google_account_email` = ?';
            $params[] = $googleAccountEmail;
        }

        $params[] = $clubId;
        $sql = "UPDATE club_google_calendar SET " . implode(', ', $sets) . " WHERE club_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function markSync(int $clubId, string $status, ?string $message = null): void
    {
        $stmt = $this->db->prepare(
            "UPDATE club_google_calendar
                SET last_sync_at = NOW(), last_sync_status = ?, last_sync_message = ?
              WHERE club_id = ?"
        );
        $stmt->execute([$status, $message !== null ? mb_substr($message, 0, 500) : null, $clubId]);
    }

    /**
     * Czyści wszystkie tokeny + dezaktywuje (disconnect).
     */
    public function disconnect(int $clubId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE club_google_calendar
                SET access_token_enc = NULL,
                    refresh_token_enc = NULL,
                    token_expires_at = NULL,
                    google_account_email = NULL,
                    is_active = 0,
                    last_sync_status = 'disconnected'
              WHERE club_id = ?"
        );
        $stmt->execute([$clubId]);
    }

    /**
     * Lista aktywnych integracji (do CLI cron runnera).
     * @return array<int, array<string,mixed>>
     */
    public function listActive(): array
    {
        $stmt = $this->db->query(
            "SELECT cgc.*, c.name AS club_name
               FROM club_google_calendar cgc
               JOIN clubs c ON c.id = cgc.club_id
              WHERE cgc.is_active = 1 AND c.is_active = 1
              ORDER BY cgc.club_id"
        );
        $rows = $stmt->fetchAll();
        return array_map(fn(array $r): array => $this->decryptRow($r), $rows);
    }

    /**
     * Decrypt-on-read: _enc → logiczne nazwy.
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function decryptRow(array $row): array
    {
        foreach (self::ENCRYPTED_FIELDS as $logical => $physical) {
            if (!empty($row[$physical])) {
                try {
                    $row[$logical] = Encryption::decrypt((string)$row[$physical]);
                } catch (\Throwable) {
                    $row[$logical . '_decrypt_error'] = true;
                    $row[$logical] = null;
                }
            } else {
                $row[$logical] = null;
            }
        }
        return $row;
    }
}
