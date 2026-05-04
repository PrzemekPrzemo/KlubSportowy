<?php

namespace App\Sports\Archery\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Archery\Models\ArcheryBowModel;

class BowsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $bows    = (new ArcheryBowModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('archery/bows/index', [
            'title'   => 'Sprzęt (łuki) — Łucznictwo',
            'bows'    => $bows,
            'members' => $members,
            'bowTypes' => ArcheryBowModel::$BOW_TYPES,
            'limbLengths' => ArcheryBowModel::$LIMB_LENGTHS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $bowType = $_POST['bow_type'] ?? 'recurve';
        if (!array_key_exists($bowType, ArcheryBowModel::$BOW_TYPES)) {
            $bowType = 'recurve';
        }

        $memberId = (int)($_POST['member_id'] ?? 0) ?: null;
        $ownedBy  = ($_POST['owned_by'] ?? 'club') === 'member' ? 'member' : 'club';

        (new ArcheryBowModel())->insert([
            'bow_type'    => $bowType,
            'brand'       => trim($_POST['brand'] ?? '') ?: null,
            'model'       => trim($_POST['model'] ?? '') ?: null,
            'draw_weight' => !empty($_POST['draw_weight']) ? (float)$_POST['draw_weight'] : null,
            'draw_length' => !empty($_POST['draw_length']) ? (float)$_POST['draw_length'] : null,
            'limb_length' => in_array($_POST['limb_length'] ?? '', ArcheryBowModel::$LIMB_LENGTHS, true)
                                ? $_POST['limb_length'] : null,
            'serial_no'   => trim($_POST['serial_no'] ?? '') ?: null,
            'owned_by'    => $ownedBy,
            'member_id'   => $memberId,
            'status'      => 'active',
            'notes'       => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Łuk dodany.');
        $this->redirect('archery/bows');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new ArcheryBowModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('archery/bows');
    }
}
