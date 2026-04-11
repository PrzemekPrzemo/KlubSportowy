<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ActivityLogModel;

/**
 * Kontrola impersonacji — stop impersonation musi być dostępny
 * dla impersonującego (aktualnie Auth::isSuperAdmin() zwraca false),
 * dlatego nie dziedziczy requireSuperAdmin z AdminController.
 */
class ImpersonationController extends BaseController
{
    public function stop(): void
    {
        Csrf::verify();
        $this->requireLogin();

        if (!Auth::isImpersonating()) {
            $this->redirect('dashboard');
        }

        try {
            (new ActivityLogModel())->log('impersonate_stop', 'user', Auth::id());
        } catch (\Throwable) {}

        Auth::stopImpersonation();
        Session::flash('success', 'Powrót do konta administratora.');
        $this->redirect('admin/dashboard');
    }
}
