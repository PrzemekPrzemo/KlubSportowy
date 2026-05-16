<?php
declare(strict_types=1);

namespace App\Models;

/**
 * trainer_availability — cykliczna dostepnosc trenera.
 *
 * Uwaga: extends BaseModel (NIE ClubScopedModel) — trener moze byc cross-club
 * i availability moze byc globalna (club_id NULL). Filtrowanie po club_id
 * robimy jawnie w zapytaniach.
 */
class TrainerAvailabilityModel extends BaseModel
{
    protected string $table = 'trainer_availability';

    /** @return array<int, array<string,mixed>> */
    public function forUser(int $userId, ?int $clubId = null): array
    {
        $sql = "SELECT * FROM trainer_availability WHERE user_id = ?";
        $params = [$userId];
        if ($clubId !== null) {
            $sql .= " AND (club_id IS NULL OR club_id = ?)";
            $params[] = $clubId;
        }
        $sql .= " ORDER BY weekday, time_start";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function deleteAllForUser(int $userId, ?int $clubId = null): void
    {
        $sql = "DELETE FROM trainer_availability WHERE user_id = ?";
        $params = [$userId];
        if ($clubId !== null) {
            // Usuwamy tylko te per-klub; globalne (NULL) zostawiamy w spokoju.
            $sql .= " AND club_id = ?";
            $params[] = $clubId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Bulk-replace availability (najpierw delete, potem insert).
     *
     * @param array<int, array{weekday:int,time_start:string,time_end:string,club_id:?int,valid_from:?string,valid_until:?string}> $slots
     */
    public function replaceForUser(int $userId, array $slots, ?int $scopeClubId = null): void
    {
        $this->db->beginTransaction();
        try {
            $this->deleteAllForUser($userId, $scopeClubId);
            $stmt = $this->db->prepare(
                "INSERT INTO trainer_availability
                    (user_id, club_id, weekday, time_start, time_end, valid_from, valid_until)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            foreach ($slots as $s) {
                $stmt->execute([
                    $userId,
                    $s['club_id'] ?? $scopeClubId,
                    (int)$s['weekday'],
                    (string)$s['time_start'],
                    (string)$s['time_end'],
                    $s['valid_from'] ?? null,
                    $s['valid_until'] ?? null,
                ]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
