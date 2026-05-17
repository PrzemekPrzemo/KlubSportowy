<?php

namespace App\Sports\Dance\Controllers;

use App\Controllers\BaseController;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Sports\Dance\Models\DanceMemberStyleModel;
use App\Sports\Dance\Models\DancePerformanceModel;
use App\Sports\Dance\Models\DanceStyleModel;

/**
 * Portal zawodnika — taniec:
 *   GET  /portal/dance/styles        — moje style + wystepy
 *   POST /portal/dance/styles/save   — dodanie/edycja stylu
 *   POST /portal/dance/styles/remove — usuniecie stylu
 */
class PortalDanceController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        MemberAuth::requireLogin();
    }

    private function ensureClubContext(): int
    {
        $clubId = MemberAuth::clubId();
        if ($clubId === null) {
            $m = MemberAuth::member();
            $clubId = (int)($m['club_id'] ?? 0);
        }
        if ($clubId > 0) {
            ClubContext::set((int)$clubId);
        }
        return (int)$clubId;
    }

    public function myStyles(): void
    {
        $memberId = (int)MemberAuth::id();
        $this->ensureClubContext();

        $mine         = (new DanceMemberStyleModel())->listForMember($memberId);
        $allStyles    = (new DanceStyleModel())->listAvailableForClub();
        $performances = (new DancePerformanceModel())->listForMember($memberId);

        $this->view->setLayout('portal');
        $this->view->render('dance/portal/my_styles', [
            'title'        => 'Moje style tanca',
            'mine'         => $mine,
            'styles'       => $allStyles,
            'levels'       => DanceStyleModel::LEVELS,
            'performances' => $performances,
        ]);
    }

    public function saveStyle(): void
    {
        Csrf::verify();
        $memberId = (int)MemberAuth::id();
        $this->ensureClubContext();

        $styleCode = strtolower(trim($_POST['style_code'] ?? ''));
        if (!preg_match('/^[a-z0-9_]{2,50}$/', $styleCode)) {
            Session::flash('error', 'Wybierz styl.');
            $this->redirect('portal/dance/styles');
        }
        $style = (new DanceStyleModel())->findByCode($styleCode);
        if ($style === null) {
            Session::flash('error', 'Wybrany styl nie jest dostepny.');
            $this->redirect('portal/dance/styles');
        }
        (new DanceMemberStyleModel())->upsert($memberId, $styleCode, [
            'level' => $_POST['level'] ?? 'beginner',
        ]);
        Session::flash('success', 'Styl zapisany.');
        $this->redirect('portal/dance/styles');
    }

    public function removeStyle(): void
    {
        Csrf::verify();
        $memberId  = (int)MemberAuth::id();
        $this->ensureClubContext();
        $styleCode = strtolower(trim($_POST['style_code'] ?? ''));
        if (preg_match('/^[a-z0-9_]{2,50}$/', $styleCode)) {
            (new DanceMemberStyleModel())->remove($memberId, $styleCode);
            Session::flash('success', 'Styl usuniety.');
        }
        $this->redirect('portal/dance/styles');
    }
}
