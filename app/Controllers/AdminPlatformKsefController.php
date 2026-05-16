<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ClubKsefConfigModel;

/**
 * Super admin: zarządzanie integracją KSeF na poziomie platformy.
 *
 * Faza 1 (foundation):
 *   - Lista wszystkich klubów + status enabled/disabled.
 *   - Toggle enabled (włącza dostęp klubu do /club/ksef-settings).
 *
 * Faza 2/3 (później) doda: globalny status bramki KSeF, kolejka błędów,
 * statystyki wysyłki per klub.
 *
 * Bezpieczeństwo: requireSuperAdmin — żaden admin klubu nie może wpłynąć
 * na status enabled (super admin = jedyny gatekeeper feature flagu).
 */
class AdminPlatformKsefController extends BaseController
{
    private ClubKsefConfigModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
        $this->model = new ClubKsefConfigModel();
    }

    public function index(): void
    {
        $filter      = (string)($_GET['filter'] ?? 'all');
        $enabledOnly = $filter === 'enabled';
        $rows        = $this->model->listAllClubs($enabledOnly);

        $this->render('admin/platform/ksef/index', [
            'title'  => 'KSeF — zarządzanie integracją',
            'rows'   => $rows,
            'filter' => $filter,
        ]);
    }

    public function toggle(string $clubId): void
    {
        Csrf::verify();
        $cid = (int)$clubId;
        if ($cid <= 0) {
            Session::flash('error', 'Nieprawidłowy klub.');
            $this->redirect('admin/platform/ksef');
        }

        $newVal = $this->model->toggleEnabled($cid);
        $this->model->audit(
            $cid,
            $newVal === 1 ? 'enabled' : 'disabled',
            $newVal === 1
                ? 'Super admin włączył dostęp klubu do KSeF.'
                : 'Super admin wyłączył dostęp klubu do KSeF.'
        );

        Session::flash(
            'success',
            $newVal === 1 ? 'KSeF włączony dla klubu.' : 'KSeF wyłączony dla klubu.'
        );
        $this->redirect('admin/platform/ksef');
    }
}
