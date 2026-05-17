<?php

namespace App\Controllers;

use App\Helpers\Bracket\BracketAdvancer;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SportModuleLoader;
use App\Models\MemberModel;
use App\Models\TournamentModel;
use App\Models\TournamentParticipantModel;

class TournamentsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $model    = new TournamentModel();
        $sportKey = $_GET['sport'] ?? null;
        $tournaments = $model->listForClub($sportKey ?: null);
        $sports      = SportModuleLoader::load();

        $this->render('tournaments/index', [
            'title'       => 'Turnieje',
            'tournaments' => $tournaments,
            'sports'      => $sports,
            'filterSport' => $sportKey,
        ]);
    }

    public function create(): void
    {
        $sports = SportModuleLoader::load();

        $this->render('tournaments/create', [
            'title'  => 'Nowy turniej',
            'sports' => $sports,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $name      = trim($_POST['name'] ?? '');
        $sportKey  = trim($_POST['sport_key'] ?? '');
        $format    = trim($_POST['format'] ?? 'single_elimination');
        $dateStart = trim($_POST['date_start'] ?? '');

        if ($name === '' || $sportKey === '' || $dateStart === '') {
            Session::flash('error', 'Wypełnij wszystkie wymagane pola.');
            $this->redirect('tournaments/create');
        }

        $allowed = ['single_elimination', 'double_elimination', 'round_robin'];
        if (!in_array($format, $allowed, true)) {
            $format = 'single_elimination';
        }

        (new TournamentModel())->insert([
            'sport_key'  => $sportKey,
            'name'       => $name,
            'format'     => $format,
            'date_start' => $dateStart,
            'status'     => 'draft',
        ]);

        Session::flash('success', 'Turniej został utworzony.');
        $this->redirect('tournaments');
    }

    public function show(string $id): void
    {
        $model      = new TournamentModel();
        $tournament = $model->withParticipants((int)$id);

        if (!$tournament) {
            Session::flash('error', 'Turniej nie został znaleziony.');
            $this->redirect('tournaments');
        }

        $brackets = $model->brackets((int)$id);

        // Group brackets by round
        $byRound = [];
        foreach ($brackets as $match) {
            $byRound[(int)$match['round']][] = $match;
        }

        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $sports  = SportModuleLoader::load();

        $this->render('tournaments/show', [
            'title'      => 'Turniej: ' . $tournament['name'],
            'tournament' => $tournament,
            'byRound'    => $byRound,
            'members'    => $members,
            'sports'     => $sports,
        ]);
    }

    public function addParticipant(string $id): void
    {
        Csrf::verify();

        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('tournaments/' . $id);
        }

        try {
            (new TournamentParticipantModel())->insert([
                'tournament_id' => (int)$id,
                'member_id'     => $memberId,
            ]);
            Session::flash('success', 'Zawodnik dodany do turnieju.');
        } catch (\Throwable) {
            Session::flash('error', 'Nie można dodać zawodnika (może już być zapisany).');
        }

        $this->redirect('tournaments/' . $id);
    }

    public function removeParticipant(string $id): void
    {
        Csrf::verify();

        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            $this->redirect('tournaments/' . $id);
        }

        $stmt = (new TournamentParticipantModel())->getDb()->prepare(
            "DELETE FROM tournament_participants WHERE tournament_id = ? AND member_id = ?"
        );
        $stmt->execute([(int)$id, $memberId]);

        Session::flash('success', 'Zawodnik usunięty z turnieju.');
        $this->redirect('tournaments/' . $id);
    }

    public function generateBracket(string $id): void
    {
        Csrf::verify();

        $model      = new TournamentModel();
        $tournament = $model->findById((int)$id);

        if (!$tournament) {
            Session::flash('error', 'Turniej nie istnieje.');
            $this->redirect('tournaments');
        }

        $model->generateBracket((int)$id);
        Session::flash('success', 'Drabinka została wygenerowana.');
        $this->redirect('tournaments/' . $id);
    }

    public function recordResult(string $matchId): void
    {
        Csrf::verify();

        $winnerId = (int)($_POST['winner_id'] ?? 0);
        $score1   = trim($_POST['score1'] ?? '');
        $score2   = trim($_POST['score2'] ?? '');

        if ($winnerId <= 0) {
            Session::flash('error', 'Wybierz zwycięzcę.');
            $this->redirect('tournaments');
        }

        // Get tournament_id from match
        $model = new TournamentModel();
        $stmt  = $model->getDb()->prepare(
            "SELECT tournament_id FROM tournament_matches WHERE id = ?"
        );
        $stmt->execute([(int)$matchId]);
        $match = $stmt->fetch();

        if (!$match) {
            Session::flash('error', 'Mecz nie istnieje.');
            $this->redirect('tournaments');
        }

        $model->recordResult((int)$matchId, $winnerId, $score1, $score2);

        // Posun zwyciezce do nastepnej rundy + opcjonalny SSE push do live channel.
        try {
            BracketAdvancer::advance((int)$matchId);
        } catch (\Throwable) {
            // Advancement nigdy nie crashuje zapisu wyniku.
        }

        Session::flash('success', 'Wynik zapisany.');
        $this->redirect('tournaments/' . $match['tournament_id']);
    }

    public function delete(string $id): void
    {
        Csrf::verify();

        (new TournamentModel())->delete((int)$id);
        Session::flash('success', 'Turniej usunięty.');
        $this->redirect('tournaments');
    }

    /**
     * POST /tournaments/:id/toggle-public-live
     *
     * Wlacza/wylacza publiczna strone live wynikow turnieju. Przy pierwszym
     * wlaczeniu generuje globalnie unikalny slug. Opcjonalnie ustawia flage
     * "pelne nazwiska" (default: inicjaly nazwiska tylko).
     */
    public function togglePublicLive(string $id): void
    {
        Csrf::verify();

        $model      = new TournamentModel();
        $tournament = $model->findById((int)$id);
        if (!$tournament) {
            Session::flash('error', 'Turniej nie istnieje.');
            $this->redirect('tournaments');
        }
        // Sanity check — multi-tenant: turniej musi nalezec do biezacego klubu.
        $clubId = \App\Helpers\ClubContext::current();
        if ($clubId === null || (int)$tournament['club_id'] !== (int)$clubId) {
            Session::flash('error', 'Brak dostepu do turnieju.');
            $this->redirect('tournaments');
        }

        $enable    = (int)($_POST['enable'] ?? 0) === 1;
        $fullNames = (int)($_POST['full_names'] ?? 0) === 1;

        $slug = $tournament['public_live_slug'] ?? null;
        if ($enable && empty($slug)) {
            try {
                $slug = $model->generatePublicLiveSlug((int)$id);
            } catch (\Throwable $e) {
                Session::flash('error', 'Nie udalo sie wygenerowac slug-a: ' . $e->getMessage());
                $this->redirect('tournaments/' . (int)$id);
            }
        }

        $stmt = $model->getDb()->prepare(
            "UPDATE tournaments
             SET public_live_enabled = ?, public_live_slug = ?, public_live_full_names = ?
             WHERE id = ? AND club_id = ?"
        );
        $stmt->execute([
            $enable ? 1 : 0,
            $slug,
            $fullNames ? 1 : 0,
            (int)$id,
            (int)$clubId,
        ]);

        // Audit — uzywamy tenant_access_log gdy jest dostepny.
        try {
            $log = new \App\Models\TenantAccessLogModel();
            $log->logBypass(
                'tournaments',
                $enable ? 'enable_public_live' : 'disable_public_live',
                __FILE__,
                __LINE__,
                self::class
            );
        } catch (\Throwable) {}

        Session::flash(
            'success',
            $enable
                ? 'Publiczna strona live wlaczona. Link: ' . url('live/' . $slug)
                : 'Publiczna strona live wylaczona.'
        );
        $this->redirect('tournaments/' . (int)$id);
    }
}
