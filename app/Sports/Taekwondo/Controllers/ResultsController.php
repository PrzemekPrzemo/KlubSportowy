<?php
namespace App\Sports\Taekwondo\Controllers;
use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Taekwondo\Models\TaekwondoResultModel;
class ResultsController extends BaseController {
    public function __construct() { parent::__construct(); $this->requireLogin(); $this->requireClubContext(); }
    public function index(): void {
        $this->render('taekwondo/results/index', ['title'=>'Wyniki zawodów — Taekwondo','results'=>(new TaekwondoResultModel())->listForClub(),'members'=>(new MemberModel())->search('','aktywny',null,1,500)['data']??[],'categories'=>TaekwondoResultModel::$CATEGORIES]);
    }
    public function store(): void {
        Csrf::verify();
        $memberId = (int)($_POST['member_id']??0);
        if($memberId<=0){Session::flash('error','Wybierz zawodnika.');$this->redirect('taekwondo/results');}
        $category = array_key_exists($_POST['category']??'',TaekwondoResultModel::$CATEGORIES)?$_POST['category']:null;
        (new TaekwondoResultModel())->insert(['member_id'=>$memberId,'competition_name'=>trim($_POST['competition_name']??''),'competition_date'=>trim($_POST['competition_date']??'')?:date('Y-m-d'),'age_category'=>trim($_POST['age_category']??'')?:null,'category'=>$category,'placement'=>!empty($_POST['placement'])?(int)$_POST['placement']:null,'notes'=>trim($_POST['notes']??'')?:null]);
        Session::flash('success','Wynik dodany.');$this->redirect('taekwondo/results');
    }
    public function delete(string $id): void { Csrf::verify();(new TaekwondoResultModel())->delete((int)$id);Session::flash('success','Usunięto.');$this->redirect('taekwondo/results'); }
}
