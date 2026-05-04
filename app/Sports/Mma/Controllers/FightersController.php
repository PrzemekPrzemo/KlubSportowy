<?php

namespace App\Sports\Mma\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Mma\Models\MmaFighterModel;

class FightersController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('mma');
    }

    public function index(): void
    {
        $this->render('mma/fighters/index', [
            'title'    => 'Zawodnicy MMA',
            'fighters' => (new MmaFighterModel())->listForClub(),
            'members'  => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'stances'  => MmaFighterModel::$STANCES,
            'styles'   => MmaFighterModel::$STYLES,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) { Session::flash('error', 'Wybierz zawodnika.'); $this->redirect('mma/fighters'); }
        $stance = array_key_exists($_POST['stance']        ?? '', MmaFighterModel::$STANCES) ? $_POST['stance'] : 'ortodox';
        $style  = array_key_exists($_POST['primary_style'] ?? '', MmaFighterModel::$STYLES)  ? $_POST['primary_style'] : 'mixed';

        Database::pdo()->prepare(
            "INSERT INTO mma_fighters (club_id, member_id, nickname, stance, weight_class, primary_style)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                nickname = VALUES(nickname),
                stance = VALUES(stance),
                weight_class = VALUES(weight_class),
                primary_style = VALUES(primary_style)"
        )->execute([
            $this->currentClub(), $memberId,
            trim($_POST['nickname'] ?? '') ?: null,
            $stance,
            trim($_POST['weight_class'] ?? '') ?: null,
            $style,
        ]);
        Session::flash('success', 'Profil zawodnika MMA zapisany.');
        $this->redirect('mma/fighters');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new MmaFighterModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('mma/fighters');
    }
}
