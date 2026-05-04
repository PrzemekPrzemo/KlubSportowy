<?php

namespace App\Sports\DanceSport\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\DanceSport\Models\DanceCoupleModel;

class CouplesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $couples = (new DanceCoupleModel())->listForClub();
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('dance_sport/couples/index', [
            'title'       => 'Pary taneczne — Taniec sportowy',
            'couples'     => $couples,
            'members'     => $members,
            'disciplines' => DanceCoupleModel::$DISCIPLINES,
            'classes'     => DanceCoupleModel::$CLASSES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $leaderId   = (int)($_POST['leader_id'] ?? 0);
        $followerId = (int)($_POST['follower_id'] ?? 0) ?: null;

        if ($leaderId <= 0) {
            Session::flash('error', 'Wybierz tancerza prowadzącego.');
            $this->redirect('dance_sport/couples');
        }

        $discipline = array_key_exists($_POST['discipline'] ?? '', DanceCoupleModel::$DISCIPLINES)
                        ? $_POST['discipline'] : 'standard';
        $class      = array_key_exists($_POST['class_level'] ?? '', DanceCoupleModel::$CLASSES)
                        ? $_POST['class_level'] : 'D';

        (new DanceCoupleModel())->insert([
            'leader_id'   => $leaderId,
            'follower_id' => $followerId,
            'couple_name' => trim($_POST['couple_name'] ?? '') ?: null,
            'class_level' => $class,
            'discipline'  => $discipline,
            'active_from' => trim($_POST['active_from'] ?? '') ?: date('Y-m-d'),
            'active_to'   => trim($_POST['active_to'] ?? '') ?: null,
            'status'      => 'active',
            'notes'       => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Para dodana.');
        $this->redirect('dance_sport/couples');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new DanceCoupleModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('dance_sport/couples');
    }
}
