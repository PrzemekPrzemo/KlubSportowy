<?php

namespace App\Controllers;

use App\Models\ClubModel;
use App\Models\EventModel;
use App\Models\MemberModel;
use App\Models\PaymentModel;
use App\Models\SubscriptionModel;

class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $clubId = $this->currentClub();

        $stats = (new ClubModel())->stats($clubId);
        $upcoming = (new EventModel())->upcomingForClub(5);
        $sub = (new SubscriptionModel())->findForClub($clubId);

        $this->render('dashboard/index', [
            'title'        => 'Dashboard',
            'stats'        => $stats,
            'upcoming'     => $upcoming,
            'subscription' => $sub,
        ]);
    }
}
