<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ClubModel;
use App\Models\DashboardWidgetModel;
use App\Models\EventModel;
use App\Models\MedicalExamModel;
use App\Models\MemberModel;
use App\Models\PaymentModel;
use App\Models\SubscriptionModel;
use App\Models\TrainingModel;

class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $clubId = $this->currentClub();

        if ((new ClubModel())->needsOnboarding($clubId) && !Session::get('skip_onboarding')) {
            $this->redirect('onboarding/step1');
        }

        $stats            = (new ClubModel())->stats($clubId);
        $upcoming         = (new EventModel())->upcomingForClub(5);
        $upcomingTrainings = (new TrainingModel())->upcomingForClub(5);
        $expiringMedical  = (new MedicalExamModel())->expiringSoon(60);
        $sub              = (new SubscriptionModel())->findForClub($clubId);

        // X.2 — Activity feed (10 ostatnich zdarzeń klubu)
        $activityFeed = [];
        try {
            $activityFeed = (new \App\Models\ActivityLogModel())->recentForClub($clubId, 10);
        } catch (\Throwable) {}

        // Load widget configuration for current user
        $widgetModel  = new DashboardWidgetModel();
        $widgets      = $widgetModel->getForUser((int)Auth::id());

        $this->render('dashboard/index', [
            'title'            => 'Dashboard',
            'stats'            => $stats,
            'upcoming'         => $upcoming,
            'upcomingTrainings'=> $upcomingTrainings,
            'expiringMedical'  => $expiringMedical,
            'subscription'     => $sub,
            'widgets'          => $widgets,
            'activityFeed'     => $activityFeed,
        ]);
    }

    public function saveWidgets(): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        Csrf::verify();

        $keys    = $_POST['widget_key'] ?? [];
        $visible = $_POST['widget_visible'] ?? [];

        if (!is_array($keys)) {
            Session::flash('error', __('flash.error'));
            $this->redirect('dashboard');
        }

        $widgets = [];
        foreach ($keys as $i => $key) {
            $widgets[] = [
                'widget_key' => $key,
                'is_visible' => isset($visible[$key]) ? 1 : 0,
            ];
        }

        $widgetModel = new DashboardWidgetModel();
        $widgetModel->saveOrder((int)Auth::id(), $widgets);

        Session::flash('success', __('flash.saved'));
        $this->redirect('dashboard');
    }
}
