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
        $this->render('fencing/results/index', ['title'=>'Wyniki zawodów — Szermierka','results'=>(new FencingResultModel())->listForClub(),'members'=>(new MemberModel())->search('','aktywny',null,1,500)['data']??[],'categories'=>FencingResultModel::$CATEGORIES]);
    }
    public function store(): void {
        Csrf::verify();
        $memberId = (int)($_POST['member_id']??0);
        if($memberId<=0){Session::flash('error','Wybierz zawodnika.');$this->redirect('fencing/results');}
        $category = array_key_exists($_POST['category']??'',FencingResultModel::$CATEGORIES)?$_POST['category']:null;
        (new FencingResultModel())->insert(['member_id'=>$memberId,'competition_name'=>trim($_POST['competition_name']??''),'competition_date'=>trim($_POST['competition_date']??'')?:date('Y-m-d'),'age_category'=>trim($_POST['age_category']??'')?:null,'category'=>$category,'placement'=>!empty($_POST['placement'])?(int)$_POST['placement']:null,'notes'=>trim($_POST['notes']??'')?:null]);
        Session::flash('success','Wynik dodany.');$this->redirect('fencing/results');
    }
    public function delete(string $id): void { Csrf::verify();(new FencingResultModel())->delete((int)$id);Session::flash('success','Usunięto.');$this->redirect('fencing/results'); }
}
