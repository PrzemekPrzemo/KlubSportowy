<?php
namespace App\Sports\Rowing\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SportResultsCrudTrait;
use App\Models\MemberModel;
use App\Sports\Rowing\Models\RowingResultModel;

class ResultsController extends BaseController
{
    use SportResultsCrudTrait;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    protected function crudConfig(): array
    {
        return [
            'model'         => new RowingResultModel(),
            'table'         => 'rowing_results',
            'index_route'   => 'rowing/results',
            'view_prefix'   => 'rowing/results',
            'title_show'    => 'Szczegóły wyniku — Wioślarstwo',
            'title_edit'    => 'Edytuj wynik — Wioślarstwo',
            'extra_selects' => [
                'category'  => ['label' => 'Kategoria', 'options' => RowingResultModel::$CATEGORIES],
                'boat_type' => ['label' => 'Łódź',      'options' => RowingResultModel::$BOAT_TYPES],
            ],
        ];
    }

    public function index(): void
    {
        $this->render('rowing/results/index', [
            'title'      => 'Wyniki zawodów — Wioślarstwo',
            'results'    => (new RowingResultModel())->listForClub(),
            'members'    => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'categories' => RowingResultModel::$CATEGORIES,
            'boatTypes'  => RowingResultModel::$BOAT_TYPES,
            'distances'  => RowingResultModel::$DISTANCES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('rowing/results');
        }
        $category = array_key_exists($_POST['category'] ?? '', RowingResultModel::$CATEGORIES)
            ? $_POST['category'] : null;
        $boatType = array_key_exists($_POST['boat_type'] ?? '', RowingResultModel::$BOAT_TYPES)
            ? $_POST['boat_type'] : null;
        $distanceM = in_array((int)($_POST['distance_m'] ?? 0), RowingResultModel::$DISTANCES, true)
            ? (int)$_POST['distance_m'] : null;
        $timeMs = null;
        if (isset($_POST['time_min']) || isset($_POST['time_sec'])) {
            $min = (int)($_POST['time_min'] ?? 0);
            $sec = (int)($_POST['time_sec'] ?? 0);
            $cs  = (int)($_POST['time_cs'] ?? 0);
            if ($min > 0 || $sec > 0 || $cs > 0) {
                $timeMs = ($min * 60 + $sec) * 1000 + $cs * 10;
            }
        }
        (new RowingResultModel())->insert([
            'member_id'        => $memberId,
            'competition_name' => trim($_POST['competition_name'] ?? ''),
            'competition_date' => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'age_category'     => trim($_POST['age_category'] ?? '') ?: null,
            'time_ms'          => $timeMs,
            'distance_m'       => $distanceM,
            'boat_type'        => $boatType,
            'category'         => $category,
            'placement'        => !empty($_POST['placement']) ? (int)$_POST['placement'] : null,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('rowing/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new RowingResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('rowing/results');
    }
}
