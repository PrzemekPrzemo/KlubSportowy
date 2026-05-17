<?php

namespace App\Sports\Support\Models;

use App\Helpers\ClubContext;
use App\Helpers\Database;

/**
 * Profil zeglarza (boat classes, ISAF, ranking krajowy).
 * PK = member_id — jeden profil na czlonka klubu.
 */
class SailingMemberProfileModel
{
    public static array $BOAT_CLASSES = [
        'optimist' => 'Optimist',
        'laser'    => 'Laser',
        '420'      => '420',
        '470'      => '470',
        '49er'     => '49er',
        'finn'     => 'Finn',
        'starship' => 'Starship',
        'dragon'   => 'Dragon',
        'other'    => 'Inna klasa',
    ];

    public function get(int $memberId): ?array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM sport_sailing_member WHERE member_id = ? AND club_id = ?"
        );
        $stmt->execute([$memberId, ClubContext::current()]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(int $memberId, array $data): void
    {
        $clubId = ClubContext::current();
        if (!$clubId) return;
        $boatClasses = $data['boat_classes'] ?? null;
        if (is_array($boatClasses)) {
            $boatClasses = implode(',', array_filter(array_map('strval', $boatClasses)));
        }
        $isaf = !empty($data['isaf_number']) ? (string)$data['isaf_number'] : null;
        $rank = isset($data['national_rank']) && $data['national_rank'] !== '' ? (int)$data['national_rank'] : null;

        $stmt = Database::pdo()->prepare(
            "INSERT INTO sport_sailing_member (member_id, club_id, boat_classes, isaf_number, national_rank)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               boat_classes = VALUES(boat_classes),
               isaf_number = VALUES(isaf_number),
               national_rank = VALUES(national_rank),
               club_id = VALUES(club_id)"
        );
        $stmt->execute([$memberId, $clubId, $boatClasses, $isaf, $rank]);
    }

    public function listForClub(): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT s.*, m.first_name, m.last_name, m.member_number
             FROM sport_sailing_member s
             JOIN members m ON m.id = s.member_id
             WHERE s.club_id = ?
             ORDER BY s.national_rank ASC, m.last_name ASC"
        );
        $stmt->execute([ClubContext::current()]);
        return $stmt->fetchAll();
    }
}
