<?php

namespace App\Sports\Support\Controllers;

use App\Controllers\BaseController;
use App\Helpers\MemberAuth;
use App\Helpers\SportModuleLoader;
use App\Sports\Support\SportStrengthAttemptModel;
use App\Sports\Support\SportStrengthMemberModel;

/**
 * Portal członka — moje PB squat/bench/deadlift + total dla strength sportów.
 * URL: /portal/sport/{key}/my_pbs
 */
class SportStrengthPortalController extends BaseController
{
    public function myPbs(string $key): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $member   = MemberAuth::member();
        $manifest = SportModuleLoader::get($key);
        if (!$manifest) {
            \App\Helpers\Session::flash('error', 'Nieznany sport: ' . $key);
            $this->redirect('portal/dashboard');
        }

        $profile  = (new SportStrengthMemberModel())->findForMember($memberId);
        $attempts = new SportStrengthAttemptModel();
        $pbs      = $attempts->personalBests($memberId, $key);
        $recent   = $attempts->listForMember($memberId, $key, null, 50);

        $this->view->setLayout('portal');
        $this->view->render('portal/sport/strength/my_pbs', [
            'title'    => 'Moje rekordy — ' . ($manifest['name'] ?? $key),
            'member'   => $member,
            'sportKey' => $key,
            'manifest' => $manifest,
            'profile'  => $profile,
            'pbs'      => $pbs,
            'recent'   => $recent,
        ]);
    }
}
