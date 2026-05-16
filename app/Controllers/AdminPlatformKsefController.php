<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ClubKsefConfigModel;
use App\Models\KsefSendQueueModel;

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

    // ── Phase 3: queue overview ──────────────────────────────────

    public function queue(): void
    {
        $queue   = new KsefSendQueueModel();
        $filters = [
            'status'  => trim((string)($_GET['status'] ?? '')) ?: null,
            'club_id' => !empty($_GET['club_id']) ? (int)$_GET['club_id'] : null,
        ];
        $rows  = $queue->listAll($filters, 200);
        $stats = $queue->stats();

        $this->render('admin/platform/ksef/queue', [
            'title'   => 'KSeF — kolejka wysylki',
            'rows'    => $rows,
            'stats'   => $stats,
            'filters' => $filters,
        ]);
    }

    public function queueForceRetry(string $queueId): void
    {
        Csrf::verify();
        $qid = (int)$queueId;
        if ($qid <= 0) {
            Session::flash('error', 'Nieprawidlowy wpis kolejki.');
            $this->redirect('admin/platform/ksef/queue');
        }
        $queue = new KsefSendQueueModel();
        $entry = $queue->findById($qid);
        if (!$entry) {
            Session::flash('error', 'Wpis kolejki nie istnieje.');
            $this->redirect('admin/platform/ksef/queue');
        }
        $queue->forceRetry($qid);
        $this->model->audit((int)$entry['club_id'], 'queue_force_retry',
            'admin force-retry queue_id=' . $qid . ' invoice_id=' . (int)$entry['invoice_id']);
        Session::flash('success', 'Wymuszono ponowienie wysylki.');
        $this->redirect('admin/platform/ksef/queue');
    }

    public function queueForceFail(string $queueId): void
    {
        Csrf::verify();
        $qid = (int)$queueId;
        $reason = trim((string)($_POST['reason'] ?? 'admin manual fail'));
        if ($qid <= 0) {
            Session::flash('error', 'Nieprawidlowy wpis kolejki.');
            $this->redirect('admin/platform/ksef/queue');
        }
        $queue = new KsefSendQueueModel();
        $entry = $queue->findById($qid);
        if (!$entry) {
            Session::flash('error', 'Wpis kolejki nie istnieje.');
            $this->redirect('admin/platform/ksef/queue');
        }
        $queue->forceFail($qid, $reason);
        $this->model->audit((int)$entry['club_id'], 'queue_force_fail',
            'admin force-fail queue_id=' . $qid . ' invoice_id=' . (int)$entry['invoice_id'] . ' reason=' . mb_substr($reason, 0, 200));
        Session::flash('success', 'Wpis oznaczony jako failed.');
        $this->redirect('admin/platform/ksef/queue');
    }
}
