<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Gateway\GatewayException;
use App\Helpers\Gateway\SubscriptionService;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Models\ClubPaymentGatewayModel;
use App\Models\FeeRateModel;
use App\Models\MemberSubscriptionModel;

/**
 * Member-facing kontroler subskrypcji cyklicznych składek.
 *
 * Multi-tenant safety: każda akcja weryfikuje że:
 *   - subscription.member_id == MemberAuth::id()
 *   - subscription.club_id   == MemberAuth::clubId()
 */
class MemberSubscriptionsController extends BaseController
{
    public function index(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();

        $subs = (new MemberSubscriptionModel())->forMember($memberId);

        $this->view->setLayout('portal');
        $this->view->render('portal/subscriptions/index', [
            'title'    => 'Subskrypcje cykliczne',
            'subs'     => $subs,
            'statuses' => MemberSubscriptionModel::STATUSES,
            'periods'  => MemberSubscriptionModel::PERIODS,
            'member'   => MemberAuth::member(),
            'appName'  => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /**
     * GET — wybór provider + billing_period dla danego fee_rate.
     */
    public function setupForm(string $feeRateId): void
    {
        MemberAuth::requireLogin();
        $clubId = (int)MemberAuth::clubId();
        $feeId  = (int)$feeRateId;

        $feeRate = (new FeeRateModel())->withoutScope()->findById($feeId);
        if (!$feeRate || (int)$feeRate['club_id'] !== $clubId || empty($feeRate['is_active'])) {
            Session::flash('error', 'Nieprawidłowa opłata.');
            $this->redirect('portal/subscriptions');
        }

        $gateways = (new ClubPaymentGatewayModel())->listForClub();
        $activeProviders = array_values(array_filter(array_map(
            fn($g) => ($g['is_active'] && in_array($g['provider'], ['stripe', 'przelewy24'], true)) ? $g['provider'] : null,
            $gateways
        )));

        $this->view->setLayout('portal');
        $this->view->render('portal/subscriptions/setup', [
            'title'           => 'Skonfiguruj subskrypcję',
            'feeRate'         => $feeRate,
            'activeProviders' => $activeProviders,
            'periods'         => MemberSubscriptionModel::PERIODS,
            'member'          => MemberAuth::member(),
            'appName'         => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /**
     * POST — utwórz subskrypcję + redirect do Stripe/P24 checkout.
     */
    public function setupSubmit(string $feeRateId): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();

        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $feeId    = (int)$feeRateId;

        $provider      = (string)($_POST['provider'] ?? 'stripe');
        $billingPeriod = (string)($_POST['billing_period'] ?? 'monthly');

        if (!in_array($provider, ['stripe', 'przelewy24'], true)) {
            Session::flash('error', 'Nieobsługiwany dostawca.');
            $this->redirect('portal/subscriptions/setup/' . $feeId);
        }

        try {
            $successUrl = url('portal/subscriptions/return');
            $cancelUrl  = url('portal/subscriptions?cancelled=1');
            $result = SubscriptionService::createSetup(
                clubId:        $clubId,
                memberId:      $memberId,
                feeRateId:     $feeId,
                provider:      $provider,
                billingPeriod: $billingPeriod,
                successUrl:    $successUrl,
                cancelUrl:     $cancelUrl,
                customerEmail: MemberAuth::member()['email'] ?? null,
            );
            header('Location: ' . $result['redirect_url']);
            exit;
        } catch (GatewayException $e) {
            error_log('Subscription setup failed: ' . $e->getMessage());
            Session::flash('error', 'Nie udało się rozpocząć konfiguracji: ' . $e->getMessage());
            $this->redirect('portal/subscriptions/setup/' . $feeId);
        }
    }

    /**
     * Return URL z Stripe Checkout — zawiera session_id={CHECKOUT_SESSION_ID}.
     * Wyciągamy session, retrievujemy subscription i pokazujemy success.
     */
    public function returnFromCheckout(): void
    {
        MemberAuth::requireLogin();
        $clubId    = (int)MemberAuth::clubId();
        $sessionId = (string)($_GET['session_id'] ?? '');

        $sub = null;
        if ($sessionId !== '') {
            try {
                $sub = SubscriptionService::handleStripeReturn($clubId, $sessionId);
            } catch (\Throwable $e) {
                error_log('returnFromCheckout error: ' . $e->getMessage());
            }
        }

        $this->view->setLayout('portal');
        $this->view->render('portal/subscriptions/success', [
            'title'   => 'Subskrypcja skonfigurowana',
            'sub'     => $sub,
            'member'  => MemberAuth::member(),
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function cancel(string $id): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();

        $sub = $this->loadOwnSubscription((int)$id);
        try {
            SubscriptionService::cancel($sub, atPeriodEnd: true, reason: 'Cancelled by member');
            Session::flash('success', 'Subskrypcja zostanie anulowana z końcem bieżącego okresu.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Anulowanie nie powiodło się: ' . $e->getMessage());
        }
        $this->redirect('portal/subscriptions');
    }

    public function pause(string $id): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();

        $sub = $this->loadOwnSubscription((int)$id);
        try {
            SubscriptionService::pause($sub);
            Session::flash('success', 'Subskrypcja została wstrzymana.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Wstrzymanie nie powiodło się: ' . $e->getMessage());
        }
        $this->redirect('portal/subscriptions');
    }

    public function resume(string $id): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();

        $sub = $this->loadOwnSubscription((int)$id);
        try {
            SubscriptionService::resume($sub);
            Session::flash('success', 'Subskrypcja została wznowiona.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Wznowienie nie powiodło się: ' . $e->getMessage());
        }
        $this->redirect('portal/subscriptions');
    }

    /**
     * Weryfikuje że subskrypcja należy do zalogowanego membera w jego klubie.
     */
    private function loadOwnSubscription(int $id): array
    {
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $sub = (new MemberSubscriptionModel())->findById($id);
        if (!$sub || (int)$sub['member_id'] !== $memberId || (int)$sub['club_id'] !== $clubId) {
            http_response_code(403);
            echo 'Brak dostępu do tej subskrypcji.';
            exit;
        }
        return $sub;
    }
}
