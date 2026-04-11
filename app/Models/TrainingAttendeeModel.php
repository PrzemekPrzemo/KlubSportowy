<?php

namespace App\Models;

class TrainingAttendeeModel extends BaseModel
{
    protected string $table = 'training_attendees';

    public function register(int $trainingId, int $memberId): int
    {
        $sql = "INSERT INTO training_attendees (training_id, member_id, status)
                VALUES (?, ?, 'zapisany')
                ON DUPLICATE KEY UPDATE status = 'zapisany'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$trainingId, $memberId]);
        return (int)$this->db->lastInsertId();
    }

    public function unregister(int $trainingId, int $memberId): void
    {
        $stmt = $this->db->prepare(
            "DELETE FROM training_attendees WHERE training_id = ? AND member_id = ?"
        );
        $stmt->execute([$trainingId, $memberId]);
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare("UPDATE training_attendees SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    public function countForTraining(int $trainingId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM training_attendees WHERE training_id = ? AND status IN ('zapisany','obecny')"
        );
        $stmt->execute([$trainingId]);
        return (int)$stmt->fetchColumn();
    }
}
