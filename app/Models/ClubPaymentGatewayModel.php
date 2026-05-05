<?php

namespace App\Models;

use App\Helpers\Encryption;

/**
 * Per-klub konfiguracja bramki płatności (Przelewy24, PayU, Stripe, tpay).
 *
 * Każdy klub ma własne API credentials — pozwala SaaS-owi rozliczać każdą
 * organizację z jej własnym kontem bankowym/merchantem (zamiast jednego
 * globalnego konta Klubdesk).
 *
 * Wrażliwe pola (api_key, api_secret, crc_key, webhook_secret) są
 * szyfrowane przez App\Helpers\Encryption (AES-256-GCM) przed zapisem
 * do DB. Decrypt tylko gdy faktycznie używamy do API call'a.
 *
 * Izolacja per club przez ClubScopedModel.
 */
class ClubPaymentGatewayModel extends ClubScopedModel
{
    protected string $table = 'club_payment_gateways';

    public static array $PROVIDERS = [
        'przelewy24' => 'Przelewy24',
        'payu'       => 'PayU',
        'stripe'     => 'Stripe',
        'tpay'       => 'Tpay',
        'manual'     => 'Tylko ręczne (przelew tradycyjny)',
    ];

    private const ENCRYPTED_FIELDS = ['api_key', 'api_secret', 'crc_key', 'webhook_secret'];

    /**
     * Lista wszystkich skonfigurowanych bramek dla klubu.
     */
    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT id, club_id, provider, is_active, is_sandbox, merchant_id,
                    return_url, notify_url, currency, notes,
                    -- maskowanie wrażliwych pól w listach (pierwsze i ostatnie 4 znaki)
                    CASE WHEN api_key IS NOT NULL AND api_key != ''
                         THEN '••••' ELSE NULL END AS api_key_masked,
                    created_at, updated_at
             FROM club_payment_gateways
             WHERE club_id = ?
             ORDER BY provider"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    /**
     * Znajdź konfigurację po providerze (zaszyfrowane pola odszyfrowane).
     */
    public function findByProvider(string $provider): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM club_payment_gateways WHERE club_id = ? AND provider = ? LIMIT 1"
        );
        $stmt->execute([$clubId, $provider]);
        $row = $stmt->fetch();
        if (!$row) return null;

        return $this->decryptRow($row);
    }

    /**
     * Aktywna bramka klubu — używana w runtime do tworzenia transakcji.
     * Zwraca pierwszą aktywną (max 1 oczekiwana, ale UI pozwala na sandbox+prod).
     */
    public function activeGateway(): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM club_payment_gateways
             WHERE club_id = ? AND is_active = 1
             ORDER BY is_sandbox ASC LIMIT 1"
        );
        $stmt->execute([$clubId]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return $this->decryptRow($row);
    }

    /**
     * Zapisz/aktualizuj konfigurację — szyfruje wrażliwe pola.
     * Jeśli któreś encrypted-pole jest puste w $data, NIE nadpisuje
     * (zostaje stare zaszyfrowane).
     */
    public function upsert(string $provider, array $data): int
    {
        $clubId = $this->clubId();
        if ($clubId === null) {
            throw new \RuntimeException('upsert requires active ClubContext');
        }

        $existing = $this->findByProvider($provider);

        // Szyfruj non-empty wrażliwe pola
        foreach (self::ENCRYPTED_FIELDS as $f) {
            if (isset($data[$f]) && $data[$f] !== '') {
                $data[$f] = Encryption::encrypt((string)$data[$f]);
            } else {
                // Zachowaj stare zaszyfrowane (re-encrypt = zmiana key version OK)
                unset($data[$f]);
            }
        }

        $data['club_id']  = $clubId;
        $data['provider'] = $provider;

        if ($existing) {
            // Update — zachowaj id, użyj scoped update z ClubScopedModel
            $id = (int)$existing['id'];
            unset($data['club_id'], $data['provider']);
            $this->update($id, $data);
            return $id;
        }
        return $this->insert($data);
    }

    /**
     * Decrypt-on-read dla wrażliwych pól.
     */
    private function decryptRow(array $row): array
    {
        foreach (self::ENCRYPTED_FIELDS as $f) {
            if (!empty($row[$f])) {
                try {
                    $row[$f] = Encryption::decrypt((string)$row[$f]);
                } catch (\Throwable $e) {
                    // Stary plain text lub corrupted — zostaw jak jest
                    // (admin powinien re-zapisać creds)
                    $row[$f . '_decrypt_error'] = true;
                }
            }
        }
        return $row;
    }
}
