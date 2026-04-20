<?php
namespace App\Sports\Climbing\Controllers;
use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Climbing\Models\ClimbingResultModel;
class ResultsController extends BaseController {
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }
    public function index(): void {
        $this->render('climbing/results/index', ['title'=>'Wyniki zawodów — Wspinaczka sportowa','results'=>(new ClimbingResultModel())->listForClub(),'members'=>(new MemberModel())->search('','aktywny',null,1,500)['data']??[],'categories'=>ClimbingResultModel::$CATEGORIES]);
    }
    public function store(): void {
        Csrf::verify();
        $memberId = (int)($_POST['member_id']??0);
        if($memberId<=0){Session::flash('error','Wybierz zawodnika.');$this->redirect('climbing/results');}
        $category = array_key_exists($_POST['category']??'',ClimbingResultModel::$CATEGORIES)?$_POST['category']:null;
        $difficultyGrade = trim($_POST['difficulty_grade']??'')?:null;
        $timeSeconds = isset($_POST['time_seconds']) && $_POST['time_seconds']!=='' ? (float)$_POST['time_seconds'] : null;
        $scoreTops = isset($_POST['score_tops']) && $_POST['score_tops']!=='' ? (int)$_POST['score_tops'] : null;
        $scoreZones = isset($_POST['score_zones']) && $_POST['score_zones']!=='' ? (int)$_POST['score_zones'] : null;
        (new ClimbingResultModel())->insert(['member_id'=>$memberId,'competition_name'=>trim($_POST['competition_name']??''),'competition_date'=>trim($_POST['competition_date']??'')?:date('Y-m-d'),'age_category'=>trim($_POST['age_category']??'')?:null,'category'=>$category,'difficulty_grade'=>$difficultyGrade,'time_seconds'=>$timeSeconds,'score_tops'=>$scoreTops,'score_zones'=>$scoreZones,'placement'=>!empty($_POST['placement'])?(int)$_POST['placement']:null,'notes'=>trim($_POST['notes']??'')?:null]);
        Session::flash('success','Wynik dodany.');$this->redirect('climbing/results');
    }
    public function delete(string $id): void { Csrf::verify();(new ClimbingResultModel())->delete((int)$id);Session::flash('success','Usunięto.');$this->redirect('climbing/results'); }
}
