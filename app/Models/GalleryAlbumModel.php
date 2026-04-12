<?php

namespace App\Models;

class GalleryAlbumModel extends ClubScopedModel
{
    protected string $table = 'gallery_albums';

    /**
     * Paginated album list for current club with photo count.
     */
    public function listForClub(int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT a.*, u.full_name AS author_name, s.name AS sport_name, e.name AS event_name,
                          (SELECT COUNT(*) FROM gallery_photos p WHERE p.album_id = a.id) AS photo_count
                   FROM gallery_albums a
                   LEFT JOIN users  u ON u.id = a.created_by
                   LEFT JOIN sports s ON s.id = a.sport_id
                   LEFT JOIN events e ON e.id = a.event_id
                   WHERE 1=1";
        $params = [];
        if ($clubId !== null) {
            $sql     .= " AND a.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY a.created_at DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Single album with its photos.
     */
    public function withPhotos(int $id): ?array
    {
        $album = $this->findById($id);
        if (!$album) {
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT p.*, u.full_name AS uploader_name
             FROM gallery_photos p
             LEFT JOIN users u ON u.id = p.uploaded_by
             WHERE p.album_id = ?
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([$id]);
        $album['photos'] = $stmt->fetchAll();
        return $album;
    }

    /**
     * Public albums for a given club (used on public pages).
     */
    public function publicAlbums(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*,
                    (SELECT COUNT(*) FROM gallery_photos p WHERE p.album_id = a.id) AS photo_count
             FROM gallery_albums a
             WHERE a.club_id = ? AND a.is_public = 1
             ORDER BY a.created_at DESC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
