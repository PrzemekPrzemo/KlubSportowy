<?php

namespace App\Sports\Mma\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Mma\Models\MmaRecordModel;
use App\Sports\Mma\Models\MmaResultModel;

/**
 * Admin widok kartoteki MMA:
 *   - GET  /mma/record/:memberId          - kartoteka W-L-D + KO/Sub/Dec + mix
 *   - POST /mma/record/:memberId/update   - edycja
 */
class SportMmaController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function memberRecord(string $memberId): void
    {
        $mid    = (int)$memberId;
        $record = new MmaRecordModel();

        if (!$record->memberBelongsToClub($mid)) {
            Session::flash('error', 'Zawodnik nie nalezy do biezacego klubu.');
            $this->redirect('mma/results');
        }

        $member  = (new MemberModel())->findById($mid);
        $row     = $record->forMember($mid);
        $results = (new MmaResultModel())->listForClub($mid);

        $this->render('mma/record/index', [
            'title'   => 'Kartoteka MMA',
            'member'  => $member,
            'record'  => $row,
            'results' => $results,
            'stances' => MmaRecordModel::$STANCES,
        ]);
    }

    public function updateRecord(string $memberId): void
    {
        Csrf::verify();
        $mid    = (int)$memberId;
        $record = new MmaRecordModel();

        if (!$record->memberBelongsToClub($mid)) {
            Session::flash('error', 'Zawodnik nie nalezy do biezacego klubu.');
            $this->redirect('mma/results');
        }

        $stance = $_POST['stance'] ?? 'orthodox';
        if (!array_key_exists($stance, MmaRecordModel::$STANCES)) {
            $stance = 'orthodox';
        }

        $record->upsert($mid, [
            'wins'                 => max(0, (int)($_POST['wins']     ?? 0)),
            'losses'               => max(0, (int)($_POST['losses']   ?? 0)),
            'draws'                => max(0, (int)($_POST['draws']    ?? 0)),
            'ko_wins'              => max(0, (int)($_POST['ko_wins']  ?? 0)),
            'sub_wins'             => max(0, (int)($_POST['sub_wins'] ?? 0)),
            'dec_wins'             => max(0, (int)($_POST['dec_wins'] ?? 0)),
            'current_weight_class' => trim((string)($_POST['current_weight_class'] ?? '')) ?: null,
            'stance'               => $stance,
            'reach_cm'             => !empty($_POST['reach_cm']) ? (int)$_POST['reach_cm'] : null,
            'pct_striking'         => (int)($_POST['pct_striking']  ?? 33),
            'pct_wrestling'        => (int)($_POST['pct_wrestling'] ?? 33),
            'pct_grappling'        => (int)($_POST['pct_grappling'] ?? 34),
        ]);

        Session::flash('success', 'Kartoteka MMA zapisana.');
        $this->redirect('mma/record/' . $mid);
    }
}
