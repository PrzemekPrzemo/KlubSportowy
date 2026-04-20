<?php
namespace App\Sports\Fencing\Controllers;
use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Fencing\Models\FencingResultModel;
class ResultsController extends BaseController {
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }
    public function index(): void {
        $this->render('fencing/results/index', ['title'=>'Wyniki zawodów — Szermierka','results'=>(new FencingResultModel())->listForClub(),'members'=>(new MemberModel())->search('','aktywny',null,1,500)['data']??[],'categories'=>FencingResultModel::$CATEGORIES,'rounds'=>FencingResultModel::$ROUNDS]);
    }
    public function store(): void {
        Csrf::verify();
        $memberId = (int)($_POST['member_id']??0);
        if($memberId<=0){Session::flash('error','Wybierz zawodnika.');$this->redirect('fencing/results');}
        $weapon = array_key_exists($_POST['weapon']??'',FencingResultModel::$CATEGORIES)?$_POST['weapon']:null;
        $category = $weapon;
        $roundReached = in_array($_POST['round_reached']??'',FencingResultModel::$ROUNDS)?$_POST['round_reached']:(trim($_POST['round_reached']??'')?:null);
        $rankingPoints = isset($_POST['ranking_points']) && $_POST['ranking_points']!=='' ? (int)$_POST['ranking_points'] : null;
        $teamEvent = !empty($_POST['team_event']) ? 1 : 0;
        (new FencingResultModel())->insert(['member_id'=>$memberId,'competition_name'=>trim($_POST['competition_name']??''),'competition_date'=>trim($_POST['competition_date']??'')?:date('Y-m-d'),'age_category'=>trim($_POST['age_category']??'')?:null,'category'=>$category,'weapon'=>$weapon,'round_reached'=>$roundReached,'ranking_points'=>$rankingPoints,'team_event'=>$teamEvent,'placement'=>!empty($_POST['placement'])?(int)$_POST['placement']:null,'notes'=>trim($_POST['notes']??'')?:null]);
        Session::flash('success','Wynik dodany.');$this->redirect('fencing/results');
    }
    public function delete(string $id): void { Csrf::verify();(new FencingResultModel())->delete((int)$id);Session::flash('success','Usunięto.');$this->redirect('fencing/results'); }
}
