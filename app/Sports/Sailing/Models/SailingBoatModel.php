<?php

namespace App\Sports\Sailing\Models;

use App\Models\ClubScopedModel;

class SailingBoatModel extends ClubScopedModel
{
    protected string $table = 'sailing_boats';

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, m.first_name AS owner_first, m.last_name AS owner_last
             FROM sailing_boats b
             LEFT JOIN members m ON m.id = b.owner_member_id
             WHERE b.club_id = ?
             ORDER BY b.name"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function expiringInsurance(int $days = 30): array
    {
        $threshold = date('Y-m-d', strtotime("+{$days} days"));
        $stmt = $this->db->prepare(
            "SELECT b.*, m.first_name AS owner_first, m.last_name AS owner_last
             FROM sailing_boats b
             LEFT JOIN members m ON m.id = b.owner_member_id
             WHERE b.club_id = ? AND b.insurance_expiry <= ?
             ORDER BY b.insurance_expiry"
        );
        $stmt->execute([$this->clubId(), $threshold]);
        return $stmt->fetchAll();
    }

    public function crewForBoat(int $boatId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, m.first_name, m.last_name, m.member_number
             FROM sailing_crew c
             JOIN members m ON m.id = c.member_id
             WHERE c.boat_id = ? AND c.club_id = ?
             ORDER BY c.role, m.last_name"
        );
        $stmt->execute([$boatId, $this->clubId()]);
        return $stmt->fetchAll();
    }

    public function addCrew(int $boatId, int $memberId, string $role = 'crew', bool $permanent = false): void
    {
        $this->db->prepare(
            "INSERT IGNORE INTO sailing_crew (club_id, boat_id, member_id, role, is_permanent, joined_at)
             VALUES (?,?,?,?,?,?)"
        )->execute([$this->clubId(), $boatId, $memberId, $role, $permanent ? 1 : 0, date('Y-m-d')]);
    }

    public function removeCrew(int $boatId, int $memberId): void
    {
        $this->db->prepare(
            "DELETE FROM sailing_crew WHERE boat_id=? AND member_id=? AND club_id=?"
        )->execute([$boatId, $memberId, $this->clubId()]);
    }

    public function boatsForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT b.*, c.role, c.is_permanent
             FROM sailing_crew c
             JOIN sailing_boats b ON b.id = c.boat_id
             WHERE c.member_id = ? AND c.club_id = ?
             ORDER BY c.role"
        );
        $stmt->execute([$memberId, $this->clubId()]);
        return $stmt->fetchAll();
    }
}
