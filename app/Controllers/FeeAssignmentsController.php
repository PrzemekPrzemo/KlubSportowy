<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\MemberFilter;
use App\Helpers\RateLimiter;
use App\Helpers\Session;
use App\Helpers\ValidatesRequest;
use App\Models\FeeDiscountModel;
use App\Models\FeeRateModel;
use App\Models\MemberFeeAssignmentModel;
use App\Models\MemberModel;
use App\Models\SportModel;

/**
 * Faza P.3 — przypisanie polityki opłat (fee_rate) do zawodnika
 * z opcjonalnymi zniżkami (M:N).
 *
 * Logika:
 *   1. Admin wybiera członka + fee_rate + ewentualnie 1..N zniżek
 *   2. Kliknięcie "Zapisz" → INSERT do member_fee_assignments
 *      + INSERT do member_fee_assignment_discounts (M:N)
 *   3. Net amount kalkulowany dynamicznie przy generowaniu należności (P.4)
 *
 * Pełna izolacja per klub przez ClubScopedModel.
 */
class FeeAssignmentsController extends BaseController
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
        $statusFilter = $_GET['status'] ?? null;
        $assignments  = (new MemberFeeAssignmentModel())->listForClub(null, $statusFilter);

        $this->render('assignments/index', [
            'title'        => 'Subskrypcje opłat',
            'assignments'  => $assignments,
            'statuses'     => MemberFeeAssignmentModel::$STATUSES,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function create(): void
    {
        $members  = (new MemberModel())->search('', 'aktywny', null, 1, 1000)['data'] ?? [];
        $rates    = (new FeeRateModel())->listForClub(null, true); // tylko aktywne
        $today    = date('Y-m-d');
        $discounts = (new FeeDiscountModel())->activeOnDate($today);

        $this->render('assignments/edit', [
            'title'      => 'Nowa subskrypcja opłat',
            'assignment' => null,
            'attached'   => [],
            'members'    => $members,
            'rates'      => $rates,
            'discounts'  => $discounts,
            'statuses'   => MemberFeeAssignmentModel::$STATUSES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $back = 'fees/assignments';

        $memberId   = $this->validateInt($_POST['member_id'] ?? '', 'member_id', 1, null, $back);
        $feeRateId  = $this->validateInt($_POST['fee_rate_id'] ?? '', 'fee_rate_id', 1, null, $back);
        $validFrom  = $this->validateOptionalDate($_POST['valid_from'] ?? null) ?? date('Y-m-d');
        $validTo    = $this->validateOptionalDate($_POST['valid_to'] ?? null);
        $status     = $this->validateInList(
            $_POST['status'] ?? 'active',
            MemberFeeAssignmentModel::$STATUSES,
            'status',
            $back
        );

        // Walidacja: fee_rate i member istnieją w klubie
        $rate   = (new FeeRateModel())->findById($feeRateId);
        $member = (new MemberModel())->findById($memberId);
        if (!$rate || !$member) {
            Session::flash('error', 'Nieprawidłowy zawodnik lub stawka.');
            $this->redirect($back);
        }

        $model = new MemberFeeAssignmentModel();
        $assignmentId = $model->insert([
            'member_id'   => $memberId,
            'fee_rate_id' => $feeRateId,
            'valid_from'  => $validFrom,
            'valid_to'    => $validTo,
            'status'      => $status,
            'notes'       => $this->validateOptionalString($_POST['notes'] ?? null, 5000, $back),
            'created_by'  => Auth::id(),
        ]);

        // M:N — dołącz wybrane zniżki
        $discountIds = array_unique(array_map('intval', (array)($_POST['discount_ids'] ?? [])));
        foreach ($discountIds as $did) {
            if ($did > 0) {
                $model->attachDiscount($assignmentId, $did, Auth::id());
            }
        }

        Session::flash('success', 'Subskrypcja utworzona.');
        $this->redirect('fees/assignments/' . $assignmentId . '/edit');
    }

    public function edit(string $id): void
    {
        $idInt = (int)$id;
        $model = new MemberFeeAssignmentModel();
        $assignment = $model->findById($idInt);
        if (!$assignment) {
            Session::flash('error', 'Nie znaleziono subskrypcji.');
            $this->redirect('fees/assignments');
        }

        // Pre-fill: lista wszystkich aktywnych zniżek + zaznaczone te dołączone
        $today      = date('Y-m-d');
        $discounts  = (new FeeDiscountModel())->activeOnDate($today);
        $attached   = $model->discountsForAssignment($idInt);
        $attachedIds = array_column($attached, 'id');

        $members = (new MemberModel())->search('', 'aktywny', null, 1, 1000)['data'] ?? [];
        $rates   = (new FeeRateModel())->listForClub(null, true);

        // Preview kalkulacji: gross + zniżki = net
        $rate = (new FeeRateModel())->findById((int)$assignment['fee_rate_id']);
        $preview = MemberFeeAssignmentModel::calculateNet(
            (float)($rate['amount'] ?? 0),
            $attached
        );

        $this->render('assignments/edit', [
            'title'        => 'Edytuj subskrypcję opłat',
            'assignment'   => $assignment,
            'attached'     => $attached,
            'attachedIds'  => $attachedIds,
            'members'      => $members,
            'rates'        => $rates,
            'discounts'    => $discounts,
            'statuses'     => MemberFeeAssignmentModel::$STATUSES,
            'preview'      => $preview,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $back = 'fees/assignments';
        $idInt = (int)$id;

        $model = new MemberFeeAssignmentModel();
        $existing = $model->findById($idInt);
        if (!$existing) {
            Session::flash('error', 'Nie znaleziono subskrypcji.');
            $this->redirect($back);
        }

        $validFrom = $this->validateOptionalDate($_POST['valid_from'] ?? null) ?? $existing['valid_from'];
        $validTo   = $this->validateOptionalDate($_POST['valid_to'] ?? null);
        $status    = $this->validateInList(
            $_POST['status'] ?? 'active',
            MemberFeeAssignmentModel::$STATUSES,
            'status',
            $back
        );
        $feeRateId = $this->validateInt($_POST['fee_rate_id'] ?? '', 'fee_rate_id', 1, null, $back);

        $model->update($idInt, [
            'fee_rate_id' => $feeRateId,
            'valid_from'  => $validFrom,
            'valid_to'    => $validTo,
            'status'      => $status,
            'notes'       => $this->validateOptionalString($_POST['notes'] ?? null, 5000, $back),
        ]);

        // Sync discounts (M:N): full overwrite — najpierw odepnij wszystkie,
        // potem zaczep wybrane. Defensywnie idempotentne.
        $currentAttached = $model->discountsForAssignment($idInt);
        foreach ($currentAttached as $d) {
            $model->detachDiscount($idInt, (int)$d['id']);
        }
        $newDiscountIds = array_unique(array_map('intval', (array)($_POST['discount_ids'] ?? [])));
        foreach ($newDiscountIds as $did) {
            if ($did > 0) {
                $model->attachDiscount($idInt, $did, Auth::id());
            }
        }

        Session::flash('success', 'Subskrypcja zaktualizowana.');
        $this->redirect($back);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new MemberFeeAssignmentModel())->delete((int)$id);
        Session::flash('success', 'Subskrypcja usunięta.');
        $this->redirect('fees/assignments');
    }

    /**
     * Endpoint AJAX: zwraca kalkulację net dla wybranej kombinacji
     * fee_rate + zniżki — używany do live-preview w formularzu.
     */
    public function calculatePreview(): void
    {
        Csrf::verify();
        $rateId = (int)($_POST['fee_rate_id'] ?? 0);
        $discountIds = array_map('intval', (array)($_POST['discount_ids'] ?? []));

        $rate = (new FeeRateModel())->findById($rateId);
        if (!$rate) {
            $this->json(['error' => 'rate_not_found'], 404);
        }

        $allActive = (new FeeDiscountModel())->activeOnDate(date('Y-m-d'));
        $selected  = array_filter($allActive, fn($d) => in_array((int)$d['id'], $discountIds, true));

        $preview = MemberFeeAssignmentModel::calculateNet((float)$rate['amount'], $selected);
        $this->json([
            'rate_name' => $rate['name'],
            'period'    => $rate['period'],
            ...$preview,
        ]);
    }

    /**
     * Formularz bulk-assign: wybór fee_rate + filtr członków + okres.
     * Dostępny dla zarząd/księgowy/admin.
     */
    public function bulkAssignForm(): void
    {
        $this->requireRole(['zarzad', 'ksiegowy', 'admin']);
        $rates      = (new FeeRateModel())->listForClub(null, true);
        $clubSports = (new SportModel())->listForClub($this->currentClub());
        $statuses   = MemberFeeAssignmentModel::$STATUSES;

        $this->render('assignments/bulk_assign', [
            'title'      => 'Masowe przypisanie składek',
            'rates'      => $rates,
            'clubSports' => $clubSports,
            'statuses'   => $statuses,
        ]);
    }

    /**
     * Wykonuje bulk-assign na członkach pasujących do filtra.
     * Tworzy po jednym member_fee_assignment per zawodnika.
     */
    public function bulkAssign(): void
    {
        Csrf::verify();
        $this->requireRole(['zarzad', 'ksiegowy', 'admin']);

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!RateLimiter::check($ip, 'bulk_fee_assign', 5, 60)) {
            Session::flash('error', 'Przekroczono limit bulk operacji (5/godz). Spróbuj później.');
            $this->redirect('fees/bulk-assign');
        }
        RateLimiter::hit($ip, 'bulk_fee_assign', 5, 60);

        $feeRateId = (int)($_POST['fee_rate_id'] ?? 0);
        $validFrom = $this->validateOptionalDate($_POST['valid_from'] ?? null) ?? date('Y-m-d');
        $validTo   = $this->validateOptionalDate($_POST['valid_to']   ?? null);
        $status    = in_array($_POST['status'] ?? 'active', array_keys(MemberFeeAssignmentModel::$STATUSES), true)
            ? $_POST['status'] : 'active';

        if ($feeRateId <= 0) {
            Session::flash('error', 'Wybierz stawkę opłat.');
            $this->redirect('fees/bulk-assign');
        }

        $rate = (new FeeRateModel())->findById($feeRateId);
        if (!$rate) {
            Session::flash('error', 'Nieprawidłowa stawka opłat.');
            $this->redirect('fees/bulk-assign');
        }

        $filter = MemberFilter::fromRequest($_POST);
        $rows   = MemberFilter::query($this->currentClub(), $filter, 5000);

        if (empty($rows)) {
            Session::flash('warning', 'Filtr nie zwrócił żadnych członków.');
            $this->redirect('fees/bulk-assign');
        }

        $model       = new MemberFeeAssignmentModel();
        $created     = 0;
        $skipped     = 0;
        foreach ($rows as $m) {
            try {
                // Idempotentnie: nie twórz duplikatu jeśli już istnieje aktywne identyczne
                $existing = $model->activeForMember((int)$m['id'], $validFrom);
                $alreadyHas = false;
                foreach ($existing as $e) {
                    if ((int)$e['fee_rate_id'] === $feeRateId) {
                        $alreadyHas = true; break;
                    }
                }
                if ($alreadyHas) { $skipped++; continue; }

                $model->insert([
                    'member_id'   => (int)$m['id'],
                    'fee_rate_id' => $feeRateId,
                    'valid_from'  => $validFrom,
                    'valid_to'    => $validTo,
                    'status'      => $status,
                    'notes'       => 'Bulk assign: ' . MemberFilter::describe($filter),
                    'created_by'  => Auth::id(),
                ]);
                $created++;
            } catch (\Throwable $e) {
                $skipped++;
            }
        }

        Session::flash('success', "Przypisano stawkę {$created} zawodnikom." .
            ($skipped > 0 ? " {$skipped} pominięto (duplikat lub błąd)." : ''));
        $this->redirect('fees/assignments');
    }

    private function validateOptionalDate(mixed $value): ?string
    {
        $str = is_string($value) ? trim($value) : '';
        if ($str === '') return null;
        $d = \DateTime::createFromFormat('Y-m-d', $str);
        return ($d && $d->format('Y-m-d') === $str) ? $str : null;
    }
}
