<?php

namespace App\Sports\Sailing\Controllers;

use App\Controllers\BaseController;
use App\Controllers\Traits\RequiresActiveSport;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Support\Models\SailingMemberProfileModel;

/**
 * Profil zeglarza — klasy lodzi (ISAF), numer ISAF, ranking krajowy.
 * Tabela: sport_sailing_member (z 106_scoring_niche_full.sql).
 */
class SailorProfileController extends BaseController
{
    use RequiresActiveSport;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireSportActive('sailing');
    }

    public function index(): void
    {
        $model = new SailingMemberProfileModel();
        $this->render('sailing/sailor/index', [
            'title'        => 'Profile żeglarzy (ISAF) — Żeglarstwo',
            'profiles'     => $model->listForClub(),
            'members'      => (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [],
            'boatClasses'  => SailingMemberProfileModel::$BOAT_CLASSES,
        ]);
    }

    public function save(): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz zawodnika.');
            $this->redirect('sailing/sailor');
        }
        $classes = $_POST['boat_classes'] ?? [];
        if (!is_array($classes)) $classes = [];
        $valid = array_intersect($classes, array_keys(SailingMemberProfileModel::$BOAT_CLASSES));

        (new SailingMemberProfileModel())->upsert($memberId, [
            'boat_classes'  => $valid,
            'isaf_number'   => trim((string)($_POST['isaf_number'] ?? '')) ?: null,
            'national_rank' => isset($_POST['national_rank']) && $_POST['national_rank'] !== ''
                                  ? (int)$_POST['national_rank'] : null,
        ]);
        Session::flash('success', 'Profil żeglarza zapisany.');
        $this->redirect('sailing/sailor');
    }
}
