<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\CsvExporter;
use App\Helpers\Session;
use App\Helpers\ValidatesRequest;
use App\Models\SportModel;
use App\Models\TrainerCommissionLogModel;
use App\Models\TrainerCommissionRateModel;

/**
 * Faza U.2 — admin UI dla prowizji trenerów.
 *
 * Zakres:
 *   - CRUD stawek: /club/trainers/commissions/rates
 *   - Raport miesięczny per trener z CSV: /club/trainers/commissions/report
 *   - Bulk mark-paid-out po wypłacie do trenera
 *
 * Dostęp: dowolny user z club_id w sesji (RBAC zarządu wyegzekwowany
 * w przyszłości — na razie requireClubContext).
 */
class TrainerCommissionsController extends BaseController
{
    use ValidatesRequest;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    /** Dashboard prowizji — lista naliczonych + szybka agregacja bieżącego miesiąca. */
    public function index(): void
    {
        $year   = (int)($_GET['year']  ?? date('Y'));
        $month  = !empty($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
        $logModel = new TrainerCommissionLogModel();

        $items   = $logModel->listForClub([
            'period_year'  => $year,
            'period_month' => $month,
        ]);
        $summary = $logModel->aggregateByTrainer($year, $month);

        $this->render('trainers/commissions/index', [
            'title'    => 'Prowizje trenerów',
            'items'    => $items,
            'summary'  => $summary,
            'year'     => $year,
            'month'    => $month,
            'statuses' => TrainerCommissionLogModel::$STATUSES,
        ]);
    }

    /** Raport rok/miesiąc — opcjonalnie CSV gdy ?format=csv. */
    public function report(): void
    {
        $year  = (int)($_GET['year']  ?? date('Y'));
        $month = !empty($_GET['month']) ? (int)$_GET['month'] : null;
        $logModel = new TrainerCommissionLogModel();

        $rows = $logModel->aggregateByTrainer($year, $month);

        if (($_GET['format'] ?? '') === 'csv') {
            $headers = ['Trener', 'Login', 'Wpisów', 'Suma', 'Naliczone', 'Wypłacone'];
            $data    = array_map(fn($r) => [
                $r['full_name'] ?? $r['username'],
                $r['username'],
                (int)$r['items'],
                number_format((float)$r['total'],    2, '.', ''),
                number_format((float)$r['accrued'],  2, '.', ''),
                number_format((float)$r['paid_out'], 2, '.', ''),
            ], $rows);
            $period = $month ? "{$year}-" . str_pad((string)$month, 2, '0', STR_PAD_LEFT) : (string)$year;
            CsvExporter::download("prowizje_trenerow_{$period}.csv", $headers, $data);
            return;
        }

        $this->render('trainers/commissions/report', [
            'title' => 'Raport prowizji',
            'rows'  => $rows,
            'year'  => $year,
            'month' => $month,
        ]);
    }

    /** Bulk: oznacz wskazane wpisy logu jako wypłacone (po przelewie). */
    public function markPaidOut(): void
    {
        Csrf::verify();
        $ids = (array)($_POST['ids'] ?? []);
        $count = (new TrainerCommissionLogModel())->markPaidOut($ids);
        Session::flash('success', "Oznaczono {$count} pozycji jako wypłacone.");
        $this->redirect('club/trainers/commissions');
    }

    // -------------- CRUD STAWEK --------------

    public function rates(): void
    {
        $rateModel = new TrainerCommissionRateModel();
        $rates     = $rateModel->listForClub();
        $trainers  = $rateModel->trainersForClub();

        $this->render('trainers/commissions/rates', [
            'title'      => 'Stawki prowizji',
            'rates'      => $rates,
            'trainers'   => $trainers,
            'types'      => TrainerCommissionRateModel::$TYPES,
            'appliesTo'  => TrainerCommissionRateModel::$APPLIES_TO,
        ]);
    }

    public function createRate(): void
    {
        $rateModel = new TrainerCommissionRateModel();
        $this->render('trainers/commissions/rate_edit', [
            'title'     => 'Nowa stawka prowizji',
            'rate'      => null,
            'trainers'  => $rateModel->trainersForClub(),
            'sports'    => (new SportModel())->findAll('name'),
            'types'     => TrainerCommissionRateModel::$TYPES,
            'appliesTo' => TrainerCommissionRateModel::$APPLIES_TO,
        ]);
    }

    public function storeRate(): void
    {
        Csrf::verify();
        $back = 'club/trainers/commissions/rates';

        $trainerId = (int)($_POST['trainer_user_id'] ?? 0);
        if ($trainerId <= 0) {
            Session::flash('error', 'Wybierz trenera.');
            $this->redirect($back);
        }

        $type = $this->validateInList(
            $_POST['commission_type'] ?? '',
            TrainerCommissionRateModel::$TYPES,
            'commission_type',
            $back
        );

        $valueRaw = $_POST['value'] ?? '';
        if (!is_numeric($valueRaw)) {
            Session::flash('error', "Pole 'value' musi być liczbą.");
            $this->redirect($back);
        }
        $value = (float)$valueRaw;
        if ($type === TrainerCommissionRateModel::TYPE_PERCENT && ($value < 0 || $value > 100)) {
            Session::flash('error', 'Procent musi być w zakresie 0-100.');
            $this->redirect($back);
        }
        if ($type === TrainerCommissionRateModel::TYPE_FIXED && $value < 0) {
            Session::flash('error', 'Kwota stała musi być >= 0.');
            $this->redirect($back);
        }

        $appliesTo = $this->validateInList(
            $_POST['applies_to'] ?? '',
            TrainerCommissionRateModel::$APPLIES_TO,
            'applies_to',
            $back
        );

        $data = [
            'trainer_user_id' => $trainerId,
            'sport_id'        => !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : null,
            'commission_type' => $type,
            'value'           => $value,
            'applies_to'      => $appliesTo,
            'valid_from'      => $this->validateDate($_POST['valid_from'] ?? null, $back) ?? date('Y-m-d'),
            'valid_to'        => $this->validateDate($_POST['valid_to'] ?? null, $back),
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
        ];

        try {
            (new TrainerCommissionRateModel())->insert($data);
        } catch (\PDOException $e) {
            // Naruszenie UNIQUE: ten sam trener + sport + applies_to już istnieje
            if ((int)$e->getCode() === 23000) {
                Session::flash('error',
                    'Stawka dla tej kombinacji (trener + sport + typ opłaty) już istnieje. Edytuj istniejący wpis.');
                $this->redirect($back);
            }
            throw $e;
        }
        Session::flash('success', 'Stawka utworzona.');
        $this->redirect($back);
    }

    public function editRate(string $id): void
    {
        $rateModel = new TrainerCommissionRateModel();
        $rate      = $rateModel->findById((int)$id);
        if (!$rate) {
            Session::flash('error', 'Nie znaleziono stawki.');
            $this->redirect('club/trainers/commissions/rates');
        }
        $this->render('trainers/commissions/rate_edit', [
            'title'     => 'Edytuj stawkę prowizji',
            'rate'      => $rate,
            'trainers'  => $rateModel->trainersForClub(),
            'sports'    => (new SportModel())->findAll('name'),
            'types'     => TrainerCommissionRateModel::$TYPES,
            'appliesTo' => TrainerCommissionRateModel::$APPLIES_TO,
        ]);
    }

    public function updateRate(string $id): void
    {
        Csrf::verify();
        $back  = 'club/trainers/commissions/rates';
        $idInt = (int)$id;

        $rateModel = new TrainerCommissionRateModel();
        if (!$rateModel->findById($idInt)) {
            Session::flash('error', 'Nie znaleziono stawki.');
            $this->redirect($back);
        }

        $type = $this->validateInList(
            $_POST['commission_type'] ?? '',
            TrainerCommissionRateModel::$TYPES,
            'commission_type',
            $back
        );
        $valueRaw = $_POST['value'] ?? '';
        if (!is_numeric($valueRaw)) {
            Session::flash('error', "Pole 'value' musi być liczbą.");
            $this->redirect($back);
        }
        $value = (float)$valueRaw;
        if ($type === TrainerCommissionRateModel::TYPE_PERCENT && ($value < 0 || $value > 100)) {
            Session::flash('error', 'Procent musi być w zakresie 0-100.');
            $this->redirect($back);
        }

        $rateModel->update($idInt, [
            'commission_type' => $type,
            'value'           => $value,
            'applies_to'      => $this->validateInList(
                $_POST['applies_to'] ?? '',
                TrainerCommissionRateModel::$APPLIES_TO,
                'applies_to',
                $back
            ),
            'valid_from'      => $this->validateDate($_POST['valid_from'] ?? null, $back) ?? date('Y-m-d'),
            'valid_to'        => $this->validateDate($_POST['valid_to'] ?? null, $back),
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
            'notes'           => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Stawka zaktualizowana.');
        $this->redirect($back);
    }

    public function toggleRate(string $id): void
    {
        Csrf::verify();
        $idInt = (int)$id;
        $rateModel = new TrainerCommissionRateModel();
        $rate = $rateModel->findById($idInt);
        if (!$rate) {
            Session::flash('error', 'Nie znaleziono stawki.');
            $this->redirect('club/trainers/commissions/rates');
        }
        $rateModel->update($idInt, ['is_active' => empty($rate['is_active']) ? 1 : 0]);
        Session::flash('success', 'Status stawki zmieniony.');
        $this->redirect('club/trainers/commissions/rates');
    }

    public function deleteRate(string $id): void
    {
        Csrf::verify();
        (new TrainerCommissionRateModel())->delete((int)$id);
        Session::flash('success', 'Stawka usunięta.');
        $this->redirect('club/trainers/commissions/rates');
    }

    private function validateDate(mixed $value, string $back): ?string
    {
        $str = is_string($value) ? trim($value) : '';
        if ($str === '') return null;
        $d = \DateTime::createFromFormat('Y-m-d', $str);
        if (!$d || $d->format('Y-m-d') !== $str) {
            Session::flash('error', 'Data musi być w formacie YYYY-MM-DD.');
            $this->redirect($back);
        }
        return $str;
    }
}
