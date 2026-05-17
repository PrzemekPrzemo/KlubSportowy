<?php

namespace App\Controllers;

use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Gateway\GatewayException;
use App\Helpers\Gateway\P24MarketplaceAdapter;
use App\Helpers\Gateway\StripeConnectAdapter;
use App\Helpers\Session;
use App\Models\PlatformPaymentAccountModel;

/**
 * Klub (rola: zarzad) — onboarding i status konta merchanta dla split
 * payments (Stripe Connect Express albo — info-only — P24 Marketplace).
 *
 * Flow:
 *   1. /club/platform-payment            → status + button "Onboarding"
 *   2. POST /club/platform-payment/onboard
 *      - jeżeli brak konta → createConnectAccount() + zapis
 *      - getOnboardingLink() → redirect do Stripe
 *   3. GET /club/platform-payment/return
 *      - po powrocie z Stripe → getAccountStatus() → upsert flags
 */
class ClubPlatformPaymentsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'admin']);
    }

    private function platformStripeKey(): string
    {
        // Klucz platformy ClubDesk (NIE klucz klubu) — z env / config/app.php
        $appCfg = require ROOT_PATH . '/config/app.php';
        $k = (string)($appCfg['stripe_platform_secret'] ?? $_ENV['STRIPE_PLATFORM_SECRET'] ?? '');
        return $k;
    }

    public function index(): void
    {
        $clubId = (int)ClubContext::require();
        $m = new PlatformPaymentAccountModel();

        $stripeAccount = $m->findByClubProvider($clubId, 'stripe_connect');
        $p24Account    = $m->findByClubProvider($clubId, 'p24_marketplace');

        $this->render('club/platform_payments/index', [
            'title'            => 'Konto rozliczeń ClubDesk',
            'stripeAccount'    => $stripeAccount,
            'p24Account'       => $p24Account,
            'p24Notice'        => P24MarketplaceAdapter::PARTNERSHIP_NOTICE,
            'platformKeyReady' => $this->platformStripeKey() !== '',
        ]);
    }

    public function onboard(): void
    {
        Csrf::verify();
        $clubId = (int)ClubContext::require();

        $key = $this->platformStripeKey();
        if ($key === '') {
            Session::flash('error', 'Klucz Stripe platformy nie jest skonfigurowany w config/app.php (stripe_platform_secret).');
            $this->redirect('club/platform-payment');
        }

        $m = new PlatformPaymentAccountModel();
        $account = $m->findByClubProvider($clubId, 'stripe_connect');

        $adapter = new StripeConnectAdapter(['platform_api_key' => $key]);

        try {
            if (!$account) {
                $created = $adapter->createConnectAccount($clubId);
                $extId   = (string)($created['id'] ?? '');
                if ($extId === '') {
                    throw new GatewayException('Stripe nie zwrócił account.id');
                }
                $m->upsertAccount($clubId, 'stripe_connect', $extId);
                $account = $m->findByClubProvider($clubId, 'stripe_connect');
            }

            $returnUrl = url('club/platform-payment/return');
            $link = $adapter->getOnboardingLink((string)$account['external_account_id'], $returnUrl);
            header('Location: ' . $link);
            exit;
        } catch (GatewayException $e) {
            Session::flash('error', 'Stripe Connect: ' . $e->getMessage());
            $this->redirect('club/platform-payment');
        }
    }

    public function returnFromOnboarding(): void
    {
        $clubId = (int)ClubContext::require();
        $m = new PlatformPaymentAccountModel();
        $account = $m->findByClubProvider($clubId, 'stripe_connect');

        if (!$account) {
            Session::flash('warning', 'Brak konta Stripe Connect — uruchom onboarding.');
            $this->redirect('club/platform-payment');
        }

        $key = $this->platformStripeKey();
        if ($key === '') {
            Session::flash('error', 'Klucz Stripe platformy nie jest skonfigurowany.');
            $this->redirect('club/platform-payment');
        }

        try {
            $adapter = new StripeConnectAdapter(['platform_api_key' => $key]);
            $status = $adapter->getAccountStatus((string)$account['external_account_id']);

            $m->syncStatus(
                (int)$account['id'],
                (string)$status['kyc_status'],
                (bool)$status['charges_enabled'],
                (bool)$status['payouts_enabled'],
                (array)$status['capabilities'],
                (bool)$status['details_submitted'] && (bool)$status['charges_enabled']
            );

            Session::flash('success', 'Status konta Stripe Connect zaktualizowany.');
        } catch (GatewayException $e) {
            Session::flash('error', 'Stripe Connect status: ' . $e->getMessage());
        }
        $this->redirect('club/platform-payment');
    }
}
