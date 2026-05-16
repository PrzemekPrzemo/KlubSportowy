<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\EmailService;
use App\Helpers\GdprService;
use App\Helpers\Session;
use App\Models\GdprRequestModel;
use App\Models\TenantAccessLogModel;

/**
 * Admin panel obslugi prosb GDPR (zarzad/admin klubu).
 *
 * Wszystkie operacje scoped per club_id (ClubContext::current()).
 */
class AdminGdprController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'admin']);
    }

    public function index(): void
    {
        $clubId = $this->currentClub();
        $status = $_GET['status'] ?? null;
        if ($status !== null && !array_key_exists($status, GdprRequestModel::STATUSES)) {
            $status = null;
        }

        $requests = (new GdprRequestModel())->listForClub($clubId, $status, 200);

        $this->render('admin/gdpr/index', [
            'title'    => 'Prosby GDPR (RODO)',
            'requests' => $requests,
            'types'    => GdprRequestModel::TYPES,
            'statuses' => GdprRequestModel::STATUSES,
            'filter'   => $status,
        ]);
    }

    public function detail(string $id): void
    {
        $clubId = $this->currentClub();
        $req    = (new GdprRequestModel())->findForClub((int)$id, $clubId);
        if (!$req) {
            Session::flash('error', 'Prosba nie istnieje lub nie nalezy do tego klubu.');
            $this->redirect('admin/gdpr');
        }

        $this->render('admin/gdpr/detail', [
            'title'    => 'Prosba GDPR #' . (int)$id,
            'request'  => $req,
            'types'    => GdprRequestModel::TYPES,
            'statuses' => GdprRequestModel::STATUSES,
        ]);
    }

    public function process(string $id): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $reqId  = (int)$id;
        $action = $_POST['action'] ?? '';
        $notes  = trim($_POST['notes'] ?? '');

        $model = new GdprRequestModel();
        $req   = $model->findForClub($reqId, $clubId);
        if (!$req) {
            Session::flash('error', 'Prosba nie istnieje lub nie nalezy do tego klubu.');
            $this->redirect('admin/gdpr');
        }

        $adminId = (int)(Auth::id() ?? 0);

        if ($action === 'reject') {
            if ($notes === '') {
                Session::flash('error', 'Podaj powod odrzucenia.');
                $this->redirect('admin/gdpr/' . $reqId);
            }
            $model->markRejected($reqId, $adminId ?: null, $notes);
            Session::flash('success', 'Prosba odrzucona.');
            $this->redirect('admin/gdpr');
        }

        if ($action === 'approve') {
            $type     = $req['request_type'];
            $memberId = (int)$req['member_id'];

            if ($type === 'delete') {
                try {
                    $ok = GdprService::anonymizeMember($memberId, $clubId);
                } catch (\Throwable $e) {
                    Session::flash('error', 'Blad anonimizacji: ' . $e->getMessage());
                    $this->redirect('admin/gdpr/' . $reqId);
                }
                if (!$ok) {
                    Session::flash('error', 'Cross-tenant guard: member nie nalezy do tego klubu.');
                    $this->redirect('admin/gdpr/' . $reqId);
                }
                $model->markCompleted($reqId, $adminId, $notes ?: 'Anonimizacja wykonana recznie przez admina.');

                try {
                    (new TenantAccessLogModel())->logBypass(
                        'members',
                        'delete',
                        __FILE__,
                        __LINE__,
                        self::class,
                        'critical',
                        'GDPR admin process delete req=' . $reqId . ' member=' . $memberId
                    );
                } catch (\Throwable) {}

            } elseif ($type === 'export') {
                try {
                    $zipPath = GdprService::buildExportZip($memberId, $clubId, $reqId);
                } catch (\Throwable $e) {
                    Session::flash('error', 'Blad generacji eksportu: ' . $e->getMessage());
                    $this->redirect('admin/gdpr/' . $reqId);
                }
                $model->markCompleted($reqId, $adminId, $notes ?: 'Eksport wygenerowany recznie przez admina.', $zipPath);

                // Email notification do czlonka
                $stmt = Database::pdo()->prepare("SELECT email, first_name FROM members WHERE id = ?");
                $stmt->execute([$memberId]);
                $m = $stmt->fetch();
                if (!empty($m['email'])) {
                    EmailService::send(
                        $clubId,
                        $m['email'],
                        'Twoj eksport danych jest gotowy',
                        "Czesc " . ($m['first_name'] ?? '') . ",\n\n"
                        . "Twoj eksport danych zostal wygenerowany. "
                        . "Mozesz go pobrac w portalu w sekcji 'Moje dane'."
                    );
                }
            } else {
                // Inne typy — tylko mark completed z notatka
                $model->markCompleted($reqId, $adminId, $notes ?: 'Prosba obsluzona recznie.');
            }

            Session::flash('success', 'Prosba zrealizowana.');
            $this->redirect('admin/gdpr');
        }

        Session::flash('error', 'Nieznana akcja.');
        $this->redirect('admin/gdpr/' . $reqId);
    }

    /**
     * Force regenerate ZIP — usuwa stary plik i generuje od nowa.
     * Dostepne TYLKO dla zakonczonych prosb typu export.
     */
    public function forceRegenerate(string $id): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $reqId  = (int)$id;

        $model = new GdprRequestModel();
        $req   = $model->findForClub($reqId, $clubId);
        if (!$req) {
            Session::flash('error', 'Prosba nie istnieje lub nie nalezy do tego klubu.');
            $this->redirect('admin/gdpr');
        }

        if ($req['request_type'] !== 'export' || $req['status'] !== 'completed') {
            Session::flash('error', 'Force regenerate dziala tylko dla zakonczonych eksportow.');
            $this->redirect('admin/gdpr/' . $reqId);
        }

        $memberId = (int)$req['member_id'];

        // Usun stary plik (jesli istnieje)
        if (!empty($req['export_file_path']) && is_file($req['export_file_path'])) {
            @unlink($req['export_file_path']);
        }

        try {
            $zipPath = GdprService::buildExportZip($memberId, $clubId, $reqId);
        } catch (\Throwable $e) {
            Session::flash('error', 'Blad regeneracji eksportu: ' . $e->getMessage());
            $this->redirect('admin/gdpr/' . $reqId);
        }

        $model->markCompleted($reqId, (int)(Auth::id() ?? 0), 'Eksport zregenerowany przez admina.', $zipPath);

        try {
            (new TenantAccessLogModel())->logBypass(
                'gdpr_requests',
                'write',
                __FILE__,
                __LINE__,
                self::class,
                'info',
                'GDPR export regenerated by admin req=' . $reqId . ' member=' . $memberId
            );
        } catch (\Throwable) {}

        Session::flash('success', 'Eksport zregenerowany. Wygasa za 7 dni.');
        $this->redirect('admin/gdpr/' . $reqId);
    }
}
