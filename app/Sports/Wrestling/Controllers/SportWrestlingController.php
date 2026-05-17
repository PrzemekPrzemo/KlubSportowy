<?php

namespace App\Sports\Wrestling\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Wrestling\Models\WrestlingMatchBreakdownModel;
use App\Sports\Wrestling\Models\WrestlingMemberModel;
use App\Sports\Wrestling\Models\WrestlingResultModel;

/**
 * Admin widok kartoteki zapasniczej:
 *   - GET  /wrestling/profile/:memberId           - kartoteka + style + statystyki
 *   - POST /wrestling/profile/:memberId/update    - edycja stylu/wagi/rank
 *   - GET  /wrestling/breakdown/:matchId          - formularz technicznego protokolu
 *   - POST /wrestling/breakdown/:matchId/store    - dodaj breakdown
 */
class SportWrestlingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function memberRecord(string $memberId): void
    {
        $mid     = (int)$memberId;
        $profile = new WrestlingMemberModel();

        if (!$profile->memberBelongsToClub($mid)) {
            Session::flash('error', 'Zawodnik nie nalezy do biezacego klubu.');
            $this->redirect('wrestling/results');
        }

        $member  = (new MemberModel())->findById($mid);
        $row     = $profile->forMember($mid);
        $stats   = (new WrestlingMatchBreakdownModel())->statsForMember($mid);
        $results = (new WrestlingResultModel())->listForClub($mid);

        $this->render('wrestling/profile/index', [
            'title'   => 'Profil zapasnika',
            'member'  => $member,
            'profile' => $row,
            'stats'   => $stats,
            'results' => $results,
            'styles'  => WrestlingMemberModel::$STYLE_KEYS,
        ]);
    }

    public function updateRecord(string $memberId): void
    {
        Csrf::verify();
        $mid     = (int)$memberId;
        $profile = new WrestlingMemberModel();

        if (!$profile->memberBelongsToClub($mid)) {
            Session::flash('error', 'Zawodnik nie nalezy do biezacego klubu.');
            $this->redirect('wrestling/results');
        }

        $styles = $_POST['styles'] ?? [];
        if (!is_array($styles)) $styles = [$styles];

        $weight = $_POST['current_weight_kg'] ?? '';
        $weight = ($weight !== '' && is_numeric($weight)) ? (float)$weight : null;

        $profile->upsert($mid, [
            'styles'               => $styles,
            'current_weight_kg'    => $weight,
            'current_weight_class' => trim((string)($_POST['current_weight_class'] ?? '')) ?: null,
            'rank_points'          => max(0, (int)($_POST['rank_points'] ?? 0)),
        ]);

        Session::flash('success', 'Profil zapasnika zapisany.');
        $this->redirect('wrestling/profile/' . $mid);
    }

    /** GET /wrestling/breakdown/:matchId — formularz dodawania breakdownu. */
    public function breakdownForm(string $matchId): void
    {
        $mid       = (int)$matchId;
        $breakdown = new WrestlingMatchBreakdownModel();

        if (!$breakdown->matchBelongsToClub($mid)) {
            Session::flash('error', 'Mecz nie istnieje lub nie nalezy do klubu.');
            $this->redirect('wrestling/results');
        }

        $existing = $breakdown->listForMatch($mid);
        $members  = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('wrestling/breakdown/form', [
            'title'    => 'Protokol techniczny meczu',
            'matchId'  => $mid,
            'existing' => $existing,
            'members'  => $members,
        ]);
    }

    /** POST /wrestling/breakdown/:matchId/store — zapis breakdownu. */
    public function storeBreakdown(string $matchId): void
    {
        Csrf::verify();
        $matchIdInt = (int)$matchId;
        $breakdown  = new WrestlingMatchBreakdownModel();

        if (!$breakdown->matchBelongsToClub($matchIdInt)) {
            Session::flash('error', 'Mecz nie istnieje lub nie nalezy do klubu.');
            $this->redirect('wrestling/results');
        }

        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0 || !$breakdown->memberBelongsToClub($memberId)) {
            Session::flash('error', 'Wybierz zawodnika nalezacego do klubu.');
            $this->redirect('wrestling/breakdown/' . $matchIdInt);
        }

        $breakdown->insert([
            'match_id'       => $matchIdInt,
            'member_id'      => $memberId,
            'takedowns'      => max(0, (int)($_POST['takedowns']     ?? 0)),
            'exposures'      => max(0, (int)($_POST['exposures']     ?? 0)),
            'escapes'        => max(0, (int)($_POST['escapes']       ?? 0)),
            'technical_fall' => !empty($_POST['technical_fall']) ? 1 : 0,
            'pin'            => !empty($_POST['pin']) ? 1 : 0,
            'caution_count'  => max(0, (int)($_POST['caution_count'] ?? 0)),
        ]);

        Session::flash('success', 'Protokol dodany.');
        $this->redirect('wrestling/breakdown/' . $matchIdInt);
    }
}
