<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\ClubModel;
use App\Models\SubscriptionModel;

class BillingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad']);
    }

    public function plans(): void
    {
        $plans       = (new SubscriptionModel())->listPlans();
        $currentSub  = (new SubscriptionModel())->findForClub($this->currentClub());
        $this->render('billing/plans', [
            'title'      => 'Zmiana planu',
            'plans'      => $plans,
            'currentSub' => $currentSub,
        ]);
    }

    public function upgrade(): void
    {
        Csrf::verify();
        $planId = (int)($_POST['plan_id'] ?? 0);
        $cycle  = in_array($_POST['billing_cycle'] ?? '', ['monthly','yearly'], true) ? $_POST['billing_cycle'] : 'monthly';
        if ($planId <= 0) {
            Session::flash('error', 'Wybierz plan.');
            $this->redirect('billing/plans');
        }

        $db     = Database::pdo();
        $clubId = $this->currentClub();

        // Pobierz plan
        $stmt = $db->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch();
        if (!$plan) {
            Session::flash('error', 'Plan nie istnieje.');
            $this->redirect('billing/plans');
        }

        $price = $cycle === 'yearly' ? (float)$plan['price_yearly'] : (float)$plan['price_monthly'];
        $validUntil = $cycle === 'yearly'
            ? date('Y-m-d', strtotime('+1 year'))
            : date('Y-m-d', strtotime('+1 month'));

        $db->beginTransaction();
        try {
            // Upsert subskrypcji
            $stmt = $db->prepare(
                "INSERT INTO club_subscriptions (club_id, plan_id, valid_until, status, billing_cycle)
                 VALUES (?, ?, ?, 'active', ?)
                 ON DUPLICATE KEY UPDATE plan_id = VALUES(plan_id), valid_until = VALUES(valid_until),
                 status = 'active', billing_cycle = VALUES(billing_cycle)"
            );
            $stmt->execute([$clubId, $planId, $validUntil, $cycle]);

            // Generuj fakturę
            $invoiceNo = 'INV-' . $clubId . '-' . date('Ymd') . '-' . str_pad((string)random_int(1, 999), 3, '0', STR_PAD_LEFT);
            $stmt = $db->prepare(
                "INSERT INTO billing_invoices (club_id, number, issue_date, due_date, total, status)
                 VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), ?, 'issued')"
            );
            $stmt->execute([$clubId, $invoiceNo, $price]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Błąd: ' . $e->getMessage());
            $this->redirect('billing/plans');
        }

        Session::flash('success', 'Plan zmieniony na: ' . $plan['name'] . '. Faktura: ' . $invoiceNo);
        $this->redirect('billing/invoices');
    }

    public function invoices(): void
    {
        $clubId = $this->currentClub();
        $db     = Database::pdo();
        $stmt   = $db->prepare(
            "SELECT * FROM billing_invoices WHERE club_id = ? ORDER BY issue_date DESC"
        );
        $stmt->execute([$clubId]);
        $invoices = $stmt->fetchAll();

        $this->render('billing/invoices', [
            'title'    => 'Faktury',
            'invoices' => $invoices,
        ]);
    }

    public function markPaid(string $id): void
    {
        Csrf::verify();
        $db = Database::pdo();
        $db->prepare("UPDATE billing_invoices SET status = 'paid', paid_at = NOW() WHERE id = ? AND club_id = ?")
           ->execute([(int)$id, $this->currentClub()]);
        Session::flash('success', 'Faktura oznaczona jako opłacona.');
        $this->redirect('billing/invoices');
    }
}
