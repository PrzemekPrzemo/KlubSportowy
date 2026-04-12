<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;

class AdminSubscriptionsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function index(): void
    {
        $db     = Database::pdo();
        $status = trim($_GET['status'] ?? '');
        $search = trim($_GET['q'] ?? '');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $where  = [];
        $params = [];

        if ($status !== '') {
            $where[]  = 'cs.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $where[]  = 'c.name LIKE ?';
            $params[] = '%' . $search . '%';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) FROM club_subscriptions cs
                     JOIN clubs c ON c.id = cs.club_id
                     {$whereSql}";
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT cs.*, sp.name AS plan_name, sp.code AS plan_code,
                       sp.max_members AS plan_max_members, sp.max_sports AS plan_max_sports,
                       sp.price_monthly, c.name AS club_name, c.id AS club_id,
                       (SELECT COUNT(*) FROM members m WHERE m.club_id = c.id AND m.status = 'aktywny') AS member_count,
                       (SELECT COUNT(*) FROM club_sports csp WHERE csp.club_id = c.id AND csp.is_active = 1) AS sport_count
                FROM club_subscriptions cs
                JOIN subscription_plans sp ON sp.id = cs.plan_id
                JOIN clubs c ON c.id = cs.club_id
                {$whereSql}
                ORDER BY c.name
                LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $plans = $db->query("SELECT id, name, code FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order")->fetchAll();

        $this->render('admin/subscriptions/index', [
            'title'      => 'Subskrypcje klubów',
            'rows'       => $rows,
            'plans'      => $plans,
            'status'     => $status,
            'q'          => $search,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'lastPage'   => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    public function extend(string $id): void
    {
        Csrf::verify();
        $days = max(1, (int)($_POST['days'] ?? 30));
        $db   = Database::pdo();
        $stmt = $db->prepare(
            "UPDATE club_subscriptions SET valid_until = DATE_ADD(valid_until, INTERVAL ? DAY) WHERE id = ?"
        );
        $stmt->execute([$days, (int)$id]);
        Session::flash('success', "Subskrypcja przedłużona o {$days} dni.");
        $this->redirect('admin/subscriptions');
    }

    public function changePlan(string $id): void
    {
        Csrf::verify();
        $planId = (int)($_POST['plan_id'] ?? 0);
        if ($planId <= 0) {
            Session::flash('error', 'Nieprawidłowy plan.');
            $this->redirect('admin/subscriptions');
        }
        $db   = Database::pdo();
        $stmt = $db->prepare("UPDATE club_subscriptions SET plan_id = ? WHERE id = ?");
        $stmt->execute([$planId, (int)$id]);
        Session::flash('success', 'Plan zmieniony.');
        $this->redirect('admin/subscriptions');
    }

    public function suspend(string $id): void
    {
        Csrf::verify();
        $db   = Database::pdo();
        $stmt = $db->prepare("UPDATE club_subscriptions SET status = 'suspended' WHERE id = ?");
        $stmt->execute([(int)$id]);
        Session::flash('success', 'Subskrypcja zawieszona.');
        $this->redirect('admin/subscriptions');
    }

    public function activate(string $id): void
    {
        Csrf::verify();
        $db   = Database::pdo();
        $stmt = $db->prepare("UPDATE club_subscriptions SET status = 'active' WHERE id = ?");
        $stmt->execute([(int)$id]);
        Session::flash('success', 'Subskrypcja aktywowana.');
        $this->redirect('admin/subscriptions');
    }

    public function override(string $id): void
    {
        Csrf::verify();
        $maxMembers = trim($_POST['max_members_override'] ?? '') !== '' ? (int)$_POST['max_members_override'] : null;
        $maxSports  = trim($_POST['max_sports_override'] ?? '') !== '' ? (int)$_POST['max_sports_override'] : null;
        $db   = Database::pdo();
        $stmt = $db->prepare(
            "UPDATE club_subscriptions SET max_members_override = ?, max_sports_override = ? WHERE id = ?"
        );
        $stmt->execute([$maxMembers, $maxSports, (int)$id]);
        Session::flash('success', 'Nadpisania limitów zapisane.');
        $this->redirect('admin/subscriptions');
    }

    public function revenue(): void
    {
        $db = Database::pdo();

        // MRR = sum price_monthly of active subscriptions
        $mrr = (float)$db->query(
            "SELECT COALESCE(SUM(sp.price_monthly), 0)
             FROM club_subscriptions cs
             JOIN subscription_plans sp ON sp.id = cs.plan_id
             WHERE cs.status = 'active'"
        )->fetchColumn();

        $arr = $mrr * 12;

        $activeClubs = (int)$db->query(
            "SELECT COUNT(*) FROM club_subscriptions WHERE status = 'active'"
        )->fetchColumn();

        $totalClubs = (int)$db->query(
            "SELECT COUNT(*) FROM club_subscriptions"
        )->fetchColumn();

        $churnRate = $totalClubs > 0
            ? round((($totalClubs - $activeClubs) / $totalClubs) * 100, 1)
            : 0;

        // Revenue per month (last 12 months from payments)
        $revenuePerMonth = $db->query(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month, SUM(amount) AS total
             FROM payments
             WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY month ORDER BY month"
        )->fetchAll();

        $this->render('admin/subscriptions/revenue', [
            'title'          => 'Przychody',
            'mrr'            => $mrr,
            'arr'            => $arr,
            'activeClubs'    => $activeClubs,
            'churnRate'      => $churnRate,
            'revenuePerMonth' => $revenuePerMonth,
        ]);
    }
}
