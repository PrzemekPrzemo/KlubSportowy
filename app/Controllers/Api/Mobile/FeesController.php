<?php

namespace App\Controllers\Api\Mobile;

use App\Helpers\Database;
use App\Helpers\PaymentGateway;
use App\Models\OnlinePaymentModel;
use App\Models\PaymentDueModel;

/**
 * Mobile API v1 — fees / dues.
 * Re-uses PaymentDueModel (data) and PaymentGateway::createCheckoutSession()
 * (existing MemberPaymentController flow).
 */
class FeesController extends V1Controller
{
    /**
     * GET /api/mobile/v1/fees?status=pending|overdue|paid&page=N
     * Returns all dues for the current member (paginated client-side from full list).
     */
    public function index(): void
    {
        $this->requireAuth();
        $status  = $_GET['status'] ?? null;
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        $allDues = (new PaymentDueModel())->forMember($this->memberId, $status ?: null);
        $today   = date('Y-m-d');

        $shaped = array_map(fn($d) => $this->shapeDue($d, $today), $allDues);

        $total = count($shaped);
        $slice = array_slice($shaped, ($page - 1) * $perPage, $perPage);

        $this->paginated([
            'data'         => $slice,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int)ceil($total / $perPage)),
        ]);
    }

    /** GET /api/mobile/v1/fees/:id */
    public function show(string $id): void
    {
        $this->requireAuth();
        $dueId = (int)$id;
        $row = $this->fetchDueForMember($dueId);
        if ($row === null) {
            $this->error('Należność nie istnieje lub nie należy do Ciebie.', 404, 'not_found');
        }
        $this->json($this->shapeDue($row, date('Y-m-d')));
    }

    /**
     * POST /api/mobile/v1/fees/:id/checkout
     * Returns { redirect_url, payment_id } for the mobile app to open in WebView.
     * Re-uses PaymentGateway::createCheckoutSession from MemberPaymentController flow.
     */
    public function checkout(string $id): void
    {
        $this->requireAuth();
        $dueId = (int)$id;
        $row   = $this->fetchDueForMember($dueId);
        if ($row === null) {
            $this->error('Należność nie istnieje lub nie należy do Ciebie.', 404, 'not_found');
        }
        if (in_array($row['status'], ['paid', 'cancelled'], true)) {
            $this->error('Ta należność nie wymaga już opłaty.', 409, 'already_paid');
        }

        $amount = (float)$row['net_amount'] - (float)$row['paid_amount'];
        if ($amount <= 0) {
            $this->error('Pozostała kwota wynosi zero.', 409, 'nothing_to_pay');
        }

        $desc = trim(($row['rate_name'] ?? 'Składka klubowa') . ' #' . $dueId);

        $opModel = new OnlinePaymentModel();
        $opId = $opModel->createPayment([
            'club_id'      => $this->clubId,
            'member_id'    => $this->memberId,
            'fee_rate_id'  => $row['fee_rate_id'] ?? null,
            'amount'       => $amount,
            'description'  => $desc,
            'period_year'  => (int)($row['period_year'] ?? date('Y')),
            'period_month' => $row['period_month'] ?? null,
            'provider'     => 'stripe',
            'status'       => 'pending',
            'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);

        $successUrl = url('portal/payments/success?op_id=' . $opId);
        $cancelUrl  = url('portal/payments?cancelled=1');

        $checkoutUrl = PaymentGateway::createCheckoutSession(
            $this->clubId, $amount, $desc, $successUrl, $cancelUrl
        );

        if (!$checkoutUrl) {
            $this->error('Bramka płatności nie jest dostępna dla tego klubu.', 503, 'gateway_unavailable');
        }
        $opModel->update($opId, ['checkout_url' => $checkoutUrl]);

        $this->json([
            'payment_id'    => $opId,
            'redirect_url'  => $checkoutUrl,
            'amount'        => round($amount, 2),
            'description'   => $desc,
        ]);
    }

    // --------- helpers ---------

    private function fetchDueForMember(int $dueId): ?array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT pd.*, fr.name AS rate_name, fr.fee_type AS rate_fee_type
             FROM payment_dues pd
             LEFT JOIN fee_rates fr ON fr.id = pd.fee_rate_id
             WHERE pd.id = ? AND pd.member_id = ? AND pd.club_id = ?
             LIMIT 1"
        );
        $stmt->execute([$dueId, $this->memberId, $this->clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function shapeDue(array $d, string $today): array
    {
        $remaining = (float)$d['net_amount'] - (float)$d['paid_amount'];
        $isOverdue = $d['status'] === 'overdue'
            || (in_array($d['status'], ['pending', 'partial'], true) && ($d['due_date'] ?? '') < $today);
        return [
            'id'             => (int)$d['id'],
            'rate_name'      => $d['rate_name'] ?? null,
            'fee_type'       => $d['rate_fee_type'] ?? null,
            'gross_amount'   => (float)($d['gross_amount'] ?? $d['net_amount']),
            'net_amount'     => (float)$d['net_amount'],
            'paid_amount'    => (float)$d['paid_amount'],
            'remaining'      => round($remaining, 2),
            'currency'       => $d['currency'] ?? 'PLN',
            'due_date'       => $d['due_date'] ?? null,
            'period_year'    => isset($d['period_year']) ? (int)$d['period_year'] : null,
            'period_month'   => isset($d['period_month']) ? (int)$d['period_month'] : null,
            'status'         => $d['status'],
            'is_overdue'     => $isOverdue,
        ];
    }
}
