<?php

namespace App\Models;

class ResultImageModel extends ClubScopedModel
{
    protected string $table = 'result_images';

    /**
     * List result images with pagination.
     */
    public function listImages(int $page = 1, int $perPage = 20): array
    {
        $sql = "SELECT ri.*, e.name AS event_name, m.first_name, m.last_name, s.name AS sport_name
                FROM `{$this->table}` ri
                LEFT JOIN events e ON e.id = ri.event_id
                LEFT JOIN members m ON m.id = ri.member_id
                LEFT JOIN sports s ON s.id = ri.sport_id
                WHERE 1=1";
        $params = [];

        $clubId = $this->clubId();
        if ($clubId !== null) {
            $sql .= " AND ri.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY ri.created_at DESC";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Find image with related data.
     */
    public function findWithRelations(int $id): ?array
    {
        $clubId = $this->clubId();
        $sql = "SELECT ri.*, e.name AS event_name, m.first_name, m.last_name, s.name AS sport_name,
                       u.full_name AS uploader_name
                FROM `{$this->table}` ri
                LEFT JOIN events e ON e.id = ri.event_id
                LEFT JOIN members m ON m.id = ri.member_id
                LEFT JOIN sports s ON s.id = ri.sport_id
                LEFT JOIN users u ON u.id = ri.uploaded_by
                WHERE ri.id = ?";
        $params = [$id];

        if ($clubId !== null) {
            $sql .= " AND ri.club_id = ?";
            $params[] = $clubId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Upload an image file and return the relative path.
     */
    public function uploadFile(array $file, int $clubId): ?string
    {
        // Server-side MIME detection — nie ufamy $file['type'] ani $file['name']
        // (browser-supplied, spoofowalne; "evil.php" z Content-Type: image/png
        // przeszlo by stary check i wyladowalo na serwerze jako .php).
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'image/bmp'  => 'bmp',
        ];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']) ?: '';
        if (!isset($mimeToExt[$mime])) {
            return null;
        }
        $ext = $mimeToExt[$mime];

        $dir    = 'uploads/results/' . $clubId;
        $absDir = ROOT_PATH . '/public/' . $dir;
        if (!is_dir($absDir)) {
            mkdir($absDir, 0775, true);
        }

        $filename    = uniqid('result_', true) . '.' . $ext;
        $destination = $absDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return null;
        }

        return $dir . '/' . $filename;
    }
}
