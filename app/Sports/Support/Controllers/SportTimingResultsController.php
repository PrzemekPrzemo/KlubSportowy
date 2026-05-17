<?php

namespace App\Sports\Support\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SportModuleLoader;
use App\Models\MemberModel;
use App\Sports\Support\SportTimingResultModel;

/**
 * Wspólny kontroler wyników dla wszystkich timing-sportów
 * (swimming, cycling, rowing, triathlon, biathlon, alpineski, xcski,
 * skijump, snowboard, rollerskating, kayaking).
 *
 * Sport rozpoznawany po segmencie URL `{key}` — np.
 *   /club/sport/swimming/results
 *   /club/sport/cycling/result/create
 *   /club/sport/triathlon/result/12/verify  (admin POST)
 */
class SportTimingResultsController extends BaseController
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
        $model    = new SportTimingResultModel();
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $event    = $_GET['event'] ?? null;
        $memberId = !empty($_GET['member_id']) ? (int)$_GET['member_id'] : null;
        $pagination = $model->listForClubSport($key, $memberId, $event, $page, 30);

        $this->render('sport/timing/results_list', [
            'title'      => 'Wyniki — ' . ($manifest['name'] ?? $key),
            'sportKey'   => $key,
            'manifest'   => $manifest,
            'pagination' => $pagination,
            'events'     => $model->eventNames($key),
            'eventFilter' => $event,
            'memberFilter' => $memberId,
        ]);
    }

    public function create(string $key): void
    {
        $manifest = $this->resolveSport($key);
        $members  = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('sport/timing/result_form', [
            'title'    => 'Nowy wynik — ' . ($manifest['name'] ?? $key),
            'sportKey' => $key,
            'manifest' => $manifest,
            'members'  => $members,
            'result'   => null,
        ]);
    }

    public function store(string $key): void
    {
        Csrf::verify();
        $this->resolveSport($key);

        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('club/sport/' . $key . '/result/create');
        }

        $eventName = trim((string)($_POST['event_name'] ?? ''));
        if ($eventName === '') {
            Session::flash('error', 'Podaj nazwę konkurencji.');
            $this->redirect('club/sport/' . $key . '/result/create');
        }

        $distanceM    = max(0, (int)($_POST['distance_m'] ?? 0));
        $finishTimeMs = $this->parseTimeMs($_POST);
        if ($finishTimeMs <= 0) {
            Session::flash('error', 'Wprowadź prawidłowy czas.');
            $this->redirect('club/sport/' . $key . '/result/create');
        }

        // splits_json: kolejne czasy linia po linii (ms)
        $splitsJson = null;
        $splitsRaw  = trim((string)($_POST['splits_raw'] ?? ''));
        if ($splitsRaw !== '') {
            $lines  = preg_split('/\r?\n/', $splitsRaw) ?: [];
            $splits = [];
            foreach ($lines as $ln) {
                $ln = trim($ln);
                if ($ln === '') continue;
                if (preg_match('/^([0-9]+):([0-9]{2})(?:\.([0-9]{1,2}))?$/', $ln, $mt)) {
                    $cs   = isset($mt[3]) ? (int)str_pad($mt[3], 2, '0') : 0;
                    $splits[] = ((int)$mt[1] * 60 + (int)$mt[2]) * 1000 + $cs * 10;
                } elseif (is_numeric($ln)) {
                    $splits[] = (int)$ln;
                }
            }
            if ($splits) $splitsJson = json_encode($splits, JSON_UNESCAPED_UNICODE);
        }

        $metaJson = null;
        $metaRaw  = trim((string)($_POST['metadata_json'] ?? ''));
        if ($metaRaw !== '') {
            $decoded = json_decode($metaRaw, true);
            if (is_array($decoded)) {
                $metaJson = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
        }

        $model = new SportTimingResultModel();
        $model->insertScoped([
            'tournament_id'      => !empty($_POST['tournament_id']) ? (int)$_POST['tournament_id'] : null,
            'training_id'        => !empty($_POST['training_id']) ? (int)$_POST['training_id'] : null,
            'member_id'          => $memberId,
            'sport_key'          => $key,
            'event_name'         => $eventName,
            'distance_m'         => $distanceM,
            'finish_time_ms'     => $finishTimeMs,
            'splits_json'        => $splitsJson,
            'penalties_seconds'  => (float)($_POST['penalties_seconds'] ?? 0),
            'rank'               => !empty($_POST['rank']) ? (int)$_POST['rank'] : null,
            'category'           => trim((string)($_POST['category'] ?? '')) ?: null,
            'weather_conditions' => trim((string)($_POST['weather_conditions'] ?? '')) ?: null,
            'recorded_at'        => trim((string)($_POST['recorded_at'] ?? '')) ?: date('Y-m-d'),
            'verified'           => 0,
            'metadata_json'      => $metaJson,
        ]);

        Session::flash('success', 'Wynik zapisany (oczekuje weryfikacji).');
        $this->redirect('club/sport/' . $key . '/results');
    }

    public function verify(string $key, string $id): void
    {
        Csrf::verify();
        $this->resolveSport($key);

        if (!\App\Helpers\Auth::isSuperAdmin() && !in_array(\App\Helpers\Auth::role(), ['zarzad','trener','admin'], true)) {
            Session::flash('error', 'Brak uprawnień do weryfikacji wyniku.');
            $this->redirect('club/sport/' . $key . '/results');
        }

        $model = new SportTimingResultModel();
        if (!$model->findInClub((int)$id)) {
            Session::flash('error', 'Wynik nie istnieje w tym klubie.');
            $this->redirect('club/sport/' . $key . '/results');
        }
        $model->verify((int)$id);
        Session::flash('success', 'Wynik zweryfikowany.');
        $this->redirect('club/sport/' . $key . '/results');
    }

    public function delete(string $key, string $id): void
    {
        Csrf::verify();
        $this->resolveSport($key);

        $model = new SportTimingResultModel();
        $model->deleteInClub((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('club/sport/' . $key . '/results');
    }

    private function parseTimeMs(array $post): int
    {
        if (isset($post['finish_time_ms']) && $post['finish_time_ms'] !== '') {
            return max(0, (int)$post['finish_time_ms']);
        }
        $min = max(0, (int)($post['time_min'] ?? 0));
        $sec = max(0, min(59, (int)($post['time_sec'] ?? 0)));
        $cs  = max(0, min(99, (int)($post['time_cs'] ?? 0)));
        return ($min * 60 + $sec) * 1000 + $cs * 10;
    }
}
