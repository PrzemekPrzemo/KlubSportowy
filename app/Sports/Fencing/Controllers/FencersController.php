<?php

namespace App\Sports\Fencing\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Fencing\Models\FencingFencerModel;

class FencersController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $weapon = $_GET['weapon'] ?? null;
        if ($weapon && !array_key_exists($weapon, FencingFencerModel::$WEAPONS)) $weapon = null;

        $model   = new FencingFencerModel();
        $fencers = $model->listForClub($weapon);
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $this->render('fencing/fencers/index', [
            'title'        => 'Szermierze — ranking klubu',
            'fencers'      => $fencers,
            'members'      => $members,
            'weapons'      => FencingFencerModel::$WEAPONS,
            'lateralities' => FencingFencerModel::$LATERALITIES,
            'weaponFilter' => $weapon,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('fencing/fencers');
        }

        $weapon = array_key_exists($_POST['primary_weapon'] ?? '', FencingFencerModel::$WEAPONS)
            ? $_POST['primary_weapon'] : 'foil';
        $lat = array_key_exists($_POST['laterality'] ?? '', FencingFencerModel::$LATERALITIES)
            ? $_POST['laterality'] : 'praworęczny';

        Database::pdo()->prepare(
            "INSERT INTO fencing_fencers (club_id, member_id, fie_id, primary_weapon, laterality, ranking_points, height_cm)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                fie_id = VALUES(fie_id),
                primary_weapon = VALUES(primary_weapon),
                laterality = VALUES(laterality),
                ranking_points = VALUES(ranking_points),
                height_cm = VALUES(height_cm)"
        )->execute([
            $this->currentClub(),
            $memberId,
            trim($_POST['fie_id'] ?? '') ?: null,
            $weapon,
            $lat,
            max(0, (int)($_POST['ranking_points'] ?? 0)),
            !empty($_POST['height_cm']) ? (int)$_POST['height_cm'] : null,
        ]);
        Session::flash('success', 'Profil szermierza zapisany.');
        $this->redirect('fencing/fencers');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new FencingFencerModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('fencing/fencers');
    }
}
