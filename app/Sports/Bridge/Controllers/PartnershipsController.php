<?php

namespace App\Sports\Bridge\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Bridge\Models\BridgePartnershipModel;

class PartnershipsController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('bridge');
    }

    public function index(): void
    {
        $this->render('bridge/partnerships/index', [
            'title'        => 'Pary brydżowe',
            'partnerships' => (new BridgePartnershipModel())->listForClub(),
            'members'      => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'categories'   => BridgePartnershipModel::$CATEGORIES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $p1 = (int)($_POST['player1_id'] ?? 0);
        $p2 = (int)($_POST['player2_id'] ?? 0);
        if ($p1 <= 0 || $p2 <= 0 || $p1 === $p2) {
            Session::flash('error', 'Wybierz dwóch różnych zawodników.');
            $this->redirect('bridge/partnerships');
        }
        $cat = array_key_exists($_POST['category'] ?? '', BridgePartnershipModel::$CATEGORIES) ? $_POST['category'] : 'open';

        (new BridgePartnershipModel())->insert([
            'player1_id' => $p1,
            'player2_id' => $p2,
            'name'       => trim($_POST['name'] ?? '') ?: null,
            'category'   => $cat,
            'active'     => isset($_POST['active']) ? 1 : 0,
        ]);
        Session::flash('success', 'Para zapisana.');
        $this->redirect('bridge/partnerships');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new BridgePartnershipModel())->delete((int)$id);
        Session::flash('success', 'Para usunięta.');
        $this->redirect('bridge/partnerships');
    }
}
