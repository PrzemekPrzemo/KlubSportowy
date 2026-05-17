<?php

namespace App\Sports\Boxing\Models;

use App\Models\ClubScopedModel;

/**
 * Historia wazenia bokserow — sluzy do trackowania zmian kategorii wagowej.
 */
class BoxingWeightHistoryModel extends ClubScopedModel
{
    protected string $table = 'sport_boxing_weight_history';

    public function listForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sport_boxing_weight_history
             WHERE club_id = ? AND member_id = ?
             ORDER BY measured_at DESC, id DESC"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetchAll();
    }

    public function add(int $memberId, float $weightKg, ?string $weightClass, string $measuredAt, ?string $notes = null): int
    {
        return $this->insert([
            'member_id'    => $memberId,
            'weight_kg'    => $weightKg,
            'weight_class' => $weightClass,
            'measured_at'  => $measuredAt,
            'notes'        => $notes,
        ]);
    }

    public function memberBelongsToClub(int $memberId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM members WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$memberId, (int)$this->clubId()]);
        return (bool)$stmt->fetchColumn();
    }
}
