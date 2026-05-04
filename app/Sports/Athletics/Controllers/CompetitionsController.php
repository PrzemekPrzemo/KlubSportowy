<?php

namespace App\Sports\Athletics\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Athletics\Models\AthleticsCompetitionModel;

class CompetitionsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    private const TYPES    = ['klubowe','regionalne','krajowe','mistrzostwa','miting','inne'];
    private const STATUSES = ['zaplanowane','w_trakcie','zakonczone','odwolane'];

    public function index(): void
    {
        $status     = $_GET['status'] ?? '';
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $model      = new AthleticsCompetitionModel();
        $pagination = $model->listForClub($status ?: null, $page, 25);
        $counts     = $model->statusCounts();

        $this->render('athletics/competitions/index', [
            'title'        => 'Zawody lekkoatletyczne',
            'pagination'   => $pagination,
            'statusFilter' => $status,
            'counts'       => $counts,
        ]);
    }

    public function create(): void
    {
        $this->render('athletics/competitions/form', [
            'title'       => 'Nowe zawody',
            'competition' => null,
            'types'       => self::TYPES,
            'statuses'    => self::STATUSES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        $data['created_by'] = Auth::id();
        (new AthleticsCompetitionModel())->insert($data);
        Session::flash('success', 'Zawody utworzone.');
        $this->redirect('athletics/competitions');
    }

    public function show(string $id): void
    {
        $row = (new AthleticsCompetitionModel())->withResults((int)$id);
        if (!$row) {
            Session::flash('error', 'Nie znaleziono zawodów.');
            $this->redirect('athletics/competitions');
        }
        $this->render('athletics/competitions/show', [
            'title'       => $row['name'],
            'competition' => $row,
        ]);
    }

    public function edit(string $id): void
    {
        $row = (new AthleticsCompetitionModel())->findById((int)$id);
        if (!$row) {
            Session::flash('error', 'Nie znaleziono.');
            $this->redirect('athletics/competitions');
        }
        $this->render('athletics/competitions/form', [
            'title'       => 'Edytuj zawody',
            'competition' => $row,
            'types'       => self::TYPES,
            'statuses'    => self::STATUSES,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        (new AthleticsCompetitionModel())->update((int)$id, $data);
        Session::flash('success', 'Zaktualizowano.');
        $this->redirect('athletics/competitions/' . (int)$id);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new AthleticsCompetitionModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('athletics/competitions');
    }

    private function parsePost(): ?array
    {
        $name      = trim($_POST['name'] ?? '');
        $dateFrom  = trim($_POST['date_from'] ?? '');
        $type      = $_POST['type']   ?? 'klubowe';
        $status    = $_POST['status'] ?? 'zaplanowane';

        if ($name === '' || $dateFrom === '') {
            Session::flash('error', 'Nazwa i data wymagane.');
            $this->redirect('athletics/competitions/create');
            return null;
        }
        return [
            'name'      => $name,
            'location'  => trim($_POST['location'] ?? '') ?: null,
            'date_from' => $dateFrom,
            'date_to'   => trim($_POST['date_to'] ?? '') ?: null,
            'type'      => in_array($type, self::TYPES, true) ? $type : 'klubowe',
            'status'    => in_array($status, self::STATUSES, true) ? $status : 'zaplanowane',
            'notes'     => trim($_POST['notes'] ?? '') ?: null,
        ];
    }
}
