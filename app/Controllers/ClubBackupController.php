<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Backup\ClubExporter;
use App\Helpers\Backup\ClubImporter;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\TenantAccessLogModel;
use PDO;

/**
 * ClubBackupController — pelne backupy klubu (eksport / import / restore).
 *
 * Endpointy:
 *   GET  /club/backup                  index (lista backupow + przycisk "Utworz")
 *   POST /club/backup/create           queue manual backup (async — worker CLI)
 *   GET  /club/backup/:id/download     pobranie ZIP (chunked transfer)
 *   POST /club/backup/:id/delete       usun (DB + plik)
 *   GET  /club/backup/restore          formularz restore (upload + opcje)
 *   POST /club/backup/restore/preview  walidacja przed import (dry-run)
 *   POST /club/backup/restore/execute  wykonaj import
 *
 * Bezpieczenstwo:
 *   - tylko zarzad / admin moga uzywac (nie trener, nie czlonek)
 *   - super admin omija role-check (jak we wszystkich controllerach)
 *   - kazda krytyczna operacja (create/restore/delete) logowana do
 *     tenant_access_log severity=critical
 *   - ZIP path NIGDY z user input — generowany z backup_id
 */
class ClubBackupController extends BaseController
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
        $db = Database::pdo();

        $backups = [];
        try {
            $stmt = $db->prepare(
                'SELECT * FROM club_backups WHERE club_id = ?
                 ORDER BY id DESC LIMIT 100'
            );
            $stmt->execute([$clubId]);
            $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            Session::flash('warning', 'Tabela club_backups nie istnieje — uruchom migracje 097.');
        }

        $this->render('club/backup/index', [
            'title'   => 'Kopie zapasowe klubu',
            'backups' => $backups,
        ]);
    }

    public function create(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $userId = Auth::id();

        $db = Database::pdo();
        $stmt = $db->prepare(
            "INSERT INTO club_backups (club_id, type, status, started_at, expires_at, created_by_user_id)
             VALUES (?, 'manual', 'in_progress', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), ?)"
        );
        $stmt->execute([$clubId, $userId]);
        $backupId = (int)$db->lastInsertId();

        // Audit log — krytyczna akcja
        try {
            (new TenantAccessLogModel())->logBypass(
                'club_backups',
                'write',
                __FILE__,
                __LINE__,
                self::class,
                'critical',
                "create backup id={$backupId} club={$clubId}"
            );
        } catch (\Throwable) {}

        // Tryb async: zostawiamy status='in_progress' — worker CLI przerobi.
        // W trybie synchronicznym (np. dla malych klubow) mozemy spawnowac
        // od razu — domyslnie zakladamy worker.
        $mode = $_POST['mode'] ?? 'sync';
        if ($mode === 'sync') {
            try {
                (new ClubExporter())->export($clubId, $backupId);
                Session::flash('success', "Backup utworzony (id={$backupId}).");
            } catch (\Throwable $e) {
                try {
                    $upd = $db->prepare(
                        "UPDATE club_backups SET status='failed', error_message=?, completed_at=NOW() WHERE id=?"
                    );
                    $upd->execute([$e->getMessage(), $backupId]);
                } catch (\Throwable) {}
                Session::flash('error', 'Backup nie powiodl sie: ' . $e->getMessage());
            }
        } else {
            Session::flash('info', "Backup zakolejkowany (id={$backupId}). Worker przetworzy go w ciagu kilku minut.");
        }

        $this->redirect('club/backup');
    }

    public function download(string $id): void
    {
        $clubId = $this->currentClub();
        $bid    = (int)$id;
        if ($bid <= 0) {
            Session::flash('error', 'Niepoprawne id backupu.');
            $this->redirect('club/backup');
        }

        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT * FROM club_backups WHERE id = ? AND club_id = ? AND status = 'completed' LIMIT 1"
        );
        $stmt->execute([$bid, $clubId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['backup_path'])) {
            Session::flash('error', 'Backup nie istnieje lub nie jest gotowy.');
            $this->redirect('club/backup');
        }

        // Sciezka pochodzi z DB (nie user input) — ale weryfikujemy realpath.
        $abs = ROOT_PATH . '/' . ltrim((string)$row['backup_path'], '/');
        $real = realpath($abs);
        $allowedBase = realpath(ROOT_PATH . '/storage/backups');
        if ($real === false || $allowedBase === false
            || !str_starts_with($real, $allowedBase . DIRECTORY_SEPARATOR)) {
            http_response_code(403);
            echo 'Dostep zabroniony.';
            exit;
        }

        try {
            (new TenantAccessLogModel())->logBypass(
                'club_backups', 'read', __FILE__, __LINE__, self::class, 'info',
                "download backup id={$bid}"
            );
        } catch (\Throwable) {}

        $filename = basename($real);
        $size = filesize($real);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $size);
        header('X-Content-Type-Options: nosniff');

        // Chunked streaming — zeby readfile dla 500MB nie obciazyl RAM.
        $fh = fopen($real, 'rb');
        if ($fh === false) {
            http_response_code(500);
            exit;
        }
        while (!feof($fh)) {
            $chunk = fread($fh, 1 << 20); // 1 MB
            if ($chunk === false) break;
            echo $chunk;
            if (function_exists('ob_get_level') && ob_get_level() > 0) ob_flush();
            flush();
        }
        fclose($fh);
        exit;
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $bid    = (int)$id;

        $db = Database::pdo();
        $stmt = $db->prepare('SELECT backup_path FROM club_backups WHERE id = ? AND club_id = ? LIMIT 1');
        $stmt->execute([$bid, $clubId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            if (!empty($row['backup_path'])) {
                $abs = ROOT_PATH . '/' . ltrim((string)$row['backup_path'], '/');
                $real = realpath($abs);
                $base = realpath(ROOT_PATH . '/storage/backups');
                if ($real !== false && $base !== false && str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
                    @unlink($real);
                }
            }
            $del = $db->prepare('DELETE FROM club_backups WHERE id = ? AND club_id = ?');
            $del->execute([$bid, $clubId]);
        }

        try {
            (new TenantAccessLogModel())->logBypass(
                'club_backups', 'delete', __FILE__, __LINE__, self::class, 'critical',
                "delete backup id={$bid}"
            );
        } catch (\Throwable) {}

        Session::flash('success', 'Backup usuniety.');
        $this->redirect('club/backup');
    }

    public function restoreForm(): void
    {
        $this->render('club/backup/restore', [
            'title'   => 'Przywroc dane z backupu',
            'preview' => null,
        ]);
    }

    public function restorePreview(): void
    {
        Csrf::verify();
        $upload = $_FILES['backup_zip'] ?? null;
        if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Wgraj plik ZIP.');
            $this->redirect('club/backup/restore');
        }

        // Walidacja rozmiaru (max 500 MB)
        if ((int)$upload['size'] > 500 * 1024 * 1024) {
            Session::flash('error', 'Plik wiekszy niz 500 MB — uzyj importu przez CLI.');
            $this->redirect('club/backup/restore');
        }
        if (!is_uploaded_file((string)$upload['tmp_name'])) {
            Session::flash('error', 'Nieprawidlowy upload.');
            $this->redirect('club/backup/restore');
        }

        // Zachowaj plik w tmp z bezpieczna nazwa
        $tmpDir = ROOT_PATH . '/storage/backups/_uploads';
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0700, true);
        $token = bin2hex(random_bytes(8));
        $tmpPath = $tmpDir . '/' . $token . '.zip';
        if (!move_uploaded_file($upload['tmp_name'], $tmpPath)) {
            Session::flash('error', 'Nie moge zapisac uploadu.');
            $this->redirect('club/backup/restore');
        }
        @chmod($tmpPath, 0600);

        $validation = (new ClubImporter())->validate($tmpPath);
        Session::set('restore_upload_token', $token);

        $this->render('club/backup/restore', [
            'title'      => 'Przywroc dane z backupu',
            'preview'    => $validation,
            'uploadToken'=> $token,
        ]);
    }

    public function restoreExecute(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $token  = (string)($_POST['upload_token'] ?? '');
        $expected = (string)Session::get('restore_upload_token', '');
        if ($token === '' || !hash_equals($expected, $token)) {
            Session::flash('error', 'Nieprawidlowy token uploadu.');
            $this->redirect('club/backup/restore');
        }
        $tmpPath = ROOT_PATH . '/storage/backups/_uploads/' . preg_replace('/[^a-f0-9]/', '', $token) . '.zip';
        if (!is_file($tmpPath)) {
            Session::flash('error', 'Nie znaleziono uploadowanego pliku.');
            $this->redirect('club/backup/restore');
        }

        $overwrite = !empty($_POST['overwrite']);

        try {
            (new TenantAccessLogModel())->logBypass(
                'club_backups', 'write', __FILE__, __LINE__, self::class, 'critical',
                "restore club={$clubId} overwrite=" . ($overwrite ? '1' : '0')
            );
        } catch (\Throwable) {}

        $result = (new ClubImporter())->import($tmpPath, $clubId, [
            'overwrite'     => $overwrite,
            'restore_media' => true,
        ]);

        @unlink($tmpPath);
        Session::remove('restore_upload_token');

        if ($result['success']) {
            Session::flash('success', sprintf(
                'Import zakonczony: %d wierszy, %d plikow.',
                $result['rows_imported'], $result['files_imported']
            ));
        } else {
            Session::flash('error', 'Import nieudany: ' . implode('; ', $result['errors']));
        }
        if (!empty($result['warnings'])) {
            Session::flash('warning', 'Uwagi: ' . implode('; ', $result['warnings']));
        }
        $this->redirect('club/backup');
    }
}
