<?php

namespace App\Sports\Fencing\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Fencing\Models\FencingMemberModel;
use App\Sports\Fencing\Models\FencingPoolModel;
use App\Sports\Fencing\Models\FencingResultModel;

/**
 * Admin widok kartoteki szermiercze + pools (DE):
 *   - GET  /fencing/profile/:memberId           - profil szermierza + bron + FIE
 *   - POST /fencing/profile/:memberId/update    - edycja
 *   - GET  /fencing/pools/:tournamentId         - lista pools
 *   - POST /fencing/pools/:tournamentId/store   - dodaj pool
 */
class SportFencingController extends BaseController
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
        $profile = new FencingMemberModel();

        if (!$profile->memberBelongsToClub($mid)) {
            Session::flash('error', 'Zawodnik nie nalezy do biezacego klubu.');
            $this->redirect('fencing/results');
        }

        $member  = (new MemberModel())->findById($mid);
        $row     = $profile->forMember($mid);
        $results = (new FencingResultModel())->listForClub();
        $myRes   = array_values(array_filter($results, fn($r) => (int)$r['member_id'] === $mid));

        $this->render('fencing/profile/index', [
            'title'   => 'Profil szermierza',
            'member'  => $member,
            'profile' => $row,
            'results' => $myRes,
            'weapons' => FencingMemberModel::$WEAPONS,
            'hands'   => FencingMemberModel::$HANDS,
        ]);
    }

    public function updateRecord(string $memberId): void
    {
        Csrf::verify();
        $mid     = (int)$memberId;
        $profile = new FencingMemberModel();

        if (!$profile->memberBelongsToClub($mid)) {
            Session::flash('error', 'Zawodnik nie nalezy do biezacego klubu.');
            $this->redirect('fencing/results');
        }

        $weapons = $_POST['weapons'] ?? [];
        if (!is_array($weapons)) $weapons = [$weapons];

        $hand = $_POST['hand'] ?? 'right';
        if (!array_key_exists($hand, FencingMemberModel::$HANDS)) $hand = 'right';

        $profile->upsert($mid, [
            'weapons'  => $weapons,
            'fie_rank' => !empty($_POST['fie_rank']) ? (int)$_POST['fie_rank'] : null,
            'hand'     => $hand,
        ]);

        Session::flash('success', 'Profil szermierza zapisany.');
        $this->redirect('fencing/profile/' . $mid);
    }

    public function poolForm(string $tournamentId): void
    {
        $tid  = (int)$tournamentId;
        $pool = new FencingPoolModel();

        if (!$pool->tournamentBelongsToClub($tid)) {
            Session::flash('error', 'Turniej nie nalezy do klubu.');
            $this->redirect('fencing/results');
        }

        // Fetch tournament header for context (avoid coupling — use raw query)
        $stmt = Database::pdo()->prepare(
            "SELECT id, name, sport_key, date_start, format
             FROM tournaments WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$tid, (int)$this->currentClub()]);
        $tournament = $stmt->fetch() ?: null;

        $pools = $pool->listForTournament($tid);

        $this->render('fencing/pools/form', [
            'title'      => 'Pools (szermierka)',
            'tournament' => $tournament,
            'pools'      => $pools,
            'weapons'    => FencingMemberModel::$WEAPONS,
        ]);
    }

    public function storePool(string $tournamentId): void
    {
        Csrf::verify();
        $tid  = (int)$tournamentId;
        $pool = new FencingPoolModel();

        if (!$pool->tournamentBelongsToClub($tid)) {
            Session::flash('error', 'Turniej nie nalezy do klubu.');
            $this->redirect('fencing/results');
        }

        $poolNumber = max(1, (int)($_POST['pool_number'] ?? 1));
        $weapon     = $_POST['weapon'] ?? 'epee';

        try {
            $pool->create($tid, $poolNumber, $weapon);
            Session::flash('success', 'Pool dodany.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Nie udalo sie dodac pool (mozliwy duplikat numeru).');
        }
        $this->redirect('fencing/pools/' . $tid);
    }
}
