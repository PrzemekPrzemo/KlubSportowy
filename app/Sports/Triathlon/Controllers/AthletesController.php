<?php

namespace App\Sports\Triathlon\Controllers;

use App\Controllers\BaseController;
use App\Models\MemberModel;
use App\Sports\Triathlon\Models\TriathlonResultModel;

class AthletesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $model   = new TriathlonResultModel();

        $athleteData = [];
        foreach ($members as $m) {
            $pbs = $model->pbsForMember((int)$m['id']);
            if (!empty($pbs)) {
                $athleteData[] = array_merge($m, ['pbs' => $pbs]);
            }
        }

        $this->render('triathlon/athletes/index', [
            'title'       => 'Zawodnicy — Triathlon',
            'athletes'    => $athleteData,
            'distances'   => TriathlonResultModel::$DISTANCES,
        ]);
    }
}
