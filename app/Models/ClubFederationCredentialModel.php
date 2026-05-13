<?php

namespace App\Models;

use App\Helpers\Encryption;

/**
 * Per-klub credentials do federacji sportowych (PZPN/PZSS/PZKosz/PZLA/…).
 *
 * Wzorowane 1:1 na ClubPaymentGatewayModel:
 *   - extends ClubScopedModel (auto-filter club_id)
 *   - wrażliwe pola (api_username, api_password, api_token) szyfrowane
 *     AES-256-GCM przez App\Helpers\Encryption — kolumny w DB to
 *     *_enc (TEXT). Aplikacja w warstwie modelu mapuje plain ↔ enc.
 *   - upsert() szyfruje przy zapisie; findByFederation() odszyfrowuje.
 *
 * Konwencja kolumn:
 *   api_username_enc  ↔  $row['api_username'] (decrypted)
 *   api_password_enc  ↔  $row['api_password']
 *   api_token_enc     ↔  $row['api_token']
 */
class ClubFederationCredentialModel extends ClubScopedModel
{
    protected string $table = 'club_federation_credentials';

    /** Pola plain ↔ enc (suffix _enc w DB). */
    private const ENCRYPTED_FIELDS = ['api_username', 'api_password', 'api_token'];

    /**
     * Lista wszystkich skonfigurowanych federacji dla klubu (bez odszyfrowywania).
     */
    public function listForClub(): array
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return [];
        }
        $stmt = $this->db->prepare(
            "SELECT id, club_id, federation_code, is_sandbox, organization_id,
                    notes, is_active, last_export_at, last_export_status,
                    CASE WHEN api_username_enc IS NOT NULL AND api_username_enc != '' THEN 1 ELSE 0 END AS has_username,
                    CASE WHEN api_password_enc IS NOT NULL AND api_password_enc != '' THEN 1 ELSE 0 END AS has_password,
                    CASE WHEN api_token_enc    IS NOT NULL AND api_token_enc    != '' THEN 1 ELSE 0 END AS has_token,
                    created_at, updated_at
             FROM club_federation_credentials
             WHERE club_id = ?
             ORDER BY federation_code"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    /**
     * Znajdź konfigurację po kodzie federacji (decrypted).
     */
    public function findByFederation(string $federationCode): ?array
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT * FROM club_federation_credentials
             WHERE club_id = ? AND federation_code = ? LIMIT 1"
        );
        $stmt->execute([$clubId, $federationCode]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return $this->decryptRow($row);
    }

    /**
     * Wszystkie aktywne credentiale klubu — używane przez CLI export runner.
     */
    public function activeForExport(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT * FROM club_federation_credentials WHERE is_active = 1";
        $params = [];
        if ($clubId !== null) {
            $sql .= " AND club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY club_id, federation_code";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        return array_map(fn($r) => $this->decryptRow($r), $rows);
    }

    /**
     * Zapisz/aktualizuj credentials. Wrażliwe pola są szyfrowane;
     * puste pola NIE nadpisują istniejących zaszyfrowanych wartości.
     */
    public function upsert(string $federationCode, array $data): int
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            throw new \RuntimeException('upsert requires active ClubContext');
        }

        $existing = $this->findByFederation($federationCode);

        // Mapuj plain → enc + szyfruj non-empty
        $row = [];
        foreach (self::ENCRYPTED_FIELDS as $field) {
            $encCol = $field . '_enc';
            if (isset($data[$field]) && $data[$field] !== '') {
                $row[$encCol] = Encryption::encrypt((string)$data[$field]);
            }
            // Pusty = nie nadpisuj (zostaje stara wartość)
        }

        // Plain (non-encrypted) pola
        foreach (['organization_id', 'notes', 'last_export_at', 'last_export_status'] as $f) {
            if (array_key_exists($f, $data)) {
                $row[$f] = $data[$f];
            }
        }
        if (array_key_exists('is_sandbox', $data)) {
            $row['is_sandbox'] = $data['is_sandbox'] ? 1 : 0;
        }
        if (array_key_exists('is_active', $data)) {
            $row['is_active']  = $data['is_active'] ? 1 : 0;
        }

        if ($existing) {
            $this->update((int)$existing['id'], $row);
            return (int)$existing['id'];
        }

        $row['club_id']         = $clubId;
        $row['federation_code'] = $federationCode;
        return $this->insert($row);
    }

    /** Zaktualizuj status ostatniego eksportu — wołane przez CLI runner. */
    public function markExportRun(int $id, string $status): void
    {
        $this->update($id, [
            'last_export_at'     => date('Y-m-d H:i:s'),
            'last_export_status' => $status,
        ]);
    }

    /**
     * Decrypt-on-read: *_enc → plain (klucze bez suffix).
     */
    private function decryptRow(array $row): array
    {
        foreach (self::ENCRYPTED_FIELDS as $field) {
            $encCol = $field . '_enc';
            if (!empty($row[$encCol])) {
                try {
                    $row[$field] = Encryption::decrypt((string)$row[$encCol]);
                } catch (\Throwable) {
                    $row[$field] = null;
                    $row[$field . '_decrypt_error'] = true;
                }
            } else {
                $row[$field] = null;
            }
        }
        return $row;
    }
}
