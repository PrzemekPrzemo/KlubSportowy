<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\PaymentDueModel;

/**
 * Faza P.4 — Księgowość: rejestr wszystkich wpłat z filtrami + CSV export.
 *
 * Routes:
 *   GET /accounting          — rejestr wpłat z filtrami
 *   GET /accounting/export   — CSV z aktualnymi filtrami
 *
 * Pełna izolacja per klub (manualne WHERE club_id w SQL).
 */
class AccountingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $filters = $this->parseFilters();
        $rows    = $this->fetchPayments($filters);
        $totals  = $this->calculateTotals($rows);
        $balance = (new PaymentDueModel())->clubBalance();

        $this->render('accounting/index', [
            'title'   => 'Księgowość',
            'rows'    => $rows,
            'totals'  => $totals,
            'balance' => $balance,
            'filters' => $filters,
        ]);
    }

    public function exportCsv(): void
    {
        $filters = $this->parseFilters();
        $rows = $this->fetchPayments($filters);

        $filename = 'wplaty_' . date('Y-m-d_Hi') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        // BOM dla Excel UTF-8
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'Data wpłaty', 'Zawodnik', 'Numer', 'Sport', 'Stawka',
            'Okres', 'Kwota', 'Metoda', 'Referencja', 'Status', 'Zaksięgował'
        ], ';');

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['payment_date'],
                ($r['last_name'] ?? '') . ' ' . ($r['first_name'] ?? ''),
                $r['member_number'] ?? '',
                $r['sport_name'] ?? '',
                $r['rate_name'] ?? '',
                $r['period_year'] . (!empty($r['period_month']) ? '-' . str_pad((string)$r['period_month'], 2, '0', STR_PAD_LEFT) : ''),
                number_format((float)$r['amount'], 2, ',', ''),
                $r['method'],
                $r['reference'] ?? '',
                $r['status'] ?? 'completed',
                $r['created_by_name'] ?? '',
            ], ';');
        }
        fclose($out);
        exit;
    }

    private function parseFilters(): array
    {
        return [
            'year'      => !empty($_GET['year'])      ? (int)$_GET['year']      : (int)date('Y'),
            'month'     => !empty($_GET['month'])     ? (int)$_GET['month']     : null,
            'member_id' => !empty($_GET['member_id']) ? (int)$_GET['member_id'] : null,
            'method'    => !empty($_GET['method'])    ? $_GET['method']         : null,
            'status'    => !empty($_GET['status'])    ? $_GET['status']         : null,
        ];
    }

    private function fetchPayments(array $f): array
    {
        $clubId = $this->currentClub();
        $sql = "SELECT p.*,
                       m.first_name, m.last_name, m.member_number,
                       s.name AS sport_name,
                       fr.name AS rate_name,
                       u.username AS created_by_name
                FROM payments p
                LEFT JOIN members m   ON m.id = p.member_id
                LEFT JOIN sports s    ON s.id = p.sport_id
                LEFT JOIN fee_rates fr ON fr.id = p.fee_rate_id
                LEFT JOIN users u     ON u.id = p.created_by
                WHERE p.club_id = ?";
        $params = [$clubId];

        if ($f['year'])      { $sql .= " AND p.period_year = ?";  $params[] = $f['year']; }
        if ($f['month'])     { $sql .= " AND p.period_month = ?"; $params[] = $f['month']; }
        if ($f['member_id']) { $sql .= " AND p.member_id = ?";    $params[] = $f['member_id']; }
        if ($f['method'])    { $sql .= " AND p.method = ?";       $params[] = $f['method']; }
        if ($f['status'])    { $sql .= " AND p.status = ?";       $params[] = $f['status']; }

        $sql .= " ORDER BY p.payment_date DESC, p.id DESC";

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function calculateTotals(array $rows): array
    {
        $totalAmount = 0.0;
        $byMethod = [];
        $byMonth  = [];
        foreach ($rows as $r) {
            if (($r['status'] ?? 'completed') === 'refund') continue; // nie licz refundów do przychodu
            $amt = (float)$r['amount'];
            $totalAmount += $amt;
            $byMethod[$r['method']] = ($byMethod[$r['method']] ?? 0) + $amt;
            $monthKey = $r['period_year'] . '-' . str_pad((string)($r['period_month'] ?? 0), 2, '0', STR_PAD_LEFT);
            $byMonth[$monthKey] = ($byMonth[$monthKey] ?? 0) + $amt;
        }
        return [
            'total'    => $totalAmount,
            'count'    => count($rows),
            'byMethod' => $byMethod,
            'byMonth'  => $byMonth,
        ];
    }
}
