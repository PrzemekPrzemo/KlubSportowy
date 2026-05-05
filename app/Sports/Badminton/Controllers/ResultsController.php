<?php
namespace App\Sports\Badminton\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Helpers\SportResultsCrudTrait;
use App\Models\MemberModel;
use App\Sports\Badminton\Models\BadmintonResultModel;

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
            'model'         => new BadmintonResultModel(),
            'table'         => 'badminton_results',
            'index_route'   => 'badminton/results',
            'view_prefix'   => 'badminton/results',
            'title_show'    => 'Szczegóły wyniku — Badminton',
            'title_edit'    => 'Edytuj wynik — Badminton',
            'extra_selects' => [
                'category' => ['label' => 'Kategoria', 'options' => BadmintonResultModel::$CATEGORIES],
            ],
        ];
    }

    public function index(): void
    {
        $this->render('badminton/results/index', [
            'title'      => 'Wyniki zawodów — Badminton',
            'results'    => (new BadmintonResultModel())->listForClub(),
            'members'    => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'categories' => BadmintonResultModel::$CATEGORIES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('badminton/results');
        }
        $category = array_key_exists($_POST['category'] ?? '', BadmintonResultModel::$CATEGORIES)
            ? $_POST['category'] : null;
        (new BadmintonResultModel())->insert([
            'member_id'             => $memberId,
            'competition_name'      => trim($_POST['competition_name'] ?? ''),
            'competition_date'      => trim($_POST['competition_date'] ?? '') ?: date('Y-m-d'),
            'age_category'          => trim($_POST['age_category'] ?? '') ?: null,
            'category'              => $category,
            'sets_won'              => isset($_POST['sets_won']) && $_POST['sets_won'] !== '' ? (int)$_POST['sets_won'] : null,
            'sets_lost'             => isset($_POST['sets_lost']) && $_POST['sets_lost'] !== '' ? (int)$_POST['sets_lost'] : null,
            'ranking_points_before' => isset($_POST['ranking_points_before']) && $_POST['ranking_points_before'] !== '' ? (int)$_POST['ranking_points_before'] : null,
            'ranking_points_after'  => isset($_POST['ranking_points_after']) && $_POST['ranking_points_after'] !== '' ? (int)$_POST['ranking_points_after'] : null,
            'placement'             => !empty($_POST['placement']) ? (int)$_POST['placement'] : null,
            'notes'                 => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Wynik dodany.');
        $this->redirect('badminton/results');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new BadmintonResultModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('badminton/results');
    }
}
