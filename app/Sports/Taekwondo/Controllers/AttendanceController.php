<?php

namespace App\Sports\Taekwondo\Controllers;

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
        $rows     = $model->monthlySummary('taekwondo', $year);
        $months   = $model->activeMonths('taekwondo', $year);
        $years    = $model->years('taekwondo') ?: [date('Y')];

        $this->render('sport_attendance/index', [
            'title'     => 'Frekwencja — Taekwondo',
            'sportName' => 'Taekwondo',
            'sportKey'  => 'taekwondo',
            'year'      => $year,
            'years'     => $years,
            'rows'      => $rows,
            'months'    => $months,
        ]);
    }
}
