<?php

namespace App\Sports\Canoeing\Controllers;

use App\Controllers\BaseController;
use App\Helpers\ClubContext;
use App\Helpers\MemberAuth;
use App\Sports\Canoeing\Models\CanoeingMemberModel;
use App\Sports\Canoeing\Models\CanoeingRaceResultModel;

/**
 * Portal zawodnika — kajakarstwo (moj profil + moje wyniki).
 *
 *   GET /portal/canoeing/me   — moj profil + historia wyscigow
 */
class PortalCanoeingController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        MemberAuth::requireLogin();
    }

    public function me(): void
    {
        $memberId = (int)MemberAuth::id();
        $clubId = MemberAuth::clubId();
        if ($clubId === null) {
            $m = MemberAuth::member();
            $clubId = (int)($m['club_id'] ?? 0);
        }
        if ($clubId > 0) {
            ClubContext::set((int)$clubId);
        }

        $profile = (new CanoeingMemberModel())->findForMember($memberId);
        $results = (new CanoeingRaceResultModel())->listForMember($memberId);

        $this->view->setLayout('portal');
        $this->view->render('canoeing/portal/me', [
            'title'       => 'Moje kajakarstwo',
            'profile'     => $profile,
            'results'     => $results,
            'boatClasses' => CanoeingMemberModel::BOAT_CLASSES,
        ]);
    }
}
