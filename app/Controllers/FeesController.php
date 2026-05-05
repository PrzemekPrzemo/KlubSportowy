<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\FeeRateModel;
use App\Models\MemberModel;
use App\Models\PaymentModel;
use App\Models\SportModel;

class FeesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $page = max(1, (int)($_GET['page'] ?? 1));

        $pagination = (new PaymentModel())->listForClub(null, $year, $page, 30);
        $total      = (new PaymentModel())->totalForClubThisYear();

        $this->render('fees/index', [
            'title'      => 'Finanse',
            'pagination' => $pagination,
            'year'       => $year,
            'total'      => $total,
        ]);
    }

    public function rates(): void
    {
        // Pokaż również nieaktywne — admin może chcieć reaktywować
        $rates  = (new FeeRateModel())->listForClub(null, false);
        $sports = (new SportModel())->listForClub($this->currentClub());

        $this->render('fees/rates', [
            'title'  => 'Stawki opłat',
            'rates'  => $rates,
            'sports' => $sports,
        ]);
    }

    public function storeRate(): void
    {
        Csrf::verify();
        $data = [
            'name'     => trim($_POST['name'] ?? ''),
            'amount'   => (float)($_POST['amount'] ?? 0),
            'period'   => in_array($_POST['period'] ?? '', ['monthly','quarterly','yearly','one_time'], true)
                           ? $_POST['period'] : 'monthly',
            'fee_type' => in_array($_POST['fee_type'] ?? '', ['skladka','wpisowe','licencja','zawody','obóz','inne'], true)
                           ? $_POST['fee_type'] : 'skladka',
            'sport_id' => !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : null,
        ];

        if ($data['name'] === '') {
            Session::flash('error', 'Nazwa stawki jest wymagana.');
            $this->redirect('fees/rates');
        }

        (new FeeRateModel())->insert($data);
        Session::flash('success', 'Stawka dodana.');
        $this->redirect('fees/rates');
    }

    public function deleteRate(string $id): void
    {
        Csrf::verify();
        (new FeeRateModel())->delete((int)$id);
        Session::flash('success', 'Stawka usunięta.');
        $this->redirect('fees/rates');
    }

    public function editRate(string $id): void
    {
        $rate = (new FeeRateModel())->findById((int)$id);
        if (!$rate) {
            Session::flash('error', 'Nie znaleziono stawki.');
            $this->redirect('fees/rates');
        }

        $sports  = (new SportModel())->listForClub($this->currentClub());
        // Klasy dla aktualnego sport_id (jeśli ustawiony)
        $classes = [];
        if (!empty($rate['sport_id'])) {
            $stmt = \App\Helpers\Database::pdo()->prepare(
                "SELECT id, name FROM member_classes
                 WHERE sport_id = ? AND (club_id IS NULL OR club_id = ?)
                 ORDER BY sort_order, name"
            );
            $stmt->execute([(int)$rate['sport_id'], $this->currentClub()]);
            $classes = $stmt->fetchAll();
        }

        $this->render('fees/edit_rate', [
            'title'   => 'Edytuj stawkę',
            'rate'    => $rate,
            'sports'  => $sports,
            'classes' => $classes,
        ]);
    }

    public function updateRate(string $id): void
    {
        Csrf::verify();
        $idInt = (int)$id;
        $existing = (new FeeRateModel())->findById($idInt);
        if (!$existing) {
            Session::flash('error', 'Nie znaleziono stawki.');
            $this->redirect('fees/rates');
        }

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('error', 'Nazwa stawki jest wymagana.');
            $this->redirect('fees/rates/' . $idInt . '/edit');
        }

        $amount = (float)($_POST['amount'] ?? 0);
        if ($amount < 0) {
            Session::flash('error', 'Kwota musi być >= 0.');
            $this->redirect('fees/rates/' . $idInt . '/edit');
        }

        $data = [
            'name'        => $name,
            'amount'      => $amount,
            'period'      => in_array($_POST['period'] ?? '', ['monthly','quarterly','yearly','one_time'], true)
                              ? $_POST['period'] : 'monthly',
            'fee_type'    => in_array($_POST['fee_type'] ?? '', ['skladka','wpisowe','licencja','zawody','obóz','inne'], true)
                              ? $_POST['fee_type'] : 'skladka',
            'sport_id'    => !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : null,
            'class_id'    => !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        ];

        (new FeeRateModel())->update($idInt, $data);
        Session::flash('success', 'Stawka zaktualizowana.');
        $this->redirect('fees/rates');
    }

    /**
     * Toggle is_active bez usuwania — pozwala dezaktywować historyczne
     * stawki bez utraty danych referencyjnych w fee_rate_id payments'ów.
     */
    public function toggleRateActive(string $id): void
    {
        Csrf::verify();
        $idInt = (int)$id;
        $rate = (new FeeRateModel())->findById($idInt);
        if (!$rate) {
            Session::flash('error', 'Nie znaleziono stawki.');
            $this->redirect('fees/rates');
        }
        $newVal = empty($rate['is_active']) ? 1 : 0;
        (new FeeRateModel())->update($idInt, ['is_active' => $newVal]);
        Session::flash('success', $newVal ? 'Stawka aktywowana.' : 'Stawka dezaktywowana.');
        $this->redirect('fees/rates');
    }

    public function createPayment(): void
    {
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $rates   = (new FeeRateModel())->listForClub();
        $this->render('fees/payment_form', [
            'title'   => 'Nowa opłata',
            'members' => $members,
            'rates'   => $rates,
        ]);
    }

    public function storePayment(): void
    {
        Csrf::verify();
        $data = [
            'member_id'    => (int)($_POST['member_id'] ?? 0),
            'fee_rate_id'  => !empty($_POST['fee_rate_id']) ? (int)$_POST['fee_rate_id'] : null,
            'amount'       => (float)($_POST['amount'] ?? 0),
            'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
            'period_year'  => (int)($_POST['period_year'] ?? date('Y')),
            'period_month' => !empty($_POST['period_month']) ? (int)$_POST['period_month'] : null,
            'method'       => in_array($_POST['method'] ?? '', ['gotowka','przelew','karta','blik','inny'], true)
                              ? $_POST['method'] : 'przelew',
            'reference'    => trim($_POST['reference'] ?? '') ?: null,
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
            'created_by'   => Auth::id(),
        ];

        if ($data['member_id'] <= 0 || $data['amount'] <= 0) {
            Session::flash('error', 'Wybierz zawodnika i podaj kwotę.');
            $this->redirect('fees/new');
        }

        $paymentId = (new PaymentModel())->insert($data);

        // U.1 — auto-naliczanie prowizji trenerów po manualnej rejestracji wpłaty.
        // Błąd kalkulatora NIE powinien rollbackować wpłaty — log + dalej.
        try {
            \App\Helpers\CommissionCalculator::accrueForPayment(
                array_merge($data, [
                    'id'      => $paymentId,
                    'club_id' => \App\Helpers\ClubContext::current(),
                ])
            );
        } catch (\Throwable $e) {
            error_log('CommissionCalculator (manual) failed: ' . $e->getMessage());
        }

        Session::flash('success', 'Opłata zarejestrowana.');
        $this->redirect('fees');
    }
}
