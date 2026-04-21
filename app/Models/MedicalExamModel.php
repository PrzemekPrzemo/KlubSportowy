<?php

namespace App\Models;

use App\Models\Traits\EncryptsFields;

class MedicalExamModel extends ClubScopedModel
{
    use EncryptsFields;

    protected string $table = 'member_medical_exams';

    /** Pola z danymi szczególnej kategorii (RODO art. 9). */
    protected static array $ENCRYPTED_FIELDS = ['doctor_name', 'notes', 'document_path'];

    public function insert(array $data): int
    {
        $data = $this->encryptFields($data);
        return parent::insert($data);
    }

    public function update(int $id, array $data): bool
    {
        $data = $this->encryptFields($data);
        return parent::update($id, $data);
    }

    public function findById(int $id): ?array
    {
        return $this->decryptRow(parent::findById($id));
    }

    public function listForClub(?int $memberId = null, int $page = 1, int $perPage = 25): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT me.*, m.first_name, m.last_name, m.member_number,
                          DATEDIFF(me.valid_until, CURDATE()) AS days_remaining
                   FROM member_medical_exams me
                   JOIN members m ON m.id = me.member_id
                   WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND me.club_id = ?"; $params[] = $clubId; }
        if ($memberId !== null) { $sql .= " AND me.member_id = ?"; $params[] = $memberId; }
        $sql .= " ORDER BY me.valid_until DESC";
        $result = $this->paginate($sql, $params, $page, $perPage);
        if (isset($result['data'])) {
            $result['data'] = $this->decryptRows($result['data']);
        }
        return $result;
    }

    public function expiringSoon(int $days = 30): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT me.*, m.first_name, m.last_name, m.member_number,
                          DATEDIFF(me.valid_until, CURDATE()) AS days_remaining
                   FROM member_medical_exams me
                   JOIN members m ON m.id = me.member_id
                   WHERE me.valid_until BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                             AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                     AND m.status = 'aktywny'";
        $params = [$days];
        if ($clubId !== null) { $sql .= " AND me.club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY me.valid_until ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->decryptRows($stmt->fetchAll());
    }

    public function latestForMember(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM member_medical_exams WHERE member_id = ? ORDER BY valid_until DESC LIMIT 1"
        );
        $stmt->execute([$memberId]);
        $row = $stmt->fetch();
        return $this->decryptRow($row ?: null);
    }
}
