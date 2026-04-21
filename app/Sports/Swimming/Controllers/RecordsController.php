<?php

namespace App\Sports\Swimming\Controllers;

use App\Controllers\BaseController;
use App\Sports\Swimming\Models\SwimmingResultModel;

class RecordsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $model   = new SwimmingResultModel();
        $records = $model->clubRecords();

        // Group by pool_type for display
        $grouped = [];
        foreach ($records as $r) {
            $grouped[$r['pool_type']][] = $r;
        }

        $this->render('swimming/records/index', [
            'title'     => 'Rekordy klubu — Pływanie',
            'grouped'   => $grouped,
            'strokes'   => SwimmingResultModel::$STROKES,
            'poolTypes' => SwimmingResultModel::$POOL_TYPES,
        ]);
    }
}
