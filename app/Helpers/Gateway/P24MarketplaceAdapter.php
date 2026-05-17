<?php

namespace App\Helpers\Gateway;

/**
 * Przelewy24 Marketplace Multi-Account — honest stub.
 *
 * Split payments dla Przelewy24 wymagają osobnej umowy partnerskiej
 * (Sendormeco Holding ↔ Przelewy24 / PayPro S.A.). Dopóki taka umowa nie
 * jest zawarta, NIE MOŻEMY wykonywać split per-transaction po stronie P24.
 *
 * Strategia tymczasowa (model "out-of-band fee"):
 *   - Płatności idą bezpośrednio na konto klubu (zwykły P24).
 *   - Klub rozlicza platform fee miesięczną fakturą od ClubDesk
 *     (osobny komponent billingu — `billing_invoices`).
 *
 * Po podpisaniu umowy Marketplace ten adapter zostanie zaimplementowany
 * do końca — endpointy:
 *   POST /api/v1/marketplace/account     — utworzenie sub-merchant
 *   POST /api/v1/transaction/register    — z subAccountId i platformFee
 *
 * @link https://developers.przelewy24.pl/en/index.php?en_marketplace
 */
class P24MarketplaceAdapter
{
    /** Flag zwracany przez wszystkie operacje split — UI ma czytelny komunikat. */
    public const STATUS_REQUIRES_PARTNERSHIP = 'requires_partnership';

    public const PARTNERSHIP_NOTICE =
        'Split payments dla Przelewy24 wymagają umowy partnerskiej '
        . '(Sendormeco ↔ Przelewy24). Tymczasem płatności idą bezpośrednio '
        . 'na konto klubu, a klub rozlicza platform fee fakturą od ClubDesk '
        . 'co miesiąc.';

    public function __construct(
        private readonly array $config = []
    ) {
    }

    public function isAvailable(): bool
    {
        // Można aktywować przez ENV po podpisaniu umowy.
        return (bool)($this->config['marketplace_enabled'] ?? false);
    }

    /**
     * Stub — zwraca status wymagający umowy.
     *
     * @return array{status:string, notice:string, external_account_id:?string}
     */
    public function createConnectAccount(int $clubId): array
    {
        return [
            'status'              => self::STATUS_REQUIRES_PARTNERSHIP,
            'notice'              => self::PARTNERSHIP_NOTICE,
            'external_account_id' => null,
            'club_id'             => $clubId,
        ];
    }

    public function getOnboardingLink(string $accountId, string $returnUrl): array
    {
        return [
            'status' => self::STATUS_REQUIRES_PARTNERSHIP,
            'notice' => self::PARTNERSHIP_NOTICE,
            'url'    => null,
        ];
    }

    public function getAccountStatus(string $accountId): array
    {
        return [
            'status'          => self::STATUS_REQUIRES_PARTNERSHIP,
            'notice'          => self::PARTNERSHIP_NOTICE,
            'charges_enabled' => false,
            'payouts_enabled' => false,
            'kyc_status'      => 'pending',
        ];
    }

    /**
     * Stub — sygnalizuje brak split per-transaction. Caller powinien użyć
     * standardowego Przelewy24Adapter (bez split) i rozliczyć platform fee
     * fakturą miesięczną.
     */
    public function createCheckoutWithFee(
        string $clubAccountId,
        int $amountCents,
        int $applicationFeeCents,
        string $currency,
        string $description,
        string $successUrl,
        string $cancelUrl,
        array $metadata = []
    ): array {
        return [
            'status' => self::STATUS_REQUIRES_PARTNERSHIP,
            'notice' => self::PARTNERSHIP_NOTICE,
            'redirect_url' => null,
            'session_id'   => null,
        ];
    }
}
