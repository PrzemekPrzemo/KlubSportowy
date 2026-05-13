<?php

namespace App\Models;

use App\Helpers\Encryption;

/**
 * Per-klub konfiguracja providera wysyłki (na razie tylko InPost).
 *
 * Każdy klub posiada własny token API ShipX i organization_id. Pozwala to
 * korzystać z osobnej umowy handlowej (cennik, limity) — analogicznie do
 * ClubPaymentGatewayModel.
 *
 * Wrażliwe pola (api_token, organization_id) szyfrowane AES-256-GCM przez
 * App\Helpers\Encryption. Decrypt-on-read tylko w decryptedConfig().
 */
class ClubShippingProviderModel extends ClubScopedModel
{
    protected string $table = 'club_shipping_providers';

    public static array $PROVIDERS = [
        'inpost' => 'InPost (Paczkomaty / Kurier)',
    ];

    public static array $SIZES = [
        'A' => 'A — mała (8×38×64 cm)',
        'B' => 'B — średnia (19×38×64 cm)',
        'C' => 'C — duża (41×38×64 cm)',
    ];

    public static array $SERVICES = [
        'inpost_locker_standard'  => 'Paczkomat (Standard)',
        'inpost_courier_standard' => 'Kurier InPost (Standard)',
    ];

    /**
     * Wrażliwe pola PRZECHOWYWANE w kolumnach `_enc`. Mapowanie:
     *   logical name      → physical column
     */
    private const ENCRYPTED_FIELDS = [
        'organization_id' => 'organization_id_enc',
        'api_token'       => 'api_token_enc',
    ];

    /**
     * Znajdź konfigurację dla obecnego klubu (provider domyślnie 'inpost').
     * Zwraca zdeszyfrowane wartości w kluczach 'api_token', 'organization_id'.
     */
    public function findByProvider(string $provider = 'inpost'): ?array
    {
        $clubId = $this->clubId();
        if ($clubId === null) return null;

        $stmt = $this->db->prepare(
            "SELECT * FROM club_shipping_providers WHERE club_id = ? AND provider = ? LIMIT 1"
        );
        $stmt->execute([$clubId, $provider]);
        $row = $stmt->fetch();
        if (!$row) return null;

        return $this->decryptRow($row);
    }

    /**
     * Aktywny provider klubu (używany w runtime). Max 1 aktywny (UNIQUE
     * (club_id, provider) + obecnie tylko InPost).
     */
    public function activeProvider(): ?array
    {
        $clubId = $this->clubId();
        if ($clubId === null) return null;

        $stmt = $this->db->prepare(
            "SELECT * FROM club_shipping_providers
             WHERE club_id = ? AND is_active = 1
             ORDER BY is_sandbox ASC LIMIT 1"
        );
        $stmt->execute([$clubId]);
        $row = $stmt->fetch();
        return $row ? $this->decryptRow($row) : null;
    }

    /**
     * Zwraca config gotowy do podania InPostAdapter (zdeszyfrowane creds +
     * wszystkie pola sender_*).
     */
    public function decryptedConfig(string $provider = 'inpost'): ?array
    {
        $row = $this->findByProvider($provider);
        if (!$row) return null;
        // findByProvider już deszyfruje pola, zwracamy wprost.
        return $row;
    }

    /**
     * Upsert konfiguracji. Wrażliwe pola wchodzą w $data jako 'api_token' i
     * 'organization_id' (plain). Jeśli puste — zachowujemy poprzednie
     * zaszyfrowane wartości.
     */
    public function upsert(string $provider, array $data): int
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            throw new \RuntimeException('upsert requires active ClubContext');
        }

        $existing = $this->findByProvider($provider);

        // Przepisz logiczne -> physical i zaszyfruj
        foreach (self::ENCRYPTED_FIELDS as $logical => $physical) {
            if (isset($data[$logical]) && $data[$logical] !== '') {
                $data[$physical] = Encryption::encrypt((string)$data[$logical]);
            }
            // jeśli puste → pomiń (zostanie stara zaszyfrowana wartość)
            unset($data[$logical]);
        }

        $data['club_id']  = $clubId;
        $data['provider'] = $provider;

        if ($existing) {
            $id = (int)$existing['id'];
            unset($data['club_id'], $data['provider']);
            $this->update($id, $data);
            return $id;
        }
        return $this->insert($data);
    }

    /**
     * Decrypt-on-read: czyta _enc kolumny i wystawia logical names.
     */
    private function decryptRow(array $row): array
    {
        foreach (self::ENCRYPTED_FIELDS as $logical => $physical) {
            if (!empty($row[$physical])) {
                try {
                    $row[$logical] = Encryption::decrypt((string)$row[$physical]);
                } catch (\Throwable) {
                    // Stary plaintext lub corrupted — admin powinien re-zapisać creds
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
