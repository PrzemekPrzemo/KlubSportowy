<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ActivityLogModel;
use App\Models\ErrorLogModel;

class AdminErrorController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function index(): void
    {
        $level = $_GET['level'] ?? null;
        $from  = $_GET['from'] ?? null;
        $to    = $_GET['to'] ?? null;
        $page  = max(1, (int)($_GET['page'] ?? 1));

        $model = new ErrorLogModel();
        $pagination = $model->listFiltered($level, $from, $to, $page, 30);
        $stats = $model->stats();

        $this->render('admin/errors/index', [
            'title'      => 'Dziennik błędów',
            'pagination' => $pagination,
            'stats'      => $stats,
            'filter'     => ['level' => $level, 'from' => $from, 'to' => $to],
        ]);
    }

    public function show(string $id): void
    {
        $row = (new ErrorLogModel())->findById((int)$id);
        if (!$row) {
            Session::flash('error', 'Wpis nie istnieje.');
            $this->redirect(url('admin/errors'));
            return;
        }

        $this->render('admin/errors/show', [
            'title' => 'Błąd #' . (int)$id,
            'row'   => $row,
        ]);
    }

    public function purge(): void
    {
        Csrf::verify();
        $days = max(1, (int)($_POST['days'] ?? 90));
        $deleted = (new ErrorLogModel())->purgeOlderThan($days);
        (new ActivityLogModel())->log('error_log_purge', 'error_log', null, "days={$days};deleted={$deleted}");
        Session::flash('success', "Usunięto {$deleted} wpisów starszych niż {$days} dni.");
        $this->redirect(url('admin/errors'));
    }
}
