<?php

namespace App\Controllers;

use App\Helpers\Backup\ClubExporter;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\TenantAccessLogModel;
use PDO;

/**
 * AdminPlatformBackupsController — super-admin widok wszystkich backupow
 * ze wszystkich klubow + mozliwosc wymuszonego utworzenia backupu dla
 * dowolnego klubu (np. przed planowanym usunieciem).
 */
class AdminPlatformBackupsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function index(): void
    {
        $db = Database::pdo();
        $backups = [];
        try {
            $stmt = $db->query(
                'SELECT cb.*, c.name AS club_name
                 FROM club_backups cb
                 LEFT JOIN clubs c ON c.id = cb.club_id
                 ORDER BY cb.id DESC LIMIT 200'
            );
            $backups = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (\Throwable $e) {
            Session::flash('warning', 'Tabela club_backups nie istnieje — uruchom migracje 097.');
        }

        $clubs = [];
        try {
            $clubs = $db->query('SELECT id, name FROM clubs WHERE is_active = 1 ORDER BY name')
                        ->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable) {}

        $this->render('admin/platform_backups', [
            'title'   => 'Backupy klubow — platforma',
            'backups' => $backups,
            'clubs'   => $clubs,
        ]);
    }

    public function forceCreate(): void
    {
        Csrf::verify();
        $clubId = (int)($_POST['club_id'] ?? 0);
        if ($clubId <= 0) {
            Session::flash('error', 'Nieprawidlowe id klubu.');
            $this->redirect('admin/platform/backups');
        }

        $db = Database::pdo();
        $stmt = $db->prepare(
            "INSERT INTO club_backups (club_id, type, status, started_at, expires_at, created_by_user_id)
             VALUES (?, 'manual', 'in_progress', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), ?)"
        );
        $stmt->execute([$clubId, \App\Helpers\Auth::id()]);
        $backupId = (int)$db->lastInsertId();

        try {
            (new TenantAccessLogModel())->logBypass(
                'club_backups', 'write', __FILE__, __LINE__, self::class, 'critical',
                "platform forced backup club={$clubId} backup={$backupId}"
            );
        } catch (\Throwable) {}

        try {
            (new ClubExporter())->export($clubId, $backupId);
            Session::flash('success', "Backup utworzony (id={$backupId}) dla klubu {$clubId}.");
        } catch (\Throwable $e) {
            try {
                $upd = $db->prepare(
                    "UPDATE club_backups SET status='failed', error_message=?, completed_at=NOW() WHERE id=?"
                );
                $upd->execute([$e->getMessage(), $backupId]);
            } catch (\Throwable) {}
            Session::flash('error', 'Backup nie powiodl sie: ' . $e->getMessage());
        }

        $this->redirect('admin/platform/backups');
    }
}
