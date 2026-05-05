<?php

namespace App\Models;

use App\Helpers\ClubContext;

/**
 * GalleryPhotoModel — photos belong to albums which are club-scoped.
 * We ensure club_id isolation by joining through gallery_albums.
 */
class GalleryPhotoModel extends BaseModel
{
    protected string $table = 'gallery_photos';

    /**
     * All photos for a given album, with club_id verification.
     */
    public function forAlbum(int $albumId): array
    {
        $clubId = ClubContext::current();
        $sql = "SELECT p.*, u.full_name AS uploader_name
                FROM gallery_photos p
                JOIN gallery_albums a ON a.id = p.album_id
                LEFT JOIN users u ON u.id = p.uploaded_by
                WHERE p.album_id = ?";
        $params = [$albumId];
        if ($clubId !== null) {
            $sql .= " AND a.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY p.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Find photo by ID with club_id verification via album.
     */
    public function findById(int $id): ?array
    {
        $clubId = ClubContext::current();
        $sql = "SELECT p.* FROM gallery_photos p
                JOIN gallery_albums a ON a.id = p.album_id
                WHERE p.id = ?";
        $params = [$id];
        if ($clubId !== null) {
            $sql .= " AND a.club_id = ?";
            $params[] = $clubId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Delete photo with club_id verification via album.
     */
    public function delete(int $id): bool
    {
        $clubId = ClubContext::current();
        if ($clubId !== null) {
            // Verify the photo belongs to an album in this club
            $stmt = $this->db->prepare(
                "SELECT p.id FROM gallery_photos p
                 JOIN gallery_albums a ON a.id = p.album_id
                 WHERE p.id = ? AND a.club_id = ?"
            );
            $stmt->execute([$id, $clubId]);
            if (!$stmt->fetchColumn()) {
                return false;
            }
        }
        return parent::delete($id);
    }

    /**
     * Upload helper — moves uploaded file and returns the relative path.
     */
    public function upload(array $file, int $clubId, int $albumId): ?string
    {
        // Walidacja uploadu: error code, real upload, MIME, ext.
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        // MIME wykryty serwer-side (nie ufamy $file['type'] ani extensji
        // z $file['name'] — klient moze wgrac "evil.php" z fake'owym typem).
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']) ?: '';
        if (!isset($mimeToExt[$mime])) {
            return null;
        }
        $ext = $mimeToExt[$mime];

        $dir    = 'uploads/gallery/' . $clubId . '/' . $albumId;
        $absDir = ROOT_PATH . '/public/' . $dir;

        if (!is_dir($absDir)) {
            mkdir($absDir, 0775, true);
        }

        $filename = uniqid('img_', true) . '.' . $ext;
        $dest     = $absDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }

        return $dir . '/' . $filename;
    }
}
