<?php
namespace App\Sports\TableTennis\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SportResultsCrudTrait;
use App\Models\MemberModel;
use App\Sports\TableTennis\Models\TableTennisResultModel;

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
            'model'         => new TableTennisResultModel(),
            'table'         => 'table_tennis_results',
            'index_route'   => 'table_tennis/results',
            'view_prefix'   => 'table_tennis/results',
            'title_show'    => 'Szczegóły wyniku — Tenis stołowy',
            'title_edit'    => 'Edytuj wynik — Tenis stołowy',
            'extra_selects' => [
                'category'     => ['label' => 'Kategoria',       'options' => TableTennisResultModel::$CATEGORIES],
                'league_class' => ['label' => 'Klasa rozgrywek', 'options' => TableTennisResultModel::$LEAGUE_CLASSES],
            ],
        ];
    }

    public function index(): void
    {
        $this->render('table_tennis/results/index', [
            'title'         => 'Wyniki zawodów — Tenis stołowy',
            'results'       => (new TableTennisResultModel())->listForClub(),
            'members'       => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'categories'    => TableTennisResultModel::$CATEGORIES,
            'leagueClasses' => TableTennisResultModel::$LEAGUE_CLASSES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('table_tennis/results');
        }
        $category = array_key_exists($_POST['category'] ?? '', TableTennisResultModel::$CATEGORIES)
            ? $_POST['category'] : null;
        $leagueClass = array_key_exists($_POST['league_class'] ?? '', TableTennisResultModel::$LEAGUE_CLASSES)
            ? $_POST['league_class'] : null;
        (new TableTennisResultModel())->insert([
            'member_id'             => $memberId,
            'competition_name'      => trim($_POST['competition_name'] ?? ''),
            'competition_date'      => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'age_category'          => trim($_POST['age_category'] ?? '') ?: null,
            'category'              => $category,
            'sets_won'              => isset($_POST['sets_won']) && $_POST['sets_won'] !== '' ? (int)$_POST['sets_won'] : null,
            'sets_lost'             => isset($_POST['sets_lost']) && $_POST['sets_lost'] !== '' ? (int)$_POST['sets_lost'] : null,
            'ranking_points_before' => isset($_POST['ranking_points_before']) && $_POST['ranking_points_before'] !== '' ? (int)$_POST['ranking_points_before'] : null,
            'ranking_points_after'  => isset($_POST['ranking_points_after']) && $_POST['ranking_points_after'] !== '' ? (int)$_POST['ranking_points_after'] : null,
            'league_class'          => $leagueClass,
            'placement'             => !empty($_POST['placement']) ? (int)$_POST['placement'] : null,
            'notes'                 => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('table_tennis/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new TableTennisResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('table_tennis/results');
    }
}
