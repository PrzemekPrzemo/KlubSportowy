<?php
declare(strict_types=1);

namespace App\Models;

/**
 * trainer_schedule_conflicts — audyt wykrytych konfliktow.
 */
class TrainerScheduleConflictModel extends BaseModel
{
    protected string $table = 'trainer_schedule_conflicts';

    /** @return array<int, array<string,mixed>> */
    public function unresolvedForClub(int $clubId, int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, u.full_name AS trainer_name, t.name AS training_name
             FROM trainer_schedule_conflicts c
             LEFT JOIN users u     ON u.id = c.user_id
             LEFT JOIN trainings t ON t.id = c.training_id
             WHERE c.club_id = ? AND c.resolved = 0
             ORDER BY c.detected_at DESC
             LIMIT " . max(1, $limit)
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function unresolvedForUser(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, t.name AS training_name
             FROM trainer_schedule_conflicts c
             LEFT JOIN trainings t ON t.id = c.training_id
             WHERE c.user_id = ? AND c.resolved = 0
             ORDER BY c.detected_at DESC
             LIMIT " . max(1, $limit)
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function markResolved(int $id, int $clubId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE trainer_schedule_conflicts SET resolved = 1
             WHERE id = ? AND club_id = ?"
        );
        return $stmt->execute([$id, $clubId]);
    }
}
