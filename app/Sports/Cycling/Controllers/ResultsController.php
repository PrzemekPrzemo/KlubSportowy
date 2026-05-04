<?php
namespace App\Sports\Cycling\Controllers;
use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Cycling\Models\CyclingResultModel;
class ResultsController extends BaseController {
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }
    public function index(): void {
        $this->render('cycling/results/index', ['title'=>'Wyniki zawodów — Kolarstwo','results'=>(new CyclingResultModel())->listForClub(),'members'=>(new MemberModel())->search('','aktywny',null,1,500)['data']??[],'categories'=>CyclingResultModel::$CATEGORIES,'raceTypes'=>CyclingResultModel::$RACE_TYPES,'uciCategories'=>CyclingResultModel::$UCI_CATEGORIES]);
    }
    public function store(): void {
        Csrf::verify();
        $memberId = (int)($_POST['member_id']??0);
        if($memberId<=0){Session::flash('error','Wybierz zawodnika.');$this->redirect('cycling/results');}
        $category = array_key_exists($_POST['category']??'',CyclingResultModel::$CATEGORIES)?$_POST['category']:null;
        $raceType = array_key_exists($_POST['race_type']??'',CyclingResultModel::$RACE_TYPES)?$_POST['race_type']:null;
        $uciCategory = in_array($_POST['uci_category']??'',CyclingResultModel::$UCI_CATEGORIES)?$_POST['uci_category']:null;
        $distanceKm = isset($_POST['distance_km']) && $_POST['distance_km']!=='' ? (float)$_POST['distance_km'] : null;
        $timeSeconds = isset($_POST['time_seconds']) && $_POST['time_seconds']!=='' ? (float)$_POST['time_seconds'] : null;
        (new CyclingResultModel())->insert(['member_id'=>$memberId,'competition_name'=>trim($_POST['competition_name']??''),'competition_date'=>trim($_POST['competition_date']??'')?:date('Y-m-d'),'age_category'=>trim($_POST['age_category']??'')?:null,'category'=>$category,'race_type'=>$raceType,'distance_km'=>$distanceKm,'uci_category'=>$uciCategory,'time_seconds'=>$timeSeconds,'placement'=>!empty($_POST['placement'])?(int)$_POST['placement']:null,'notes'=>trim($_POST['notes']??'')?:null]);
        Session::flash('success','Wynik dodany.');$this->redirect('cycling/results');
    }
    public function delete(string $id): void { Csrf::verify();(new CyclingResultModel())->delete((int)$id);Session::flash('success','Usunięto.');$this->redirect('cycling/results'); }
}
