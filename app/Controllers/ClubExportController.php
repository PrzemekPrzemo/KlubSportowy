<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Database;

class ClubExportController extends BaseController
{
    /**
     * GET /club/export — export current club data as JSON (club admin).
     */
    public function export(): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        $clubId = $this->currentClub();

        $this->exportClubJson($clubId);
    }

    /**
     * GET /admin/clubs/:id/export — export any club data as JSON (super admin).
     */
    public function adminExport(string $id): void
    {
        $this->requireLogin();
        $this->requireSuperAdmin();
        $clubId = (int)$id;

        $this->exportClubJson($clubId);
    }

    private function exportClubJson(int $clubId): void
    {
        $db = Database::pdo();

        $data = [
            'exported_at' => date('Y-m-d\TH:i:sP'),
            'club_id'     => $clubId,
        ];

        // Club info
        $stmt = $db->prepare("SELECT * FROM clubs WHERE id = ?");
        $stmt->execute([$clubId]);
        $data['club'] = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        // Members
        $stmt = $db->prepare("SELECT * FROM members WHERE club_id = ?");
        $stmt->execute([$clubId]);
        $data['members'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Events
        try {
            $stmt = $db->prepare("SELECT * FROM events WHERE club_id = ?");
            $stmt->execute([$clubId]);
            $data['events'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $data['events'] = [];
        }

        // Trainings
        try {
            $stmt = $db->prepare("SELECT * FROM trainings WHERE club_id = ?");
            $stmt->execute([$clubId]);
            $data['trainings'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $data['trainings'] = [];
        }

        // Payments / fees
        try {
            $stmt = $db->prepare("SELECT * FROM payments WHERE club_id = ?");
            $stmt->execute([$clubId]);
            $data['payments'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $data['payments'] = [];
        }

        // Announcements
        try {
            $stmt = $db->prepare("SELECT * FROM announcements WHERE club_id = ?");
            $stmt->execute([$clubId]);
            $data['announcements'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $data['announcements'] = [];
        }

        // Calendar entries
        try {
            $stmt = $db->prepare("SELECT * FROM calendar_entries WHERE club_id = ?");
            $stmt->execute([$clubId]);
            $data['calendar_entries'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $data['calendar_entries'] = [];
        }

        // Club sports
        try {
            $stmt = $db->prepare("SELECT * FROM club_sports WHERE club_id = ?");
            $stmt->execute([$clubId]);
            $data['club_sports'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $data['club_sports'] = [];
        }

        // User-clubs (roles)
        try {
            $stmt = $db->prepare(
                "SELECT uc.*, u.username, u.email, u.full_name FROM user_clubs uc
                 JOIN users u ON u.id = uc.user_id WHERE uc.club_id = ?"
            );
            $stmt->execute([$clubId]);
            $data['users'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $data['users'] = [];
        }

        // Customization
        try {
            $stmt = $db->prepare("SELECT * FROM club_customization WHERE club_id = ?");
            $stmt->execute([$clubId]);
            $data['customization'] = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable) {
            $data['customization'] = null;
        }

        $clubName = $data['club']['short_name'] ?? $data['club']['name'] ?? 'club';
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $clubName);
        $filename = 'export_' . $safeName . '_' . date('Ymd_His') . '.json';

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
