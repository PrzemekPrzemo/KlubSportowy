<?php

namespace App\Models;

class MemberLicenseModel extends ClubScopedModel
{
    protected string $table = 'member_licenses';

    public function listForClub(?int $sportId = null, ?string $licenseType = null, int $page = 1, int $perPage = 25): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT ml.*, m.first_name, m.last_name, m.member_number,
                          s.name AS sport_name, f.code AS federation_code,
                          DATEDIFF(ml.valid_until, CURDATE()) AS days_remaining
                   FROM member_licenses ml
                   JOIN members m ON m.id = ml.member_id
                   LEFT JOIN sports s      ON s.id = ml.sport_id
                   LEFT JOIN federations f ON f.id = ml.federation_id
                   WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND ml.club_id = ?"; $params[] = $clubId; }
        if ($sportId !== null) { $sql .= " AND ml.sport_id = ?"; $params[] = $sportId; }
        if ($licenseType !== null && $licenseType !== '') {
            $sql .= " AND ml.license_type = ?";
            $params[] = $licenseType;
        }
        $sql .= " ORDER BY ml.valid_until ASC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function expiringSoon(int $days = 60, ?int $sportId = null): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT ml.*, m.first_name, m.last_name, m.member_number,
                          DATEDIFF(ml.valid_until, CURDATE()) AS days_remaining
                   FROM member_licenses ml
                   JOIN members m ON m.id = ml.member_id
                   WHERE ml.valid_until <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                     AND ml.status = 'aktywna'
                     AND m.status = 'aktywny'";
        $params = [$days];
        if ($clubId !== null) { $sql .= " AND ml.club_id = ?"; $params[] = $clubId; }
        if ($sportId !== null) { $sql .= " AND ml.sport_id = ?"; $params[] = $sportId; }
        $sql .= " ORDER BY ml.valid_until ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
