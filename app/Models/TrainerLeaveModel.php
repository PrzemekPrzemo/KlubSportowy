<?php
declare(strict_types=1);

namespace App\Models;

/**
 * trainer_leaves — urlopy/nieobecnosci trenera. Globalne (cross-club).
 */
class TrainerLeaveModel extends BaseModel
{
    protected string $table = 'trainer_leaves';

    /** @return array<int, array<string,mixed>> */
    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM trainer_leaves WHERE user_id = ? ORDER BY date_from DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Nadchodzace lub trwajace urlopy. */
    public function upcomingForUser(int $userId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM trainer_leaves
             WHERE user_id = ? AND date_to >= CURDATE()
             ORDER BY date_from ASC LIMIT " . max(1, $limit)
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
