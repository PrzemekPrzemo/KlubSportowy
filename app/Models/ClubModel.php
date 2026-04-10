<?php

namespace App\Models;

class ClubModel extends BaseModel
{
    protected string $table = 'clubs';

    public function listActive(): array
    {
        $stmt = $this->db->query("SELECT * FROM `clubs` WHERE is_active = 1 ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function search(string $q = '', int $page = 1, int $perPage = 20): array
    {
        $sql    = "SELECT * FROM `clubs`";
        $params = [];
        if ($q !== '') {
            $sql     .= " WHERE name LIKE ? OR city LIKE ? OR short_name LIKE ?";
            $like     = '%' . $q . '%';
            $params   = [$like, $like, $like];
        }
        $sql .= " ORDER BY name ASC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function stats(int $clubId): array
    {
        $out = ['members' => 0, 'sports' => 0, 'events_upcoming' => 0, 'payments_total' => 0.0];

        $out['members'] = (int)$this->db->query(
            "SELECT COUNT(*) FROM members WHERE club_id = " . (int)$clubId . " AND status='aktywny'"
        )->fetchColumn();

        $out['sports'] = (int)$this->db->query(
            "SELECT COUNT(*) FROM club_sports WHERE club_id = " . (int)$clubId . " AND is_active = 1"
        )->fetchColumn();

        $out['events_upcoming'] = (int)$this->db->query(
            "SELECT COUNT(*) FROM events WHERE club_id = " . (int)$clubId . " AND event_date >= NOW()"
        )->fetchColumn();

        $out['payments_total'] = (float)$this->db->query(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE club_id = " . (int)$clubId
            . " AND YEAR(payment_date) = YEAR(CURDATE())"
        )->fetchColumn();

        return $out;
    }
}
