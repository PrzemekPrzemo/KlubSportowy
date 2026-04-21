<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Session;
use App\Helpers\SportContext;
use App\Helpers\SportModuleLoader;
use App\Helpers\View;
use App\Models\ClubCustomizationModel;
use App\Models\RolePermissionModel;
use App\Models\SubscriptionModel;

abstract class BaseController
{
    protected View $view;

    public function __construct()
    {
        Session::start();
        $this->view = new View();
    }

    protected function render(string $template, array $data = []): void
    {
        $data['authUser']      = Auth::user();
        $data['flashSuccess']  = Session::getFlash('success');
        $data['flashError']    = Session::getFlash('error');
        $data['flashWarning']  = Session::getFlash('warning');
        $data['flashInfo']     = Session::getFlash('info');

        $appCfg                = require ROOT_PATH . '/config/app.php';
        $data['appName']       = $appCfg['app_name'] ?? 'KlubSportowy';

        $data['clubBranding']    = ClubCustomizationModel::getForCurrentClub();
        $data['isSuperAdmin']    = Auth::isSuperAdmin();
        $data['isImpersonating']   = Auth::isImpersonating();
        $data['impersonatingType'] = Session::get('impersonating');
        $data['currentClubId']   = ClubContext::current();
        $data['activeSportKey']     = SportContext::currentSportKey();
        $data['activeClubSportId']  = SportContext::currentClubSport();
        $data['sportNav']           = SportModuleLoader::navForActiveSport();

        if ($data['currentClubId']) {
            $data['currentClub'] = (new \App\Models\ClubModel())->findById((int)$data['currentClubId']);
            $data['clubSports']  = (new \App\Models\SportModel())->listForClub((int)$data['currentClubId']);
        } else {
            $data['currentClub'] = null;
            $data['clubSports']  = [];
        }

        // Filtr nawigacji wg uprawnień roli (null = super admin / brak filtra)
        $role = Auth::role() ?? '';
        if ($role !== '' && !Auth::isSuperAdmin()) {
            try {
                $data['navModules'] = (new RolePermissionModel())
                    ->modulesForRole($role, $data['currentClubId'] ?: null);
            } catch (\Throwable) {
                $data['navModules'] = null;
            }
        } else {
            $data['navModules'] = null;
        }

        // Feature flags per-club: disable modules via club_settings.module_*
        if ($data['currentClubId'] && is_array($data['navModules'])) {
            try {
                $cs = new \App\Models\ClubSettingsModel();
                $flagKeys = ['gallery','messages','bookings','analytics','shop','livestream'];
                foreach ($flagKeys as $fk) {
                    $val = $cs->get((int)$data['currentClubId'], 'module_' . $fk, '1');
                    if ($val === '0') {
                        $data['navModules'] = array_values(array_filter($data['navModules'], fn($m) => $m !== $fk));
                    }
                }
            } catch (\Throwable) {}
        }
        // Feature flags also for super admin (navModules=null) — store disabled modules for view filtering
        if ($data['currentClubId'] && $data['navModules'] === null) {
            try {
                $cs = new \App\Models\ClubSettingsModel();
                $disabledModules = [];
                $flagKeys = ['gallery','messages','bookings','analytics','shop','livestream'];
                foreach ($flagKeys as $fk) {
                    $val = $cs->get((int)$data['currentClubId'], 'module_' . $fk, '1');
                    if ($val === '0') {
                        $disabledModules[] = $fk;
                    }
                }
                $data['disabledModules'] = $disabledModules;
            } catch (\Throwable) {
                $data['disabledModules'] = [];
            }
        } else {
            $data['disabledModules'] = $data['disabledModules'] ?? [];
        }

        // Powiadomienia in-app (dzwoneczek)
        $data['unreadNotifs']      = [];
        $data['unreadNotifsCount'] = 0;
        if (Auth::id() !== null) {
            try {
                $n = new \App\Models\NotificationModel();
                $data['unreadNotifs']      = $n->unreadForUser((int)Auth::id(), 10);
                $data['unreadNotifsCount'] = $n->countUnread((int)Auth::id());
            } catch (\Throwable) {}
        }

        $this->view->render($template, $data);
    }

    protected function renderNoLayout(string $template, array $data = []): void
    {
        $this->view->setLayout('none');
        $this->render($template, $data);
    }

    protected function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function requireLogin(): void
    {
        Auth::requireLogin();
    }

    protected function requireRole(string|array $roles): void
    {
        Auth::requireRole($roles);
    }

    protected function requireSuperAdmin(): void
    {
        Auth::requireSuperAdmin();
    }

    /**
     * Wymaga roli uprawnionej do odczytu danych wrażliwych (medyczne,
     * anti-doping, body_metrics, emergency_contacts, minor_consents).
     * Dozwolone: zarzad, trener, instruktor, lekarz + super admin.
     */
    protected function requireSensitiveAccess(): void
    {
        Auth::requireSensitiveAccess();
    }

    /**
     * Require that the current user's role has 'view' permission for the given module.
     * Super admins bypass the check entirely.
     */
    protected function requireModule(string $module): void
    {
        if (Auth::isSuperAdmin()) {
            return;
        }

        $role   = Auth::role() ?? '';
        $clubId = ClubContext::current();

        if ($role === '' || !(new RolePermissionModel())->can($role, $module, 'view', $clubId ? (int)$clubId : null)) {
            http_response_code(403);
            echo 'Brak uprawnień do modułu: ' . htmlspecialchars($module, ENT_QUOTES, 'UTF-8');
            exit;
        }
    }

    protected function requireClubContext(): void
    {
        if (ClubContext::current() === null) {
            Session::flash('warning', 'Wybierz klub, aby kontynuować.');
            $this->redirect('club-select');
        }
        $this->checkSubscription();
    }

    protected function currentClub(): int
    {
        return ClubContext::require();
    }

    protected function checkSubscription(): void
    {
        if (Auth::isSuperAdmin()) return;
        $clubId = ClubContext::current();
        if ($clubId === null) return;
        try {
            $sub = new SubscriptionModel();
            if ($sub->isExpired($clubId)) {
                $this->render('errors/subscription_expired', [
                    'title' => 'Subskrypcja wygasła',
                ]);
                exit;
            }
        } catch (\Throwable) {}
    }
}
