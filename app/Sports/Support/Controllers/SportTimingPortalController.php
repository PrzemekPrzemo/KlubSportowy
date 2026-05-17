<?php

namespace App\Sports\Support\Controllers;

use App\Controllers\BaseController;
use App\Helpers\MemberAuth;
use App\Helpers\SportModuleLoader;
use App\Sports\Support\SportTimingResultModel;

/**
 * Portal członka — moje wyniki w timing-sportach.
 * URL: /portal/sport/{key}/my_results
 */
class SportTimingPortalController extends BaseController
{
    public function myResults(string $key): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $member   = MemberAuth::member();
        $manifest = SportModuleLoader::get($key);
        if (!$manifest) {
            \App\Helpers\Session::flash('error', 'Nieznany sport: ' . $key);
            $this->redirect('portal/dashboard');
        }

        $model       = new SportTimingResultModel();
        $eventFilter = $_GET['event'] ?? null;
        $page        = max(1, (int)($_GET['page'] ?? 1));

        $pagination = $model->listForClubSport($key, $memberId, $eventFilter, $page, 30);
        $pbs        = $model->personalBests($memberId, $key);
        $history    = $model->historyForMember($memberId, $key, $eventFilter, 200);
        $events     = $model->eventNames($key);

        $this->view->setLayout('portal');
        $this->view->render('portal/sport/timing/my_results', [
            'title'       => 'Moje wyniki — ' . ($manifest['name'] ?? $key),
            'member'      => $member,
            'sportKey'    => $key,
            'manifest'    => $manifest,
            'pagination'  => $pagination,
            'pbs'         => $pbs,
            'history'     => $history,
            'events'      => $events,
            'eventFilter' => $eventFilter,
        ]);
    }
}
