<?php

namespace App\Sports\Esport\Controllers;

use App\Controllers\BaseController;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Sports\Esport\Models\EsportGameModel;
use App\Sports\Esport\Models\EsportMemberProfileModel;

/**
 * Portal zawodnika — moje profile esportowe (per gra) + leaderboard.
 *
 *   GET  /portal/esport/profiles                     — moje gry (lista profili)
 *   POST /portal/esport/profiles/save                — zapis/update profilu (per gra)
 *   GET  /portal/esport/leaderboard/:gameCode        — leaderboard w klubie
 */
class PortalEsportController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        MemberAuth::requireLogin();
    }

    private function memberClubId(): int
    {
        $memberClubId = MemberAuth::clubId();
        if ($memberClubId !== null) return (int)$memberClubId;
        $m = MemberAuth::member();
        return (int)($m['club_id'] ?? 0);
    }

    public function myProfiles(): void
    {
        $memberId = (int)MemberAuth::id();
        $clubId   = $this->memberClubId();

        // Ustaw ClubContext aby ClubScopedModel filtrowal poprawnie.
        if ($clubId > 0) {
            ClubContext::set($clubId);
        }

        $profiles = (new EsportMemberProfileModel())->listForMember($memberId);
        $games    = (new EsportGameModel())->listAvailableForClub();

        $this->view->setLayout('portal');
        $this->view->render('esport/portal/my_profiles', [
            'title'     => 'Moje gry esportowe',
            'profiles'  => $profiles,
            'games'     => $games,
            'platforms' => EsportGameModel::PLATFORMS,
        ]);
    }

    public function saveProfile(): void
    {
        Csrf::verify();
        $memberId = (int)MemberAuth::id();
        $clubId   = $this->memberClubId();
        if ($clubId > 0) {
            ClubContext::set($clubId);
        }

        $gameCode = strtolower(trim($_POST['game_code'] ?? ''));
        if (!preg_match('/^[a-z0-9_]{2,50}$/', $gameCode)) {
            Session::flash('error', 'Wybierz prawidlowa gre z listy.');
            $this->redirect('portal/esport/profiles');
        }

        // Sprawdz, ze gra istnieje (globalna lub klubowa).
        $game = (new EsportGameModel())->findByCode($gameCode);
        if ($game === null) {
            Session::flash('error', 'Wybrana gra nie jest dostepna w klubie.');
            $this->redirect('portal/esport/profiles');
        }

        $ign = trim($_POST['in_game_name'] ?? '');
        if ($ign === '' || mb_strlen($ign) > 200) {
            Session::flash('error', 'Podaj nick w grze (in-game name).');
            $this->redirect('portal/esport/profiles');
        }

        (new EsportMemberProfileModel())->upsertProfile($memberId, $gameCode, [
            'in_game_name' => $ign,
            'platform'     => $_POST['platform'] ?? 'pc',
            'rank_tier'    => $_POST['rank_tier'] ?? null,
            'stream_url'   => $_POST['stream_url'] ?? null,
        ]);

        Session::flash('success', 'Profil zapisany.');
        $this->redirect('portal/esport/profiles');
    }

    public function leaderboard(string $gameCode): void
    {
        $clubId = $this->memberClubId();
        if ($clubId > 0) {
            ClubContext::set($clubId);
        }
        if (!preg_match('/^[a-z0-9_]{2,50}$/', $gameCode)) {
            Session::flash('error', 'Nieprawidlowy kod gry.');
            $this->redirect('portal/esport/profiles');
        }
        $game = (new EsportGameModel())->findByCode($gameCode);
        if ($game === null) {
            Session::flash('error', 'Gra nie istnieje.');
            $this->redirect('portal/esport/profiles');
        }
        $top = (new EsportMemberProfileModel())->leaderboard($gameCode, 20);

        $this->view->setLayout('portal');
        $this->view->render('esport/portal/leaderboard', [
            'title' => 'Leaderboard — ' . $game['display_name'],
            'game'  => $game,
            'top'   => $top,
        ]);
    }
}
