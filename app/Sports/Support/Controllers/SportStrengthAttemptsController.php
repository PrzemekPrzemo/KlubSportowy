<?php

namespace App\Sports\Support\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SportModuleLoader;
use App\Models\MemberModel;
use App\Sports\Support\SportStrengthAttemptModel;
use App\Sports\Support\SportStrengthMemberModel;

/**
 * Wspólny kontroler podejść dla strength sportów
 * (powerlifting, strongman, weightlifting).
 *
 * URL-e (key = powerlifting | strongman | weightlifting):
 *   /club/sport/{key}/attempts
 *   /club/sport/{key}/attempt/store              POST
 *   /club/sport/{key}/attempt/:id/delete         POST
 *   /club/sport/{key}/tournament/:id/scoreboard  GET (live leaderboard)
 */
class SportStrengthAttemptsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    private function resolveSport(string $key): array
    {
        $manifest = SportModuleLoader::get($key);
        if (!$manifest) {
            Session::flash('error', 'Nieznany sport: ' . $key);
            $this->redirect('dashboard');
        }
        $this->requireSportActive($key);
        return $manifest;
    }

    public function index(string $key): void
    {
        $manifest = $this->resolveSport($key);
        $memberId = !empty($_GET['member_id']) ? (int)$_GET['member_id'] : null;
        $liftType = $_GET['lift_type'] ?? null;

        $model    = new SportStrengthAttemptModel();
        $members  = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $attempts = $memberId
            ? $model->listForMember($memberId, $key, $liftType, 200)
            : [];

        $this->render('sport/strength/attempts_list', [
            'title'      => 'Podejścia — ' . ($manifest['name'] ?? $key),
            'sportKey'   => $key,
            'manifest'   => $manifest,
            'attempts'   => $attempts,
            'members'    => $members,
            'memberId'   => $memberId,
            'liftType'   => $liftType,
            'liftTypes'  => $this->liftTypesForSport($key),
        ]);
    }

    public function store(string $key): void
    {
        Csrf::verify();
        $this->resolveSport($key);

        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('club/sport/' . $key . '/attempts');
        }
        $liftType = trim((string)($_POST['lift_type'] ?? ''));
        if ($liftType === '') {
            Session::flash('error', 'Wybierz rodzaj podejścia.');
            $this->redirect('club/sport/' . $key . '/attempts');
        }

        $model = new SportStrengthAttemptModel();
        $model->insertScoped([
            'member_id'      => $memberId,
            'sport_key'      => $key,
            'tournament_id'  => !empty($_POST['tournament_id']) ? (int)$_POST['tournament_id'] : null,
            'lift_type'      => $liftType,
            'attempt_number' => max(1, min(99, (int)($_POST['attempt_number'] ?? 1))),
            'weight_kg'      => isset($_POST['weight_kg']) && $_POST['weight_kg'] !== '' ? (float)$_POST['weight_kg'] : null,
            'reps'           => max(1, (int)($_POST['reps'] ?? 1)),
            'success'        => isset($_POST['success']) ? 1 : 0,
            'notes'          => trim((string)($_POST['notes'] ?? '')) ?: null,
        ]);

        // Auto-update PB w sport_strength_member jeśli to udane podejście
        if (isset($_POST['success']) && in_array($liftType, ['squat','bench','deadlift'], true)) {
            $weight = (float)$_POST['weight_kg'];
            if ($weight > 0) {
                $strengthMember = new SportStrengthMemberModel();
                $existing = $strengthMember->findForMember($memberId);
                $field = $liftType . '_pb_kg';
                $current = $existing[$field] ?? null;
                if ($current === null || (float)$current < $weight) {
                    $strengthMember->upsert($memberId, $key, [$field => $weight]);
                }
            }
        }

        Session::flash('success', 'Podejście zapisane.');
        $this->redirect('club/sport/' . $key . '/attempts?member_id=' . $memberId);
    }

    public function delete(string $key, string $id): void
    {
        Csrf::verify();
        $this->resolveSport($key);
        (new SportStrengthAttemptModel())->deleteInClub((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('club/sport/' . $key . '/attempts');
    }

    public function scoreboard(string $key, string $tournamentId): void
    {
        $manifest = $this->resolveSport($key);
        $model    = new SportStrengthAttemptModel();
        $rows     = $model->tournamentScoreboard((int)$tournamentId, $key);
        $attempts = $model->listForTournament((int)$tournamentId, $key);

        $this->render('sport/strength/scoreboard', [
            'title'        => 'Live scoreboard — ' . ($manifest['name'] ?? $key),
            'sportKey'     => $key,
            'manifest'     => $manifest,
            'tournamentId' => (int)$tournamentId,
            'rows'         => $rows,
            'attempts'     => $attempts,
        ]);
    }

    private function liftTypesForSport(string $key): array
    {
        if ($key === 'powerlifting') {
            return ['squat' => 'Przysiad', 'bench' => 'Wyciskanie', 'deadlift' => 'Martwy ciąg'];
        }
        if ($key === 'weightlifting') {
            return ['snatch' => 'Rwanie', 'clean_jerk' => 'Podrzut'];
        }
        // strongman
        return [
            'deadlift'     => 'Deadlift',
            'log_press'    => 'Log press',
            'yoke'         => 'Yoke walk',
            'atlas_stones' => 'Atlas stones',
            'farmers_walk' => 'Farmer\'s walk',
            'tire_flip'    => 'Tire flip',
        ];
    }
}
