<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\PlatformFeeChargeModel;
use App\Models\PlatformFeeRuleModel;
use App\Models\PlatformPaymentAccountModel;

/**
 * Super-admin: Stripe Connect accounts + platform fee rules + raport
 * zebranych platform fees (revenue ClubDesk z splits).
 */
class AdminPlatformPaymentsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    // ── Konta connected (Stripe Connect / P24 Marketplace) ─────────────

    public function accounts(): void
    {
        $db = Database::pdo();
        // Wszystkie kluby + ich konta connect (LEFT JOIN — kluby bez konta też widać).
        $clubs = $db->query(
            "SELECT c.id, c.name,
                    ppa.id AS account_id, ppa.provider, ppa.external_account_id,
                    ppa.kyc_status, ppa.charges_enabled, ppa.payouts_enabled,
                    ppa.onboarding_complete, ppa.onboarded_at
               FROM clubs c
               LEFT JOIN platform_payment_accounts ppa ON ppa.club_id = c.id
              ORDER BY c.name, ppa.provider"
        )->fetchAll();

        $this->render('admin/platform_payments/accounts', [
            'title' => 'Platform payments — konta klubów',
            'clubs' => $clubs,
        ]);
    }

    // ── Reguły platform fee ────────────────────────────────────────────

    public function rules(): void
    {
        $rules = (new PlatformFeeRuleModel())->listAll();
        $plans = Database::pdo()
            ->query("SELECT code, name FROM subscription_plans ORDER BY sort_order")
            ->fetchAll();
        $this->render('admin/platform_payments/rules', [
            'title' => 'Platform fee — reguły naliczania',
            'rules' => $rules,
            'plans' => $plans,
        ]);
    }

    public function storeRule(): void
    {
        Csrf::verify();
        $data = [
            'scope'           => $_POST['scope'] ?? 'global',
            'plan_code'       => trim((string)($_POST['plan_code'] ?? '')),
            'club_id'         => $_POST['club_id'] ?? null,
            'fee_percent'     => $_POST['fee_percent'] ?? 2.0,
            'fee_fixed_cents' => $_POST['fee_fixed_cents'] ?? 0,
            'min_fee_cents'   => $_POST['min_fee_cents'] ?? 0,
            'max_fee_cents'   => $_POST['max_fee_cents'] ?? null,
            'effective_from'  => $_POST['effective_from'] ?? date('Y-m-d'),
            'effective_until' => $_POST['effective_until'] ?? null,
            'active'          => isset($_POST['active']) ? 1 : 0,
        ];
        if (!in_array($data['scope'], ['global','plan','club_override'], true)) {
            Session::flash('error', 'Niepoprawny scope.');
            $this->redirect('admin/platform/payments/fee-rules');
        }
        (new PlatformFeeRuleModel())->create($data);
        Session::flash('success', 'Reguła utworzona.');
        $this->redirect('admin/platform/payments/fee-rules');
    }

    public function updateRule(string $id): void
    {
        Csrf::verify();
        (new PlatformFeeRuleModel())->update((int)$id, [
            'fee_percent'     => $_POST['fee_percent'] ?? 2.0,
            'fee_fixed_cents' => $_POST['fee_fixed_cents'] ?? 0,
            'min_fee_cents'   => $_POST['min_fee_cents'] ?? 0,
            'max_fee_cents'   => $_POST['max_fee_cents'] ?? null,
            'effective_from'  => $_POST['effective_from'] ?? date('Y-m-d'),
            'effective_until' => $_POST['effective_until'] ?? null,
            'active'          => isset($_POST['active']) ? 1 : 0,
        ]);
        Session::flash('success', 'Reguła zaktualizowana.');
        $this->redirect('admin/platform/payments/fee-rules');
    }

    public function deleteRule(string $id): void
    {
        Csrf::verify();
        (new PlatformFeeRuleModel())->delete((int)$id);
        Session::flash('success', 'Reguła usunięta.');
        $this->redirect('admin/platform/payments/fee-rules');
    }

    // ── Raport zebranych platform fees ─────────────────────────────────

    public function charges(): void
    {
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $clubId = !empty($_GET['club_id']) ? (int)$_GET['club_id'] : null;

        $report = (new PlatformFeeChargeModel())->report($from, $to, $clubId);

        $clubs = Database::pdo()->query("SELECT id, name FROM clubs ORDER BY name")->fetchAll();

        $this->render('admin/platform_payments/charges', [
            'title'  => 'Platform fees — raport revenue',
            'from'   => $from,
            'to'     => $to,
            'clubId' => $clubId,
            'clubs'  => $clubs,
            'report' => $report,
        ]);
    }
}
