<?php

namespace App\Sports\Cycling\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Cycling\Models\CyclingFtpModel;

class FtpController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $memberFilter = !empty($_GET['member']) ? (int)$_GET['member'] : null;
        $model        = new CyclingFtpModel();
        $tests        = $model->listForClub($memberFilter);
        $members      = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('cycling/ftp/index', [
            'title'        => 'Testy FTP — Kolarstwo',
            'tests'        => $tests,
            'members'      => $members,
            'protocols'    => CyclingFtpModel::$PROTOCOLS,
            'memberFilter' => $memberFilter,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        $watts    = (int)($_POST['ftp_watts'] ?? 0);
        if ($memberId <= 0 || $watts <= 0) {
            Session::flash('error', 'Wybierz zawodnika i podaj FTP.');
            $this->redirect('cycling/ftp');
        }

        $protocol = array_key_exists($_POST['protocol'] ?? '', CyclingFtpModel::$PROTOCOLS)
            ? $_POST['protocol'] : '20min';

        (new CyclingFtpModel())->insert([
            'member_id' => $memberId,
            'test_date' => trim($_POST['test_date'] ?? '') ?: date('Y-m-d'),
            'ftp_watts' => $watts,
            'protocol'  => $protocol,
            'weight_kg' => !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : null,
            'notes'     => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Test FTP dodany.');
        $this->redirect('cycling/ftp');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new CyclingFtpModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('cycling/ftp');
    }
}
