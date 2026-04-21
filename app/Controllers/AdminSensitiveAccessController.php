<?php

namespace App\Controllers;

use App\Helpers\ClubContext;
use App\Models\SensitiveAccessLogModel;
use App\Models\MemberModel;

class AdminSensitiveAccessController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        // Tylko zarzad ma pełen wgląd w audit log
        \App\Helpers\Auth::requireRole(['zarzad']);
    }

    public function index(): void
    {
        $clubId   = ClubContext::current();
        $dataType = $_GET['type'] ?? null;
        $memberId = !empty($_GET['member']) ? (int)$_GET['member'] : null;
        $from     = $_GET['from'] ?? null;
        $to       = $_GET['to'] ?? null;
        $page     = max(1, (int)($_GET['page'] ?? 1));

        $model = new SensitiveAccessLogModel();
        $this->render('admin/sensitive_access/index', [
            'title'      => 'Dziennik dostępu do danych wrażliwych (RODO art. 30)',
            'pagination' => $model->listFiltered($clubId, $dataType, $memberId, $from, $to, $page, 50),
            'topUsers'   => $model->topAccessors($clubId, 30, 10),
            'dataTypes'  => SensitiveAccessLogModel::$DATA_TYPES,
            'actions'    => SensitiveAccessLogModel::$ACTIONS,
            'members'    => (new MemberModel())->search('', null, null, 1, 500)['data'] ?? [],
            'filters'    => compact('dataType', 'memberId', 'from', 'to'),
        ]);
    }
}
