<?php

namespace App\Sports\Support\Models;

use App\Helpers\ClubContext;
use App\Helpers\Database;

class CrossFitWodLibraryModel
{
    public static array $TYPES = [
        'for_time'    => 'For Time',
        'amrap'       => 'AMRAP',
        'rounds_reps' => 'Rounds + reps',
        'max_load'    => 'Max Load',
        'strength'    => 'Strength',
    ];

    /**
     * Zwroci WODy globalne (club_id IS NULL) + klubowe (club_id = current).
     */
    public function listAvailable(): array
    {
        $clubId = ClubContext::current();
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM sport_crossfit_wods
             WHERE club_id IS NULL OR club_id = ?
             ORDER BY (club_id IS NULL) DESC, name ASC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function listClubOnly(): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM sport_crossfit_wods WHERE club_id = ? ORDER BY name ASC"
        );
        $stmt->execute([ClubContext::current()]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $clubId = ClubContext::current();
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM sport_crossfit_wods
             WHERE id = ? AND (club_id IS NULL OR club_id = ?)"
        );
        $stmt->execute([$id, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createClubWod(array $data): int
    {
        $clubId = ClubContext::current();
        if (!$clubId) return 0;
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') return 0;
        $type = array_key_exists($data['type'] ?? '', self::$TYPES) ? $data['type'] : 'for_time';
        $cap  = isset($data['time_cap_minutes']) && $data['time_cap_minutes'] !== ''
                  ? (int)$data['time_cap_minutes'] : null;

        $stmt = Database::pdo()->prepare(
            "INSERT INTO sport_crossfit_wods
             (club_id, name, description, type, time_cap_minutes, scaling_rules)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $clubId,
            $name,
            !empty($data['description']) ? (string)$data['description'] : null,
            $type,
            $cap,
            !empty($data['scaling_rules']) ? (string)$data['scaling_rules'] : null,
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    public function deleteClubWod(int $id): bool
    {
        $clubId = ClubContext::current();
        if (!$clubId) return false;
        $stmt = Database::pdo()->prepare(
            "DELETE FROM sport_crossfit_wods WHERE id = ? AND club_id = ?"
        );
        return $stmt->execute([$id, $clubId]);
    }
}
