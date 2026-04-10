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
        $rates  = (new FeeRateModel())->listForClub();
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

        (new PaymentModel())->insert($data);
        Session::flash('success', 'Opłata zarejestrowana.');
        $this->redirect('fees');
    }
}
