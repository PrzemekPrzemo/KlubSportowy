<?php
namespace App\Sports\TableTennis\Controllers;
use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\TableTennis\Models\TableTennisResultModel;
class ResultsController extends BaseController {
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }
    public function index(): void {
        $this->render('table_tennis/results/index', ['title'=>'Wyniki zawodów — Tenis stołowy','results'=>(new TableTennisResultModel())->listForClub(),'members'=>(new MemberModel())->search('','aktywny',null,1,500)['data']??[],'categories'=>TableTennisResultModel::$CATEGORIES,'leagueClasses'=>TableTennisResultModel::$LEAGUE_CLASSES]);
    }
    public function store(): void {
        Csrf::verify();
        $memberId = (int)($_POST['member_id']??0);
        if($memberId<=0){Session::flash('error','Wybierz zawodnika.');$this->redirect('table_tennis/results');}
        $category = array_key_exists($_POST['category']??'',TableTennisResultModel::$CATEGORIES)?$_POST['category']:null;
        $leagueClass = array_key_exists($_POST['league_class']??'',TableTennisResultModel::$LEAGUE_CLASSES)?$_POST['league_class']:null;
        $setsWon = isset($_POST['sets_won']) && $_POST['sets_won']!=='' ? (int)$_POST['sets_won'] : null;
        $setsLost = isset($_POST['sets_lost']) && $_POST['sets_lost']!=='' ? (int)$_POST['sets_lost'] : null;
        $rankingBefore = isset($_POST['ranking_points_before']) && $_POST['ranking_points_before']!=='' ? (int)$_POST['ranking_points_before'] : null;
        $rankingAfter = isset($_POST['ranking_points_after']) && $_POST['ranking_points_after']!=='' ? (int)$_POST['ranking_points_after'] : null;
        (new TableTennisResultModel())->insert(['member_id'=>$memberId,'competition_name'=>trim($_POST['competition_name']??''),'competition_date'=>trim($_POST['competition_date']??'')?:date('Y-m-d'),'age_category'=>trim($_POST['age_category']??'')?:null,'category'=>$category,'sets_won'=>$setsWon,'sets_lost'=>$setsLost,'ranking_points_before'=>$rankingBefore,'ranking_points_after'=>$rankingAfter,'league_class'=>$leagueClass,'placement'=>!empty($_POST['placement'])?(int)$_POST['placement']:null,'notes'=>trim($_POST['notes']??'')?:null]);
        Session::flash('success','Wynik dodany.');$this->redirect('table_tennis/results');
    }
    public function delete(string $id): void { Csrf::verify();(new TableTennisResultModel())->delete((int)$id);Session::flash('success','Usunięto.');$this->redirect('table_tennis/results'); }
}
