<?php

namespace App\Sports\Karate\Controllers;

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
        $rows     = $model->monthlySummary('karate', $year);
        $months   = $model->activeMonths('karate', $year);
        $years    = $model->years('karate') ?: [date('Y')];

        $this->render('sport_attendance/index', [
            'title'     => 'Frekwencja — Karate',
            'sportName' => 'Karate',
            'sportKey'  => 'karate',
            'year'      => $year,
            'years'     => $years,
            'rows'      => $rows,
            'months'    => $months,
        ]);
    }
}
