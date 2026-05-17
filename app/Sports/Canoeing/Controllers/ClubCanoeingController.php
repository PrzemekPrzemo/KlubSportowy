<?php

namespace App\Sports\Canoeing\Controllers;

use App\Controllers\BaseController;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Canoeing\Models\CanoeingMemberModel;
use App\Sports\Canoeing\Models\CanoeingRaceResultModel;

/**
 * Panel klubowy — kajakarstwo (timing-based: sprint/slalom).
 *
 *   GET  /club/canoeing/members           — profile kajakarzy (boat class, ranking)
 *   POST /club/canoeing/members/save      — upsert profilu
 *   GET  /club/canoeing/results           — wyniki wyscigow
 *   POST /club/canoeing/results/store     — dodanie wyniku (z auto-rerank)
 */
class ClubCanoeingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function members(): void
    {
        $this->render('canoeing/club/members', [
            'title'        => 'Kajakarstwo — Zawodnicy',
            'rows'         => (new CanoeingMemberModel())->listForClub(),
            'allMembers'   => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'boatClasses'  => CanoeingMemberModel::BOAT_CLASSES,
        ]);
    }

    public function saveMember(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('club/canoeing/members');
        }
        (new CanoeingMemberModel())->upsert($memberId, [
            'boat_class'    => $_POST['boat_class'] ?? 'K1',
            'weight_class'  => $_POST['weight_class'] ?? null,
            'national_rank' => $_POST['national_rank'] ?? null,
        ]);
        Session::flash('success', 'Profil zapisany.');
        $this->redirect('club/canoeing/members');
    }

    public function results(): void
    {
        $this->render('canoeing/club/results', [
            'title'       => 'Kajakarstwo — Wyniki wyscigow',
            'results'     => (new CanoeingRaceResultModel())->listForClub(),
            'members'     => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'tournaments' => $this->listTournaments(),
            'distances'   => CanoeingRaceResultModel::DISTANCES,
            'boatClasses' => CanoeingMemberModel::BOAT_CLASSES,
        ]);
    }

    public function storeResult(): void
    {
        Csrf::verify();
        $tournamentId = (int)($_POST['tournament_id'] ?? 0);
        $memberId     = (int)($_POST['member_id'] ?? 0);
        $distance     = (int)($_POST['distance_m'] ?? 0);
        $boatClass    = (string)($_POST['boat_class'] ?? '');
        $finishTime   = trim((string)($_POST['finish_time'] ?? ''));

        if ($tournamentId <= 0 || $memberId <= 0 || $distance <= 0 || $boatClass === '' || $finishTime === '') {
            Session::flash('error', 'Wymagane: turniej, zawodnik, dystans, klasa lodzi, czas.');
            $this->redirect('club/canoeing/results');
        }
        if (!array_key_exists($boatClass, CanoeingMemberModel::BOAT_CLASSES)) {
            Session::flash('error', 'Nieprawidlowa klasa lodzi.');
            $this->redirect('club/canoeing/results');
        }

        $finishMs = $this->parseTimeToMs($finishTime);
        if ($finishMs === null || $finishMs <= 0) {
            Session::flash('error', 'Nieprawidlowy format czasu (uzyj M:SS.mmm lub SS.mmm).');
            $this->redirect('club/canoeing/results');
        }

        $penalties = isset($_POST['penalties_seconds']) && $_POST['penalties_seconds'] !== ''
            ? round((float)$_POST['penalties_seconds'], 2)
            : 0;

        $model = new CanoeingRaceResultModel();
        $model->insert([
            'tournament_id'     => $tournamentId,
            'member_id'         => $memberId,
            'distance_m'        => $distance,
            'boat_class'        => $boatClass,
            'finish_time_ms'    => $finishMs,
            'penalties_seconds' => $penalties,
            'rank'              => null,
        ]);
        $model->rerankTournament($tournamentId, $distance, $boatClass);

        Session::flash('success', 'Wynik dodany — przeliczono ranking.');
        $this->redirect('club/canoeing/results');
    }

    /** Parsuje "M:SS.mmm" lub "H:MM:SS.mmm" lub "SS.mmm" na ms. */
    private function parseTimeToMs(string $s): ?int
    {
        $s = trim($s);
        // ms separator: .
        if (!preg_match('/^(?:(\d+):)?(?:(\d+):)?(\d+)(?:\.(\d{1,3}))?$/', $s, $m)) {
            return null;
        }
        // Possible: [h]:[m]:s[.ms] or [m]:s[.ms] or s[.ms]
        $part1 = $m[1] ?? '';
        $part2 = $m[2] ?? '';
        $sec   = (int)$m[3];
        $ms    = isset($m[4]) ? (int)str_pad($m[4], 3, '0', STR_PAD_RIGHT) : 0;
        $h = $min = 0;
        if ($part1 !== '' && $part2 !== '') { $h = (int)$part1; $min = (int)$part2; }
        elseif ($part1 !== '')              { $min = (int)$part1; }
        $total = (($h * 3600) + ($min * 60) + $sec) * 1000 + $ms;
        return $total;
    }

    private function listTournaments(): array
    {
        $clubId = ClubContext::current();
        if ($clubId === null) return [];
        $stmt = Database::pdo()->prepare(
            "SELECT id, name, date_start, sport_key, status
             FROM tournaments
             WHERE club_id = ?
             ORDER BY date_start DESC
             LIMIT 100"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
