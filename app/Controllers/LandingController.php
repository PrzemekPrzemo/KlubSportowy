<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Models\SubscriptionModel;

class LandingController extends BaseController
{
    /**
     * Public landing page. Logged-in users are redirected to dashboard.
     */
    public function index(): void
    {
        if (Auth::check()) {
            $this->redirect('dashboard');
        }

        $plans = (new SubscriptionModel())->listPlans();

        $this->view->setLayout('landing');
        $this->render('landing/index', [
            'title' => 'Wielosportowy portal zarzadzania klubem',
            'plans' => $plans,
        ]);
    }
}
