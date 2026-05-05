<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\MemberAuth;
use App\Helpers\PaymentGateway;
use App\Helpers\Session;
use App\Models\ClubPaymentGatewayModel;
use App\Models\FeeRateModel;
use App\Models\OnlinePaymentModel;
use App\Models\PaymentDueModel;

/**
 * Płatności online z portalu zawodnika.
 * Zawodnik wybiera składkę → system tworzy online_payment →
 * redirect do Stripe/Przelewy24 → webhook potwierdza → auto-booking.
 */
class MemberPaymentController extends BaseController
{
    public function index(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();

        $rates   = (new FeeRateModel())->withoutScope()->findAll('name');
        // Filtruj stawki per-klub
        $rates = array_filter($rates, fn($r) => (int)$r['club_id'] === $clubId && $r['is_active']);

        $pending = (new OnlinePaymentModel())->pendingForMember($memberId);
        $history = (new OnlinePaymentModel())->forMember($memberId, 1, 20);

        $this->view->setLayout('portal');
        $this->view->render('portal/payments', [
            'title'    => 'Opłać składkę online',
            'rates'    => array_values($rates),
            'pending'  => $pending,
            'history'  => $history,
            'member'   => MemberAuth::member(),
            'appName'  => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function pay(): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();

        $memberId  = (int)MemberAuth::id();
        $clubId    = (int)MemberAuth::clubId();
        $feeRateId = !empty($_POST['fee_rate_id']) ? (int)$_POST['fee_rate_id'] : null;
        $amount    = (float)($_POST['amount'] ?? 0);
        $desc      = trim($_POST['description'] ?? 'Składka klubowa');
        $year      = (int)($_POST['period_year'] ?? date('Y'));
        $month     = !empty($_POST['period_month']) ? (int)$_POST['period_month'] : null;

        if ($amount <= 0) {
            Session::flash('error', 'Podaj prawidłową kwotę.');
            $this->redirect('portal/payments');
        }

        $model = new OnlinePaymentModel();
        $opId  = $model->createPayment([
            'club_id'      => $clubId,
            'member_id'    => $memberId,
            'fee_rate_id'  => $feeRateId,
            'amount'       => $amount,
            'description'  => $desc,
            'period_year'  => $year,
            'period_month' => $month,
            'provider'     => 'stripe',
            'status'       => 'pending',
            'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        // Próba Stripe checkout
        $successUrl = url('portal/payments/success?op_id=' . $opId);
        $cancelUrl  = url('portal/payments?cancelled=1');

        $checkoutUrl = PaymentGateway::createCheckoutSession(
            $clubId, $amount, $desc, $successUrl, $cancelUrl
        );

        if ($checkoutUrl) {
            $model->update($opId, ['checkout_url' => $checkoutUrl]);
            header('Location: ' . $checkoutUrl);
            exit;
        }

        // Fallback: manual payment — oznacz jako pending z instrukcją przelewu
        $model->update($opId, ['provider' => 'manual']);
        Session::flash('info', 'Płatność online nie jest dostępna. Opłać przelewem — nr ref: online#' . $opId);
        $this->redirect('portal/payments');
    }

    public function success(): void
    {
        MemberAuth::requireLogin();
        $opId = (int)($_GET['op_id'] ?? 0);
        if ($opId > 0) {
            $model = new OnlinePaymentModel();
            $op = $model->findById($opId);
            // Stripe webhook potwierdzi — tu tylko informacja
            if ($op && $op['status'] === 'pending') {
                Session::flash('info', 'Płatność przetwarzana. Potwierdzenie przyjdzie automatycznie.');
            } elseif ($op && $op['status'] === 'paid') {
                Session::flash('success', 'Płatność zaksięgowana. Dziękujemy!');
            }
        }
        $this->redirect('portal/payments');
    }

    /**
     * Faza P.6 — opłać konkretną należność (payment_due).
     * Tworzy online_payment z due_id ref + redirect do bramki klubu (P.5).
     */
    public function payDue(string $id): void
    {
        MemberAuth::requireLogin();
        Csrf::verify();

        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $dueId    = (int)$id;

        // Pobierz due — sprawdź własność (member_id musi się zgadzać)
        $due = (new PaymentDueModel())->findById($dueId);
        if (!$due || (int)$due['member_id'] !== $memberId) {
            Session::flash('error', 'Nieprawidłowa należność.');
            $this->redirect('portal/dues');
        }
        $remaining = (float)$due['net_amount'] - (float)$due['paid_amount'];
        if ($remaining <= 0) {
            Session::flash('info', 'Należność jest już opłacona.');
            $this->redirect('portal/dues');
        }

        // Sprawdź czy klub ma aktywną bramkę
        $gateway = (new ClubPaymentGatewayModel())->activeGateway();
        if (!$gateway || $gateway['provider'] === 'manual') {
            Session::flash('info', 'Klub nie udostępnia płatności online. Wpłać przelewem na konto klubu.');
            $this->redirect('portal/dues');
        }

        // Utwórz online_payment z referencją do due
        $opModel = new OnlinePaymentModel();
        $opId    = $opModel->createPayment([
            'club_id'      => $clubId,
            'member_id'    => $memberId,
            'fee_rate_id'  => $due['fee_rate_id'] ?: null,
            'amount'       => $remaining,
            'description'  => 'Należność #' . $dueId . ' (' . ($due['period_year'] ?? '') . ')',
            'period_year'  => (int)($due['period_year'] ?? date('Y')),
            'period_month' => $due['period_month'] ? (int)$due['period_month'] : null,
            'provider'     => $gateway['provider'],
            'status'       => 'pending',
            'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $successUrl = url('portal/payments/success?op_id=' . $opId);
        $cancelUrl  = url('portal/dues?cancelled=1');

        // Stripe-only obecnie — w przyszłości routing per gateway provider
        $checkoutUrl = PaymentGateway::createCheckoutSession(
            $clubId, $remaining,
            'Należność klubowa #' . $dueId,
            $successUrl, $cancelUrl
        );

        if ($checkoutUrl) {
            $opModel->update($opId, ['checkout_url' => $checkoutUrl]);
            header('Location: ' . $checkoutUrl);
            exit;
        }

        // Fallback: manual
        $opModel->update($opId, ['provider' => 'manual']);
        Session::flash('info',
            'Płatność online nie jest aktualnie dostępna. Skontaktuj się z klubem aby ustalić sposób przelewu (ref: online#' . $opId . ').');
        $this->redirect('portal/dues');
    }
}
