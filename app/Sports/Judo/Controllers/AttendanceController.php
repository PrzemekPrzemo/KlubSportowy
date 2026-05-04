<?php

namespace App\Sports\Judo\Controllers;

use App\Controllers\BaseController;
use App\Models\SportAttendanceModel;

class AttendanceController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $model    = new SportAttendanceModel();
        $year     = (int)($_GET['year'] ?? date('Y'));
        $rows     = $model->monthlySummary('judo', $year);
        $months   = $model->activeMonths('judo', $year);
        $years    = $model->years('judo') ?: [date('Y')];

        $this->render('sport_attendance/index', [
            'title'     => 'Frekwencja — Judo',
            'sportName' => 'Judo',
            'sportKey'  => 'judo',
            'year'      => $year,
            'years'     => $years,
            'rows'      => $rows,
            'months'    => $months,
        ]);
    }
}
