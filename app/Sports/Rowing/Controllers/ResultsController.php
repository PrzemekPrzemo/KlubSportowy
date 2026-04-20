<?php
namespace App\Sports\Rowing\Controllers;
use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Rowing\Models\RowingResultModel;
class ResultsController extends BaseController {
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }
    public function index(): void {
        $this->render('rowing/results/index', ['title'=>'Wyniki zawodów — Wioślarstwo','results'=>(new RowingResultModel())->listForClub(),'members'=>(new MemberModel())->search('','aktywny',null,1,500)['data']??[],'categories'=>RowingResultModel::$CATEGORIES]);
    }
    public function store(): void {
        Csrf::verify();
        $memberId = (int)($_POST['member_id']??0);
        if($memberId<=0){Session::flash('error','Wybierz zawodnika.');$this->redirect('rowing/results');}
        $category = array_key_exists($_POST['category']??'',RowingResultModel::$CATEGORIES)?$_POST['category']:null;
        (new RowingResultModel())->insert(['member_id'=>$memberId,'competition_name'=>trim($_POST['competition_name']??''),'competition_date'=>trim($_POST['competition_date']??'')?:date('Y-m-d'),'age_category'=>trim($_POST['age_category']??'')?:null,'category'=>$category,'placement'=>!empty($_POST['placement'])?(int)$_POST['placement']:null,'notes'=>trim($_POST['notes']??'')?:null]);
        Session::flash('success','Wynik dodany.');$this->redirect('rowing/results');
    }
    public function delete(string $id): void { Csrf::verify();(new RowingResultModel())->delete((int)$id);Session::flash('success','Usunięto.');$this->redirect('rowing/results'); }
}
