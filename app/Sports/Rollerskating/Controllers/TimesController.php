<?php

namespace App\Sports\Rollerskating\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Rollerskating\Models\RollerskatingTimeModel;

class TimesController extends BaseController
{
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }

    public function index(): void
    {
        $dist = $_GET['distance'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = (new RollerskatingTimeModel())->listForClub($dist ?: null, $page, 25);
        $rankings   = (new RollerskatingTimeModel())->rankings($dist ?: null, 10);
        $this->render('rollerskating/times/index', [
            'title' => 'Pomiary czasu', 'pagination' => $pagination,
            'rankings' => $rankings, 'distanceFilter' => $dist,
        ]);
    }

    public function create(): void
    {
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('rollerskating/times/form', ['title' => 'Nowy pomiar', 'members' => $members]);
    }

    public function store(): void
    {
        Csrf::verify();
        $timeStr  = trim($_POST['time_raw'] ?? '');
        $timeMs   = $this->parseTimeToMs($timeStr);
        if ($timeMs === null) {
            Session::flash('error', 'Nieprawidłowy format czasu (użyj m:ss.mmm lub ss.mmm).');
            $this->redirect('rollerskating/times/create');
        }
        $style = array_key_exists($_POST['skating_style'] ?? '', RollerskatingTimeModel::$SKATING_STYLES)
                    ? $_POST['skating_style'] : null;
        $data = [
            'member_id'         => (int)($_POST['member_id'] ?? 0),
            'distance'          => trim($_POST['distance'] ?? '') ?: null,
            'skating_style'     => $style,
            'discipline_detail' => trim($_POST['discipline_detail'] ?? '') ?: null,
            'time_ms'           => $timeMs,
            'record_date'       => trim($_POST['record_date'] ?? date('Y-m-d')),
            'is_personal_best'  => isset($_POST['is_personal_best']) ? 1 : 0,
            'discipline_id'     => !empty($_POST['discipline_id']) ? (int)$_POST['discipline_id'] : null,
            'notes'             => trim($_POST['notes'] ?? '') ?: null,
        ];
        if ($data['member_id'] <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('rollerskating/times/create'); }
        (new RollerskatingTimeModel())->insert($data);
        Session::flash('success', 'Pomiar zapisany.');
        $this->redirect('rollerskating/times');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new RollerskatingTimeModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('rollerskating/times');
    }

    /** Parsuje "1:23.456" lub "23.456" na milisekundy */
    private function parseTimeToMs(string $raw): ?int
    {
        $raw = trim($raw);
        if (preg_match('/^(\d+):(\d{1,2})\.(\d{1,3})$/', $raw, $m)) {
            return ((int)$m[1] * 60 + (int)$m[2]) * 1000 + (int)str_pad($m[3], 3, '0', STR_PAD_RIGHT);
        }
        if (preg_match('/^(\d+)\.(\d{1,3})$/', $raw, $m)) {
            return (int)$m[1] * 1000 + (int)str_pad($m[2], 3, '0', STR_PAD_RIGHT);
        }
        if (ctype_digit($raw)) return (int)$raw; // raw ms
        return null;
    }
}
