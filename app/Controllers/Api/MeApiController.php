<?php

namespace App\Controllers\Api;

use App\Helpers\ClubBranding;
use App\Helpers\Database;
use App\Models\ClubModel;
use App\Models\MemberModel;
use App\Models\MemberNotificationModel;

class MeApiController extends BaseApiController
{
    public function show(): void
    {
        $this->requireMember();
        $db = Database::pdo();

        $member = (new MemberModel())->findById($this->memberId);
        if (!$member) {
            $this->error('Nie znaleziono zawodnika.', 404, 'member_not_found');
        }

        $stmt = $db->prepare(
            "SELECT ms.*, s.name AS sport_name, s.`key` AS sport_key, s.icon
             FROM member_sports ms
             JOIN club_sports cs ON cs.id = ms.club_sport_id
             JOIN sports s ON s.id = cs.sport_id
             WHERE ms.member_id = ? AND ms.is_active = 1"
        );
        $stmt->execute([$this->memberId]);
        $sports = $stmt->fetchAll();

        $club     = (new ClubModel())->findById($this->clubId);
        $branding = ClubBranding::forClub($this->clubId)->toArray();
        $unread   = (new MemberNotificationModel())->unreadCount($this->memberId);

        $this->json([
            'member' => [
                'id'             => (int)$member['id'],
                'club_id'        => (int)$member['club_id'],
                'identity_id'    => $member['identity_id'] !== null ? (int)$member['identity_id'] : null,
                'member_number'  => $member['member_number'],
                'first_name'     => $member['first_name'],
                'last_name'      => $member['last_name'],
                'email'          => $member['email'],
                'phone'          => $member['phone'],
                'gender'         => $member['gender'],
                'birth_date'     => $member['birth_date'],
                'address_street' => $member['address_street'] ?? null,
                'address_city'   => $member['address_city'] ?? null,
                'address_postal' => $member['address_postal'] ?? null,
                'photo_path'     => $member['photo_path'] ?? null,
                'join_date'      => $member['join_date'],
                'status'         => $member['status'],
            ],
            'sports' => $sports,
            'club'   => [
                'id'         => (int)($club['id'] ?? $this->clubId),
                'name'       => $club['name'] ?? null,
                'short_name' => $club['short_name'] ?? null,
                'city'       => $club['city'] ?? null,
                'email'      => $club['email'] ?? null,
                'branding'   => AuthApiController::brandingPayload($branding, $club),
            ],
            'unread_notifications' => $unread,
        ]);
    }

    public function update(): void
    {
        $this->requireMember();

        $input = json_decode(file_get_contents('php://input') ?: '', true) ?: $_POST;
        $allowed = [];
        if (array_key_exists('phone', $input)) {
            $allowed['phone'] = $input['phone'] !== null ? substr(trim((string)$input['phone']), 0, 20) : null;
        }
        // Address: accept either single `address` (legacy mobile form) or split fields.
        foreach (['address_street','address_city','address_postal'] as $f) {
            if (array_key_exists($f, $input)) {
                $allowed[$f] = $input[$f] !== null ? substr(trim((string)$input[$f]), 0, 150) : null;
            }
        }

        if (empty($allowed)) {
            $this->error('Brak pól do aktualizacji.', 400, 'no_fields');
        }

        // Use raw PDO update — MemberModel::update has encryption side effects we want to skip.
        $set = implode(' = ?, ', array_map(fn($c) => "`{$c}`", array_keys($allowed))) . ' = ?';
        $stmt = Database::pdo()->prepare(
            "UPDATE members SET {$set} WHERE id = ? AND club_id = ?"
        );
        $stmt->execute([...array_values($allowed), $this->memberId, $this->clubId]);

        $this->show();
    }

    public function uploadPhoto(): void
    {
        $this->requireMember();

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $this->error('Brak pliku photo.', 400, 'no_file');
        }
        $file = $_FILES['photo'];

        if ($file['size'] > 5 * 1024 * 1024) {
            $this->error('Plik za duży (max 5 MB).', 400, 'file_too_large');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($allowed[$mime])) {
            $this->error('Dozwolone formaty: JPG, PNG.', 400, 'invalid_mime');
        }

        $dir = ROOT_PATH . '/storage/uploads/members/' . $this->memberId;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            $this->error('Nie udało się utworzyć katalogu.', 500, 'mkdir_failed');
        }
        $filename = 'photo.' . $allowed[$mime];
        $absPath  = $dir . '/' . $filename;

        if (!@move_uploaded_file($file['tmp_name'], $absPath)) {
            $this->error('Nie udało się zapisać pliku.', 500, 'save_failed');
        }

        $relPath = 'storage/uploads/members/' . $this->memberId . '/' . $filename;
        $stmt = Database::pdo()->prepare("UPDATE members SET photo_path = ? WHERE id = ? AND club_id = ?");
        $stmt->execute([$relPath, $this->memberId, $this->clubId]);

        $this->json(['status' => 'ok', 'photo_path' => $relPath]);
    }
}
