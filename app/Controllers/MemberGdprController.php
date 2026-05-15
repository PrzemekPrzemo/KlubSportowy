<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\EmailService;
use App\Helpers\GdprService;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Models\GdprRequestModel;
use App\Models\TenantAccessLogModel;

/**
 * Self-service GDPR portal dla czlonka.
 *
 * Endpoints (wszystkie wymagaja MemberAuth):
 *   GET  /portal/gdpr                  - landing z opcjami
 *   GET  /portal/gdpr/delete-account   - formularz prawa do bycia zapomnianym
 *   POST /portal/gdpr/delete-account   - utworz prosbe + email confirmation
 *   GET  /portal/gdpr/export           - formularz prosby o eksport
 *   POST /portal/gdpr/export           - utworz prosbe + email confirmation
 *   GET  /portal/gdpr/confirm/:token   - kliknij link z emaila (no auth)
 *   GET  /portal/gdpr/download/:id     - pobranie wygenerowanego ZIP
 *   GET  /portal/gdpr/requests         - moje prosby (status)
 *
 * Wszystkie operacje sa scoped per (member_id, club_id) — defense-in-depth
 * przeciw cross-tenant operacjom.
 */
class MemberGdprController extends BaseController
{
    public function index(): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();

        $requests = (new GdprRequestModel())->listForMember($memberId, $clubId);

        $this->view->setLayout('portal');
        $this->view->render('portal/gdpr/index', [
            'title'    => 'Moje dane (RODO)',
            'member'   => MemberAuth::member(),
            'requests' => $requests,
            'types'    => GdprRequestModel::TYPES,
            'statuses' => GdprRequestModel::STATUSES,
            'appName'  => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function showDeleteForm(): void
    {
        MemberAuth::requireLogin();
        $this->view->setLayout('portal');
        $this->view->render('portal/gdpr/delete_form', [
            'title'   => 'Prosba o usuniecie konta',
            'member'  => MemberAuth::member(),
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function submitDelete(): void
    {
        Csrf::verify();
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $member   = MemberAuth::member();

        // Walidacja: czlonek wpisuje swoj email + checkbox potwierdzenia
        $emailConfirm = trim($_POST['email_confirm'] ?? '');
        $understood   = !empty($_POST['understood']);
        $reason       = trim($_POST['reason'] ?? '');

        if (!$understood) {
            Session::flash('error', 'Musisz potwierdzic ze rozumiesz konsekwencje.');
            $this->redirect('portal/gdpr/delete-account');
        }

        $memberEmail = trim((string)($member['email'] ?? ''));
        if ($memberEmail === '' || strcasecmp($emailConfirm, $memberEmail) !== 0) {
            Session::flash('error', 'Wpisany e-mail nie zgadza sie z e-mailem konta.');
            $this->redirect('portal/gdpr/delete-account');
        }

        // Utworz prosbe + token
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $created = (new GdprRequestModel())->createRequest(
            $clubId, $memberId, 'delete', $reason ?: null, $ip, $ua
        );

        $this->sendConfirmationEmail($member, $clubId, 'delete', $created['token']);

        // Audit log
        try {
            (new TenantAccessLogModel())->logBypass(
                'gdpr_requests',
                'write',
                __FILE__,
                __LINE__,
                self::class,
                'critical',
                'GDPR delete request #' . $created['id'] . ' member=' . $memberId . ' ip=' . ($ip ?? '?')
            );
        } catch (\Throwable) {}

        $this->view->setLayout('portal');
        $this->view->render('portal/gdpr/confirmation_sent', [
            'title'   => 'Sprawdz e-mail',
            'member'  => $member,
            'type'    => 'delete',
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function showExportForm(): void
    {
        MemberAuth::requireLogin();
        $this->view->setLayout('portal');
        $this->view->render('portal/gdpr/export_form', [
            'title'   => 'Eksport moich danych',
            'member'  => MemberAuth::member(),
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function submitExport(): void
    {
        Csrf::verify();
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $member   = MemberAuth::member();

        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $created = (new GdprRequestModel())->createRequest(
            $clubId, $memberId, 'export', null, $ip, $ua
        );

        $this->sendConfirmationEmail($member, $clubId, 'export', $created['token']);

        try {
            (new TenantAccessLogModel())->logBypass(
                'gdpr_requests',
                'write',
                __FILE__,
                __LINE__,
                self::class,
                'info',
                'GDPR export request #' . $created['id'] . ' member=' . $memberId
            );
        } catch (\Throwable) {}

        $this->view->setLayout('portal');
        $this->view->render('portal/gdpr/confirmation_sent', [
            'title'   => 'Sprawdz e-mail',
            'member'  => $member,
            'type'    => 'export',
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /**
     * Kliknieie linku z emaila — potwierdza prosbe i wykonuje akcje.
     *
     * UWAGA: ten endpoint NIE wymaga MemberAuth (link moze byc otwarty
     * z innej sesji / przegladarki). Token sam w sobie jest authentication
     * factor (64 chars hex, expires 24h).
     */
    public function confirm(string $token): void
    {
        $token = preg_replace('/[^a-f0-9]/i', '', $token);
        if (strlen($token) !== 64) {
            $this->renderTokenError('Nieprawidlowy format linku.');
            return;
        }

        $model = new GdprRequestModel();
        $req   = $model->findByToken($token);

        if (!$req) {
            $this->renderTokenError('Link wygasl lub jest nieprawidlowy. Zloz nowa prosbe w portalu.');
            return;
        }

        // Potwierdz: status -> in_progress
        $model->confirm((int)$req['id']);

        $memberId = (int)$req['member_id'];
        $clubId   = (int)$req['club_id'];
        $type     = (string)$req['request_type'];

        // Wykonaj akcje
        if ($type === 'delete') {
            $this->processDeleteRequest((int)$req['id'], $memberId, $clubId);
        } elseif ($type === 'export') {
            $this->processExportRequest((int)$req['id'], $memberId, $clubId);
        } else {
            // Pozostale typy (rectify, restrict, etc.) — wymagaja recznej obslugi przez admina
        }

        // Pokaz strone podziekowania
        $this->view->setLayout('portal_auth');
        $this->view->render('portal/gdpr/confirmed', [
            'title'   => 'Prosba potwierdzona',
            'type'    => $type,
            'reqId'   => (int)$req['id'],
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    public function download(string $id): void
    {
        MemberAuth::requireLogin();
        $memberId = (int)MemberAuth::id();
        $clubId   = (int)MemberAuth::clubId();
        $reqId    = (int)$id;

        $model = new GdprRequestModel();
        $req   = $model->findOwnedBy($reqId, $memberId, $clubId);

        if (!$req || $req['request_type'] !== 'export' || empty($req['export_file_path'])) {
            Session::flash('error', 'Plik eksportu niedostepny.');
            $this->redirect('portal/gdpr');
        }

        // Sprawdz czy plik nie wygasl
        if (!empty($req['export_file_expires_at']) && strtotime($req['export_file_expires_at']) < time()) {
            // On-access cleanup
            if (is_file($req['export_file_path'])) {
                @unlink($req['export_file_path']);
            }
            Database::pdo()
                ->prepare("UPDATE gdpr_requests SET export_file_path = NULL WHERE id = ?")
                ->execute([$reqId]);

            Session::flash('error', 'Plik eksportu wygasl (7 dni). Zloz nowa prosbe.');
            $this->redirect('portal/gdpr');
        }

        $path = $req['export_file_path'];
        if (!is_file($path)) {
            Session::flash('error', 'Plik eksportu nie istnieje na dysku. Skontaktuj sie z administratorem.');
            $this->redirect('portal/gdpr');
        }

        // Stream the file
        $filename = basename($path);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    // ----------------------------------------------------------
    // Internal processors
    // ----------------------------------------------------------

    private function processDeleteRequest(int $reqId, int $memberId, int $clubId): void
    {
        $model = new GdprRequestModel();

        // Pobierz email DO wyslania notyfikacji ZANIM zanonimizujemy
        $stmt = Database::pdo()->prepare("SELECT email, first_name FROM members WHERE id = ? AND club_id = ?");
        $stmt->execute([$memberId, $clubId]);
        $member = $stmt->fetch();
        $emailBefore = $member['email'] ?? null;

        try {
            $ok = GdprService::anonymizeMember($memberId, $clubId);
        } catch (\Throwable $e) {
            $model->markRejected($reqId, null, 'Blad anonimizacji: ' . substr($e->getMessage(), 0, 400));
            return;
        }

        if (!$ok) {
            $model->markRejected($reqId, null, 'Cross-tenant guard: member nie nalezy do klubu.');
            return;
        }

        $model->markCompleted($reqId, null, 'Konto zanonimizowane automatycznie po confirmation linku.');

        // Email notification (do starego adresu, jesli byl)
        if ($emailBefore) {
            $club = $this->clubName($clubId);
            EmailService::send(
                $clubId,
                $emailBefore,
                'Twoja prosba o usuniecie danych zostala zrealizowana',
                "Czesc,\n\nTwoja prosba o usuniecie danych w klubie {$club} zostala zrealizowana. "
                . "Twoje dane osobowe zostaly zanonimizowane zgodnie z art. 17 RODO.\n\n"
                . "Dziekujemy za przynaleznosc do klubu."
            );
        }

        // Wyloguj sesje
        MemberAuth::logout();
    }

    private function processExportRequest(int $reqId, int $memberId, int $clubId): void
    {
        $model = new GdprRequestModel();
        try {
            $zipPath = GdprService::buildExportZip($memberId, $clubId);
        } catch (\Throwable $e) {
            $model->markRejected($reqId, null, 'Blad generacji eksportu: ' . substr($e->getMessage(), 0, 400));
            return;
        }

        $model->markCompleted(
            $reqId,
            null,
            'Eksport wygenerowany automatycznie. Link wygasa za 7 dni.',
            $zipPath
        );

        // Email notification
        $stmt = Database::pdo()->prepare("SELECT email, first_name FROM members WHERE id = ?");
        $stmt->execute([$memberId]);
        $m = $stmt->fetch();
        if (!empty($m['email'])) {
            $club = $this->clubName($clubId);
            $firstName = $m['first_name'] ?? '';
            EmailService::send(
                $clubId,
                $m['email'],
                'Twoj eksport danych jest gotowy',
                "Czesc {$firstName},\n\nTwoj eksport danych zostal wygenerowany. "
                . "Mozesz go pobrac w portalu w sekcji 'Moje dane' -> 'Historia prosb GDPR'.\n\n"
                . "Link wygasa za 7 dni.\n\nPozdrawiamy,\nKlub {$club}"
            );
        }
    }

    private function sendConfirmationEmail(array $member, int $clubId, string $type, string $token): void
    {
        $email = $member['email'] ?? null;
        if (!$email) return;

        $link = rtrim(BASE_URL, '/') . '/portal/gdpr/confirm/' . $token;
        $club = $this->clubName($clubId);
        $firstName = $member['first_name'] ?? '';
        $typeLabel = GdprRequestModel::TYPES[$type] ?? $type;

        $body = "Czesc {$firstName},\n\n"
              . "Otrzymalismy Twoja prosbe GDPR ({$typeLabel}) w klubie {$club}.\n"
              . "Aby ja potwierdzic, kliknij ponizszy link (wazny przez 24h):\n\n"
              . "{$link}\n\n"
              . "Jesli to nie Ty zlozyles te prosbe, zignoruj te wiadomosc — bez kliknieia linku "
              . "zadna operacja nie zostanie wykonana.\n\n"
              . "Pozdrawiamy,\nKlub {$club}";

        EmailService::send($clubId, $email, 'Potwierdz prosbe GDPR', $body);
    }

    private function clubName(int $clubId): string
    {
        try {
            $row = (new \App\Models\ClubModel())->findById($clubId);
            return (string)($row['name'] ?? 'KlubSportowy');
        } catch (\Throwable) {
            return 'KlubSportowy';
        }
    }

    private function renderTokenError(string $message): void
    {
        $this->view->setLayout('portal_auth');
        $this->view->render('portal/gdpr/token_error', [
            'title'   => 'Blad weryfikacji',
            'message' => $message,
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }
}
