<?php

namespace App\Sports\Dance\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Dance\Models\DanceJudgeScoreModel;
use App\Sports\Dance\Models\DanceMemberStyleModel;
use App\Sports\Dance\Models\DancePerformanceModel;
use App\Sports\Dance\Models\DanceStyleModel;

/**
 * Panel klubowy — taniec:
 *   GET  /club/dance/styles                       — katalog stylow
 *   POST /club/dance/styles/store                 — dodanie wlasnego stylu klubowego
 *   POST /club/dance/styles/:id/deactivate        — dezaktywacja stylu klubowego
 *   GET  /club/dance/members                      — lista zawodnikow z przypisanymi stylami
 *   POST /club/dance/members/assign               — przypisanie stylu zawodnikowi
 *   GET  /club/dance/performances                 — historia wystepow
 *   POST /club/dance/performances/store           — nowy wystep
 *   POST /club/dance/performances/:id/judge       — dodanie oceny sedziego
 */
class ClubDanceController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function styles(): void
    {
        $this->render('dance/club/styles', [
            'title'      => 'Taniec — Katalog stylow',
            'styles'     => (new DanceStyleModel())->listAvailableForClub(),
            'categories' => DanceStyleModel::CATEGORIES,
        ]);
    }

    public function storeStyle(): void
    {
        Csrf::verify();
        $code = strtolower(trim($_POST['style_code'] ?? ''));
        $name = trim($_POST['display_name'] ?? '');
        if ($name === '' || !preg_match('/^[a-z0-9_]{2,50}$/', $code)) {
            Session::flash('error', 'Podaj kod stylu (a-z, 0-9, _) i nazwe.');
            $this->redirect('club/dance/styles');
        }
        (new DanceStyleModel())->addClubStyle([
            'style_code'   => $code,
            'display_name' => $name,
            'category'     => $_POST['category'] ?? 'other',
        ]);
        Session::flash('success', 'Styl dodany.');
        $this->redirect('club/dance/styles');
    }

    public function deactivateStyle(string $id): void
    {
        Csrf::verify();
        (new DanceStyleModel())->deactivate((int)$id);
        Session::flash('success', 'Styl dezaktywowany.');
        $this->redirect('club/dance/styles');
    }

    public function members(): void
    {
        $styleCode = isset($_GET['style']) ? (string)$_GET['style'] : null;
        if ($styleCode !== null && !preg_match('/^[a-z0-9_]{2,50}$/', $styleCode)) {
            $styleCode = null;
        }
        $this->render('dance/club/members', [
            'title'   => 'Taniec — Zawodnicy',
            'rows'    => (new DanceMemberStyleModel())->listForClub($styleCode),
            'members' => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'styles'  => (new DanceStyleModel())->listAvailableForClub(),
            'levels'  => DanceStyleModel::LEVELS,
            'currentStyle' => $styleCode,
        ]);
    }

    public function assignMember(): void
    {
        Csrf::verify();
        $memberId  = (int)($_POST['member_id'] ?? 0);
        $styleCode = strtolower(trim($_POST['style_code'] ?? ''));
        if ($memberId <= 0 || !preg_match('/^[a-z0-9_]{2,50}$/', $styleCode)) {
            Session::flash('error', 'Wybierz zawodnika i styl.');
            $this->redirect('club/dance/members');
        }
        $style = (new DanceStyleModel())->findByCode($styleCode);
        if ($style === null) {
            Session::flash('error', 'Styl nie istnieje.');
            $this->redirect('club/dance/members');
        }
        (new DanceMemberStyleModel())->upsert($memberId, $styleCode, [
            'level'             => $_POST['level'] ?? 'beginner',
            'partner_member_id' => $_POST['partner_member_id'] ?? null,
        ]);
        Session::flash('success', 'Styl przypisany.');
        $this->redirect('club/dance/members');
    }

    public function performances(): void
    {
        $this->render('dance/club/performances', [
            'title'        => 'Taniec — Wystepy',
            'performances' => (new DancePerformanceModel())->listForClub(),
            'members'      => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'styles'       => (new DanceStyleModel())->listAvailableForClub(),
            'tournaments'  => $this->listTournaments(),
        ]);
    }

    public function storePerformance(): void
    {
        Csrf::verify();
        $tournamentId = (int)($_POST['tournament_id'] ?? 0);
        $memberId     = (int)($_POST['member_id'] ?? 0);
        $styleCode    = strtolower(trim($_POST['style_code'] ?? ''));
        if ($tournamentId <= 0 || $memberId <= 0 || !preg_match('/^[a-z0-9_]{2,50}$/', $styleCode)) {
            Session::flash('error', 'Wymagane: turniej, zawodnik, styl.');
            $this->redirect('club/dance/performances');
        }
        $partnerId = isset($_POST['partner_member_id']) && (int)$_POST['partner_member_id'] > 0
            ? (int)$_POST['partner_member_id'] : null;
        $perfNo    = isset($_POST['performance_number']) && $_POST['performance_number'] !== ''
            ? (int)$_POST['performance_number'] : null;

        (new DancePerformanceModel())->insert([
            'tournament_id'      => $tournamentId,
            'member_id'          => $memberId,
            'partner_member_id'  => $partnerId,
            'style_code'         => $styleCode,
            'performance_number' => $perfNo,
            'total_score'        => null,
            'rank'               => null,
        ]);
        Session::flash('success', 'Wystep zarejestrowany — dodaj oceny sedziow.');
        $this->redirect('club/dance/performances');
    }

    public function addJudgeScore(string $id): void
    {
        Csrf::verify();
        $performanceId = (int)$id;
        $perfModel = new DancePerformanceModel();
        // Sprawdz, czy wystep nalezy do klubu (ClubScopedModel filtruje przez findById? — findById brak scope).
        // Wykonujemy explicit query.
        $clubId = (new MemberModel())->getDb()->prepare(
            "SELECT id FROM sport_dance_performances WHERE id = ? AND club_id = ?"
        );
        $clubId->execute([$performanceId, \App\Helpers\ClubContext::current()]);
        if (!$clubId->fetchColumn()) {
            Session::flash('error', 'Brak dostepu do wystepu.');
            $this->redirect('club/dance/performances');
        }
        (new DanceJudgeScoreModel())->addScore($performanceId, [
            'judge_name'       => $_POST['judge_name'] ?? 'Sedzia',
            'technique_score'  => $_POST['technique_score']  ?? null,
            'artistry_score'   => $_POST['artistry_score']   ?? null,
            'difficulty_score' => $_POST['difficulty_score'] ?? null,
            'notes'            => $_POST['notes'] ?? null,
        ]);
        $perfModel->recomputeTotal($performanceId);
        Session::flash('success', 'Ocena dodana — przeliczono wynik.');
        $this->redirect('club/dance/performances');
    }

    private function listTournaments(): array
    {
        $pdo = \App\Helpers\Database::pdo();
        $clubId = \App\Helpers\ClubContext::current();
        if ($clubId === null) return [];
        $stmt = $pdo->prepare(
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
