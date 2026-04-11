<?php

namespace App\Sports\Shooting\Models;

use App\Models\ClubScopedModel;

class WeaponModel extends ClubScopedModel
{
    protected string $table = 'weapons';

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT w.*, m.first_name AS holder_first, m.last_name AS holder_last
                FROM weapons w
                LEFT JOIN members m ON m.id = w.current_holder_id";
        $params = [];
        if ($clubId !== null) {
            $sql .= " WHERE w.club_id = ?";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY w.category, w.brand, w.model";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function assignTo(int $weaponId, int $memberId, ?string $purpose = null, ?int $userId = null): void
    {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE weapons SET current_holder_id = ? WHERE id = ?");
            $stmt->execute([$memberId, $weaponId]);

            $stmt = $db->prepare(
                "INSERT INTO weapon_assignments (weapon_id, member_id, purpose, created_by)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$weaponId, $memberId, $purpose, $userId]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function returnWeapon(int $weaponId): void
    {
        $db = $this->db;
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                "UPDATE weapon_assignments SET returned_at = NOW()
                 WHERE weapon_id = ? AND returned_at IS NULL"
            );
            $stmt->execute([$weaponId]);

            $stmt = $db->prepare("UPDATE weapons SET current_holder_id = NULL WHERE id = ?");
            $stmt->execute([$weaponId]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function historyForWeapon(int $weaponId): array
    {
        $stmt = $this->db->prepare(
            "SELECT wa.*, m.first_name, m.last_name, m.member_number
             FROM weapon_assignments wa
             JOIN members m ON m.id = wa.member_id
             WHERE wa.weapon_id = ?
             ORDER BY wa.issued_at DESC"
        );
        $stmt->execute([$weaponId]);
        return $stmt->fetchAll();
    }
}
