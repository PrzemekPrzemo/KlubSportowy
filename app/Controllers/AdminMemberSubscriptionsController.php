<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Gateway\SubscriptionService;
use App\Helpers\Session;
use App\Models\MemberSubscriptionModel;
use App\Models\SubscriptionChargeModel;

/**
 * Admin kontroler subskrypcji cyklicznych składek.
 * Lista wszystkich subskrypcji w klubie + detail z timeline chargeów +
 * force-charge (manualne triggerowanie retry).
 *
 * Permission: wymaga modułu "fees" (lub super admin).
 */
class AdminMemberSubscriptionsController extends BaseController
{
    public function index(): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireModule('fees');

        $status = isset($_GET['status']) ? (string)$_GET['status'] : null;

        $model = new MemberSubscriptionModel();
        $subs  = $model->listForClub($status);

        // Agregaty per status
        $counts = ['all' => count($subs)];
        foreach (array_keys(MemberSubscriptionModel::STATUSES) as $st) {
            $counts[$st] = 0;
        }
        $allSubs = $status ? $model->listForClub(null) : $subs;
        foreach ($allSubs as $s) {
            $counts[$s['status']] = ($counts[$s['status']] ?? 0) + 1;
        }
        $counts['all'] = count($allSubs);

        $this->render('admin/member_subscriptions/index', [
            'title'    => 'Subskrypcje cykliczne składek',
            'subs'     => $subs,
            'statuses' => MemberSubscriptionModel::STATUSES,
            'periods'  => MemberSubscriptionModel::PERIODS,
            'counts'   => $counts,
            'status'   => $status,
        ]);
    }

    public function show(string $id): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireModule('fees');

        $sub = $this->loadClubSubscription((int)$id);
        $charges = (new SubscriptionChargeModel())->forSubscription((int)$sub['id']);

        $this->render('admin/member_subscriptions/show', [
            'title'    => 'Subskrypcja #' . $sub['id'],
            'sub'      => $sub,
            'charges'  => $charges,
            'statuses' => MemberSubscriptionModel::STATUSES,
            'periods'  => MemberSubscriptionModel::PERIODS,
        ]);
    }

    public function forceCharge(string $id): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireModule('fees');
        Csrf::verify();

        $sub = $this->loadClubSubscription((int)$id);
        try {
            $r = SubscriptionService::forceCharge($sub);
            $ok = ($r['paid'] ?? null) === true || ($r['status'] ?? null) === 'success';
            Session::flash($ok ? 'success' : 'warning',
                $ok ? 'Charge wykonany pomyślnie.' : 'Charge zakończony niepowodzeniem — sprawdź szczegóły.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Force-charge nie powiódł się: ' . $e->getMessage());
        }
        $this->redirect('admin/member-subscriptions/' . $sub['id']);
    }

    public function cancel(string $id): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireModule('fees');
        Csrf::verify();

        $sub = $this->loadClubSubscription((int)$id);
        $atPeriodEnd = !empty($_POST['at_period_end']);
        $reason = trim((string)($_POST['reason'] ?? 'admin cancel'));
        try {
            SubscriptionService::cancel($sub, atPeriodEnd: $atPeriodEnd, reason: $reason);
            Session::flash('success', 'Subskrypcja anulowana.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Anulowanie nie powiodło się: ' . $e->getMessage());
        }
        $this->redirect('admin/member-subscriptions/' . $sub['id']);
    }

    private function loadClubSubscription(int $id): array
    {
        $clubId = (int)ClubContext::require();
        $sub = (new MemberSubscriptionModel())->findById($id);
        if (!$sub) {
            http_response_code(404);
            echo 'Subskrypcja nie znaleziona.';
            exit;
        }
        if ((int)$sub['club_id'] !== $clubId && !Auth::isSuperAdmin()) {
            http_response_code(403);
            echo 'Brak dostępu do tej subskrypcji.';
            exit;
        }
        return $sub;
    }
}
