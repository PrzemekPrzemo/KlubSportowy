<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Ksef\KsefApiClient;
use App\Helpers\Session;
use App\Models\ClubInvoiceModel;
use App\Models\ClubKsefConfigModel;

/**
 * Per-klub konfiguracja KSeF — dla admina/zarządu klubu.
 *
 * Faza 1 (foundation): NIP + tryb + token + certyfikat + test połączenia.
 * Faza 2 doda: lista wystawionych faktur z KSeF numerami, statusy wysyłki.
 *
 * Gating: requireSuperAdmin lub requireRole(['zarzad','admin']) +
 * requireKsefEnabledForClub — super admin MUSI najpierw włączyć integrację
 * przez /admin/platform/ksef, inaczej admin klubu dostaje redirect z
 * komunikatem "Skontaktuj się z administratorem platformy".
 *
 * Sekrety (api_token, cert_password) są szyfrowane przez encryptForClub
 * w modelu. UI nigdy nie zwraca zaszyfrowanych wartości — pola pokazują
 * placeholder "••••" jeśli wartość jest ustawiona.
 */
class ClubKsefController extends BaseController
{
    private ClubKsefConfigModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'admin']);
        $this->model = new ClubKsefConfigModel();
        $this->requireKsefEnabledForClub();
    }

    /**
     * Middleware: redirect z flashem jeśli super admin nie włączył KSeF
     * dla tego klubu. Nie blokuje samego super admina — pozwala mu
     * zobaczyć / debugować widok nawet przy enabled=0.
     */
    private function requireKsefEnabledForClub(): void
    {
        if (\App\Helpers\Auth::isSuperAdmin()) {
            return;
        }
        $clubId = (int)$this->currentClub();
        if (!$this->model->isEnabledForClub($clubId)) {
            Session::flash('warning', 'KSeF nie został aktywowany dla klubu — skontaktuj się z administratorem platformy.');
            $this->redirect('dashboard');
        }
    }

    public function index(): void
    {
        $clubId = (int)$this->currentClub();
        $cfg    = $this->model->findForClub($clubId);

        // Phase 2 — bieżący format numeracji dla aktualnego roku.
        $year             = (int)date('Y');
        $invModel         = new ClubInvoiceModel();
        $numberingFormat  = $invModel->numberingFormat($clubId, $year);

        $this->render('club/ksef_settings/index', [
            'title'  => 'KSeF — konfiguracja',
            'cfg'    => $cfg,
            'clubId' => $clubId,
            'numberingFormat' => $numberingFormat,
            'numberingYear'   => $year,
        ]);
    }

    /**
     * Phase 2 — zmiana formatu numeru faktur dla danego roku.
     * Placeholder {seq} jest WYMAGANY.
     */
    public function saveNumbering(): void
    {
        Csrf::verify();
        $clubId = (int)$this->currentClub();
        $year   = (int)($_POST['year'] ?? date('Y'));
        $format = (string)($_POST['format'] ?? '');

        if (strpos($format, '{seq}') === false) {
            Session::flash('error', 'Format numeru musi zawierać placeholder {seq}.');
            $this->redirect('club/ksef-settings');
        }
        (new ClubInvoiceModel())->setNumberingFormat($clubId, $year, $format);
        $this->model->audit($clubId, 'config_change', "Numbering {$year}: {$format}");
        Session::flash('success', 'Format numeracji zapisany.');
        $this->redirect('club/ksef-settings');
    }

    public function update(): void
    {
        Csrf::verify();
        $clubId = (int)$this->currentClub();
        $changed = [];

        $nip  = trim((string)($_POST['nip'] ?? ''));
        $mode = (string)($_POST['mode'] ?? '');
        $token = (string)($_POST['api_token'] ?? '');
        $certPassword = (string)($_POST['cert_password'] ?? '');
        $subjectId    = trim((string)($_POST['authorized_subject_identifier'] ?? ''));

        $data = [];

        if ($nip !== '') {
            $nipDigits = preg_replace('/\D/', '', $nip) ?? '';
            if (!ClubKsefConfigModel::validateNip($nipDigits)) {
                Session::flash('error', 'Nieprawidłowy NIP (oczekiwano 10 cyfr z poprawną sumą kontrolną).');
                $this->redirect('club/ksef-settings');
            }
            $data['nip'] = $nipDigits;
            $changed[] = 'NIP';
        }

        if (in_array($mode, ['test', 'prod'], true)) {
            $data['mode'] = $mode;
            $changed[] = 'mode=' . $mode;
        }

        if ($subjectId !== '') {
            $data['authorized_subject_identifier'] = mb_substr($subjectId, 0, 50);
            $changed[] = 'subject_id';
        }

        // Token: niepusty = aktualizacja. Pusty = pozostaw aktualny.
        if ($token !== '') {
            $data['api_token'] = $token;
        }

        // Certyfikat .p12 (upload) + hasło
        if (!empty($_FILES['cert_file']['tmp_name']) && is_uploaded_file($_FILES['cert_file']['tmp_name'])) {
            $certPath = $this->saveCertificate($_FILES['cert_file'], $clubId);
            if ($certPath !== null) {
                $data['cert_path'] = $certPath;
                $changed[] = 'certyfikat';
                $this->model->audit($clubId, 'cert_uploaded', 'Cert: ' . basename($certPath));
            }
        }

        if ($certPassword !== '') {
            $data['cert_password'] = $certPassword;
        }

        if (!empty($data)) {
            $this->model->upsert($clubId, $data);
            if ($token !== '') {
                $this->model->audit($clubId, 'token_set', 'Token KSeF zaktualizowany.');
            }
            if (!empty($changed)) {
                $this->model->audit(
                    $clubId,
                    'config_change',
                    'Zmieniono: ' . implode(', ', $changed)
                );
            }
            Session::flash('success', 'Konfiguracja KSeF zapisana.');
        } else {
            Session::flash('info', 'Brak zmian.');
        }
        $this->redirect('club/ksef-settings');
    }

    public function testConnection(): void
    {
        Csrf::verify();
        $clubId = (int)$this->currentClub();

        // Mode pobieramy z configu, KsefApiClient dostanie mode w testConnection.
        $cfg     = $this->model->findForClub($clubId);
        $mode    = (string)($cfg['mode'] ?? KsefApiClient::MODE_TEST);
        $client  = new KsefApiClient($mode);

        try {
            $result = $client->testConnection($clubId);
        } catch (\Throwable $e) {
            $result = ['ok' => false, 'message' => 'Wyjątek: ' . $e->getMessage()];
        }

        $this->model->recordConnectionTest($clubId, (bool)$result['ok'], (string)$result['message']);
        $this->model->audit(
            $clubId,
            'connection_test',
            ($result['ok'] ? 'OK: ' : 'FAIL: ') . substr((string)$result['message'], 0, 500)
        );

        if ($result['ok']) {
            Session::flash('success', 'Test połączenia: ' . $result['message']);
        } else {
            Session::flash('error', 'Test połączenia nieudany: ' . $result['message']);
        }
        $this->redirect('club/ksef-settings');
    }

    /**
     * Zapisuje plik .p12 do storage/ksef/{clubId}/ z chmod 0600.
     * Walidacja: rozszerzenie .p12/.pfx, rozmiar <= 100KB.
     *
     * @param array<string,mixed> $file
     */
    private function saveCertificate(array $file, int $clubId): ?string
    {
        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > 100 * 1024) {
            Session::flash('error', 'Plik certyfikatu jest pusty lub przekracza 100KB.');
            return null;
        }
        $name = (string)($file['name'] ?? '');
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['p12', 'pfx'], true)) {
            Session::flash('error', 'Oczekiwano pliku .p12 lub .pfx.');
            return null;
        }
        // MIME sanity check (best-effort)
        $tmp = (string)$file['tmp_name'];
        if (is_file($tmp) && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = $finfo ? finfo_file($finfo, $tmp) : '';
            if ($finfo) finfo_close($finfo);
            $allowed = ['application/x-pkcs12', 'application/pkcs12', 'application/octet-stream'];
            if ($mime && !in_array($mime, $allowed, true)) {
                Session::flash('error', "Nieprawidłowy typ pliku ({$mime}).");
                return null;
            }
        }

        $dir = ROOT_PATH . '/storage/ksef/' . $clubId;
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $target = $dir . '/cert.p12';
        if (!@move_uploaded_file($tmp, $target)) {
            Session::flash('error', 'Nie udało się zapisać certyfikatu.');
            return null;
        }
        @chmod($target, 0600);

        return 'storage/ksef/' . $clubId . '/cert.p12';
    }
}
