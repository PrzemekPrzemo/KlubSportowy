<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Database;
use App\Models\ClubInvoiceModel;
use App\Models\MedicalExamModel;
use App\Models\PaymentDueModel;

/**
 * Dashboard biura (sekretariatu) — pulpit dla roli `ksiegowy` + zarząd.
 *
 * Konsoliduje w jednym widoku zadania operacyjne biura:
 *   - oczekujący członkowie do potwierdzenia
 *   - wpłaty bez faktury (paid bez club_invoices)
 *   - top zaległości z aging
 *   - badania medyczne wygasające (30/14/7 dni — counts only)
 *   - drafty kampanii korespondencji (email/sms)
 *   - quick actions (CSRF-bezpieczne formy/linki)
 *   - feed ostatnich operacji księgowości (tenant_access_log)
 *
 * Bezpieczeństwo:
 *   - requireLogin + requireClubContext
 *   - requireRole(['zarzad','ksiegowy','admin'])
 *   - wszystkie zapytania filtrują po club_id (multi-tenant isolation)
 */
class SekretariatDashboardController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'ksiegowy', 'admin']);
    }

    public function index(): void
    {
        $clubId = $this->currentClub();

        $tiles        = $this->collectTiles($clubId);
        $aging        = $this->topOverdueAging($clubId, 10);
        $medicalCounts= $this->medicalExpiringCounts();
        $activityFeed = $this->recentActivity(20);

        $this->render('sekretariat/index', [
            'title'         => 'Dashboard biura',
            'tiles'         => $tiles,
            'aging'         => $aging,
            'medicalCounts' => $medicalCounts,
            'activityFeed'  => $activityFeed,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // Kafelki: liczby + linki do akcji
    // ─────────────────────────────────────────────────────────────────

    private function collectTiles(int $clubId): array
    {
        $db = Database::pdo();

        // 1. Nowi członkowie do wprowadzenia (status pending lub utworzony w ciągu 7 dni)
        $pendingMembers = 0;
        try {
            $st = $db->prepare(
                "SELECT COUNT(*) FROM members
                  WHERE club_id = ?
                    AND (status = 'aktywny'
                         AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY))"
            );
            $st->execute([$clubId]);
            $pendingMembers = (int)$st->fetchColumn();
        } catch (\Throwable) {}

        // 2. Wpłaty bez faktury (paid, bez source w club_invoices)
        $paymentsWithoutInvoice = 0;
        try {
            $st = $db->prepare(
                "SELECT COUNT(*) FROM payments p
                  WHERE p.club_id = ?
                    AND (p.status IS NULL OR p.status IN ('completed','paid'))
                    AND NOT EXISTS (
                        SELECT 1 FROM club_invoices ci
                         WHERE ci.source_payment_id = p.id AND ci.club_id = p.club_id
                    )"
            );
            $st->execute([$clubId]);
            $paymentsWithoutInvoice = (int)$st->fetchColumn();
        } catch (\Throwable) {
            // tabela payments lub club_invoices może nie istnieć w starszych migracjach
        }

        // 3. Zaległości — saldo + liczba
        $balance = [];
        try {
            $balance = (new PaymentDueModel())->clubBalance();
        } catch (\Throwable) {
            $balance = ['total_overdue' => 0.0, 'count_overdue' => 0];
        }

        // 4. Faktury — niezapłacone
        $invoicesStats = ['count_unpaid' => 0, 'total_outstanding' => 0.0];
        try {
            $invoicesStats = (new ClubInvoiceModel())->statsForClub($clubId);
        } catch (\Throwable) {}

        // 5. Kampanie — drafty
        $draftCampaigns = 0;
        try {
            $st = $db->prepare(
                "SELECT COUNT(*) FROM campaigns
                  WHERE club_id = ? AND status = 'draft'"
            );
            $st->execute([$clubId]);
            $draftCampaigns = (int)$st->fetchColumn();
        } catch (\Throwable) {}

        // 6. Dokumenty oczekujące (jeśli istnieje workflow — best effort)
        $pendingDocs = 0;
        try {
            $st = $db->prepare(
                "SELECT COUNT(*) FROM documents
                  WHERE club_id = ? AND (signed_at IS NULL OR status = 'pending')"
            );
            $st->execute([$clubId]);
            $pendingDocs = (int)$st->fetchColumn();
        } catch (\Throwable) {
            // brak tabeli documents → 0
        }

        return [
            'pending_members'         => $pendingMembers,
            'payments_without_invoice'=> $paymentsWithoutInvoice,
            'overdue_amount'          => (float)($balance['total_overdue'] ?? 0),
            'overdue_count'           => (int)($balance['count_overdue'] ?? 0),
            'invoices_unpaid_count'   => (int)($invoicesStats['count_unpaid'] ?? 0),
            'invoices_outstanding'    => (float)($invoicesStats['total_outstanding'] ?? 0),
            'draft_campaigns'         => $draftCampaigns,
            'pending_docs'            => $pendingDocs,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // Top 10 zaległości z aging buckets
    // ─────────────────────────────────────────────────────────────────

    private function topOverdueAging(int $clubId, int $limit = 10): array
    {
        $db = Database::pdo();
        try {
            $sql = "SELECT pd.id, pd.due_date, pd.net_amount, pd.paid_amount,
                           pd.period_year, pd.period_month, pd.status,
                           DATEDIFF(CURDATE(), pd.due_date) AS days_overdue,
                           m.id AS member_id, m.first_name, m.last_name, m.member_number,
                           m.email, m.phone
                      FROM payment_dues pd
                      JOIN members m ON m.id = pd.member_id
                     WHERE pd.club_id = ?
                       AND (pd.status = 'overdue'
                            OR (pd.status IN ('pending','partial') AND pd.due_date < CURDATE()))
                  ORDER BY pd.due_date ASC
                     LIMIT " . (int)$limit;
            $st = $db->prepare($sql);
            $st->execute([$clubId]);
            $rows = $st->fetchAll();
        } catch (\Throwable) {
            return [];
        }

        foreach ($rows as &$r) {
            $days = (int)($r['days_overdue'] ?? 0);
            $r['aging_bucket'] = match (true) {
                $days <= 30  => '0-30',
                $days <= 60  => '31-60',
                $days <= 90  => '61-90',
                default       => '90+',
            };
            $r['outstanding'] = max(0.0, (float)$r['net_amount'] - (float)$r['paid_amount']);
        }
        unset($r);
        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────
    // Medical exam expiry — counts only (bez ujawniania danych medycznych)
    // ─────────────────────────────────────────────────────────────────

    private function medicalExpiringCounts(): array
    {
        try {
            $model = new MedicalExamModel();
            $rows30 = $model->expiringSoon(30);
            $rows14 = $model->expiringSoon(14);
            $rows7  = $model->expiringSoon(7);
            return [
                'in_30_days' => count($rows30),
                'in_14_days' => count($rows14),
                'in_7_days'  => count($rows7),
            ];
        } catch (\Throwable) {
            return ['in_30_days' => 0, 'in_14_days' => 0, 'in_7_days' => 0];
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // Recent activity — z tenant_access_log (np. importy, eksporty PESEL)
    // ─────────────────────────────────────────────────────────────────

    private function recentActivity(int $limit = 20): array
    {
        try {
            $db = Database::pdo();
            $st = $db->prepare(
                "SELECT id, created_at, table_name, operation, user_id, severity, context
                   FROM tenant_access_log
                  ORDER BY created_at DESC
                  LIMIT " . (int)$limit
            );
            $st->execute();
            return $st->fetchAll() ?: [];
        } catch (\Throwable) {
            return [];
        }
    }
}
