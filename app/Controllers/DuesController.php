<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\ValidatesRequest;
use App\Models\FeeRateModel;
use App\Models\MemberFeeAssignmentModel;
use App\Models\PaymentDueModel;

/**
 * Faza P.4 — należności (dues) + auto-generator.
 *
 * Routes:
 *   GET  /fees/dues               — lista z filtrami (status, year, month, overdue)
 *   GET  /fees/dues/generate      — formularz: rok + miesiąc + dry-run
 *   POST /fees/dues/generate      — wykonaj generowanie + raport co utworzono
 *   POST /fees/dues/:id/pay       — szybkie oznaczenie jako opłacone (rejestracja payment'u)
 *   POST /fees/dues/:id/waive     — odpuść należność (status=waived)
 *   POST /fees/dues/:id/cancel    — anuluj
 *   POST /fees/dues/refresh       — refresh overdue (cron-friendly endpoint)
 *
 * Generator: dla danego (year, month):
 *   - iteruje active member_fee_assignments (gdzie valid_from <= last_day_of_month
 *     i (valid_to IS NULL OR valid_to >= first_day_of_month))
 *   - dla każdego: pobiera fee_rate i przypisane zniżki, kalkuluje net
 *   - INSERT INTO payment_dues z UNIQUE (club, member, rate, period) — duplikaty pominięte
 *
 * Pełna izolacja per klub.
 */
class DuesController extends BaseController
{
    use ValidatesRequest;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $filters = [
            'status'       => $_GET['status']       ?? null,
            'period_year'  => !empty($_GET['period_year'])  ? (int)$_GET['period_year']  : null,
            'period_month' => !empty($_GET['period_month']) ? (int)$_GET['period_month'] : null,
            'member_id'    => !empty($_GET['member_id'])    ? (int)$_GET['member_id']    : null,
            'overdue_only' => !empty($_GET['overdue_only']),
        ];
        $dues    = (new PaymentDueModel())->listForClub($filters);
        $balance = (new PaymentDueModel())->clubBalance();

        $this->render('dues/index', [
            'title'    => 'Należności',
            'dues'     => $dues,
            'balance'  => $balance,
            'statuses' => PaymentDueModel::$STATUSES,
            'filters'  => $filters,
        ]);
    }

    public function generateForm(): void
    {
        $now = new \DateTimeImmutable();
        $this->render('dues/generate', [
            'title'        => 'Generuj należności',
            'year'         => (int)$now->format('Y'),
            'month'        => (int)$now->format('n'),
        ]);
    }

    public function generate(): void
    {
        Csrf::verify();
        $back = 'fees/dues';

        $year  = $this->validateInt($_POST['period_year']  ?? '', 'period_year', 2020, 2099, $back);
        $month = $this->validateOptionalInt($_POST['period_month'] ?? null, 1, 12, $back);
        $dryRun = !empty($_POST['dry_run']);
        // Default due_date_offset_days: 14 (czyli należność wygasa 14 dni po wystawieniu)
        $offsetDays = max(0, min(60, (int)($_POST['due_date_offset_days'] ?? 14)));

        $report = $this->doGenerate($year, $month, $dryRun, $offsetDays);

        $msg = $dryRun
            ? sprintf('Symulacja: utworzono by %d nowych należności na łączną kwotę %.2f zł (pominięte: %d).',
                     $report['would_create'], $report['total_amount'], $report['skipped'])
            : sprintf('Wygenerowano %d nowych należności na łączną kwotę %.2f zł (pominięte duplikaty: %d).',
                     $report['created'], $report['total_amount'], $report['skipped']);

        Session::flash('success', $msg);
        $this->redirect($back);
    }

    public function pay(string $id): void
    {
        Csrf::verify();
        $idInt = (int)$id;
        $due = (new PaymentDueModel())->findById($idInt);
        if (!$due) {
            Session::flash('error', 'Nie znaleziono należności.');
            $this->redirect('fees/dues');
        }

        $amountRaw = $_POST['amount'] ?? '';
        $amount = is_numeric($amountRaw)
            ? (float)$amountRaw
            : ((float)$due['net_amount'] - (float)$due['paid_amount']);
        if ($amount <= 0) {
            Session::flash('error', 'Kwota wpłaty musi być > 0.');
            $this->redirect('fees/dues');
        }

        $method = in_array($_POST['method'] ?? '', ['gotowka','przelew','karta','blik','inny'], true)
                  ? $_POST['method'] : 'przelew';

        $db = Database::pdo();
        $db->beginTransaction();
        try {
            // 1. INSERT do payments
            $stmt = $db->prepare(
                "INSERT INTO payments
                 (club_id, member_id, fee_rate_id, due_id, sport_id, amount, payment_date,
                  period_year, period_month, method, reference, notes, status, created_by, created_at)
                 VALUES (?, ?, ?, ?, NULL, ?, CURDATE(), ?, ?, ?, ?, ?, 'completed', ?, NOW())"
            );
            $stmt->execute([
                $due['club_id'],
                $due['member_id'],
                $due['fee_rate_id'] ?: null,
                $idInt,
                $amount,
                $due['period_year'],
                $due['period_month'],
                $method,
                trim($_POST['reference'] ?? '') ?: null,
                trim($_POST['notes'] ?? '') ?: null,
                Auth::id(),
            ]);

            // 2. Dolicz do dues + status update
            (new PaymentDueModel())->applyPayment($idInt, $amount);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Błąd zapisu: ' . $e->getMessage());
            $this->redirect('fees/dues');
        }

        Session::flash('success', 'Wpłata zarejestrowana.');
        $this->redirect('fees/dues');
    }

    public function waive(string $id): void
    {
        Csrf::verify();
        $idInt = (int)$id;
        (new PaymentDueModel())->update($idInt, ['status' => 'waived']);
        Session::flash('success', 'Należność zwolniona.');
        $this->redirect('fees/dues');
    }

    public function cancel(string $id): void
    {
        Csrf::verify();
        $idInt = (int)$id;
        (new PaymentDueModel())->update($idInt, ['status' => 'cancelled']);
        Session::flash('success', 'Należność anulowana.');
        $this->redirect('fees/dues');
    }

    public function refresh(): void
    {
        Csrf::verify();
        $count = (new PaymentDueModel())->refreshOverdue();
        Session::flash('success', "Zaktualizowano statusy — {$count} należności oznaczono jako przeterminowane.");
        $this->redirect('fees/dues');
    }

    /**
     * Generator logic. Zwraca raport bez side-effectów gdy $dryRun=true.
     *
     * @return array{created:int, would_create:int, skipped:int, total_amount:float}
     */
    private function doGenerate(int $year, ?int $month, bool $dryRun, int $offsetDays): array
    {
        $db = Database::pdo();
        $clubId = $this->currentClub();

        // Range okresu: pierwszy/ostatni dzień
        if ($month !== null) {
            $periodFirstDay = sprintf('%04d-%02d-01', $year, $month);
            $periodLastDay  = (new \DateTime($periodFirstDay))
                ->modify('last day of this month')->format('Y-m-d');
        } else {
            // year-only = roczna należność
            $periodFirstDay = sprintf('%04d-01-01', $year);
            $periodLastDay  = sprintf('%04d-12-31', $year);
        }
        $dueDate = (new \DateTime())->modify("+{$offsetDays} days")->format('Y-m-d');

        // Active assignments overlapping period
        $stmt = $db->prepare(
            "SELECT mfa.*, fr.amount AS rate_amount, fr.period AS rate_period
             FROM member_fee_assignments mfa
             JOIN fee_rates fr ON fr.id = mfa.fee_rate_id
             WHERE mfa.club_id = ?
               AND mfa.status = 'active'
               AND mfa.valid_from <= ?
               AND (mfa.valid_to IS NULL OR mfa.valid_to >= ?)"
        );
        $stmt->execute([$clubId, $periodLastDay, $periodFirstDay]);
        $assignments = $stmt->fetchAll();

        $created      = 0;
        $skipped      = 0;
        $totalAmount  = 0.0;

        $modelDue = new PaymentDueModel();
        $assignModel = new MemberFeeAssignmentModel();

        foreach ($assignments as $a) {
            $assignmentId = (int)$a['id'];
            $rateId       = (int)$a['fee_rate_id'];
            $gross        = (float)$a['rate_amount'];

            // Pobierz zniżki dla tego assignment'u
            $discounts = $assignModel->discountsForAssignment($assignmentId);
            $calc      = MemberFeeAssignmentModel::calculateNet($gross, $discounts);

            $insertSql = "INSERT IGNORE INTO payment_dues
                          (club_id, member_id, assignment_id, fee_rate_id, period_year, period_month,
                           gross_amount, discount_amount, net_amount, paid_amount, due_date, status,
                           discount_breakdown)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, ?, 'pending', ?)";

            if ($dryRun) {
                // Sprawdź czy taki due już istnieje (UNIQUE constraint by zablokował insert)
                $check = $db->prepare(
                    "SELECT 1 FROM payment_dues
                     WHERE club_id = ? AND member_id = ?
                       AND fee_rate_id <=> ? AND period_year = ?
                       AND period_month <=> ?
                     LIMIT 1"
                );
                $check->execute([$clubId, $a['member_id'], $rateId, $year, $month]);
                if ($check->fetchColumn()) {
                    $skipped++;
                } else {
                    $created++;
                    $totalAmount += $calc['net_amount'];
                }
                continue;
            }

            // Realny insert (INSERT IGNORE pomija duplikaty)
            $ins = $db->prepare($insertSql);
            $ins->execute([
                $clubId,
                $a['member_id'],
                $assignmentId,
                $rateId,
                $year,
                $month,
                $calc['gross_amount'],
                $calc['discount_amount'],
                $calc['net_amount'],
                $dueDate,
                json_encode($calc['breakdown'], JSON_UNESCAPED_UNICODE),
            ]);
            if ($ins->rowCount() > 0) {
                $created++;
                $totalAmount += $calc['net_amount'];
            } else {
                $skipped++;
            }
        }

        return [
            'created'      => $created,
            'would_create' => $created, // gdy dry_run, created już zliczał "would create"
            'skipped'      => $skipped,
            'total_amount' => round($totalAmount, 2),
        ];
    }
}
