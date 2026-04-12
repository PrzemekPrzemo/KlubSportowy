<?php

namespace App\Models;

class GalleryPhotoModel extends BaseModel
{
    protected string $table = 'gallery_photos';

    /**
     * All photos for a given album.
     */
    public function forAlbum(int $albumId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, u.full_name AS uploader_name
             FROM gallery_photos p
             LEFT JOIN users u ON u.id = p.uploaded_by
             WHERE p.album_id = ?
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([$albumId]);
        return $stmt->fetchAll();
    }

    /**
     * Upload helper — moves uploaded file and returns the relative path.
     */
    public function upload(array $file, int $clubId, int $albumId): ?string
    {
        $dir = 'uploads/gallery/' . $clubId . '/' . $albumId;
        $absDir = ROOT_PATH . '/public/' . $dir;

        if (!is_dir($absDir)) {
            mkdir($absDir, 0775, true);
        }

        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return null;
        }

        $filename = uniqid('img_', true) . '.' . $ext;
        $dest     = $absDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return null;
        }

        return $dir . '/' . $filename;
    }
}
