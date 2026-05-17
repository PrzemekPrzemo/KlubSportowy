<?php

declare(strict_types=1);

namespace App\Sports\Badminton\Models;

use App\Models\ClubScopedModel;

/**
 * sport_badminton_member — profil zawodnika badmintona + BWF points.
 */
class BadmintonMemberModel extends ClubScopedModel
{
    protected string $table = 'sport_badminton_member';

    public static array $DISCIPLINES = [
        'singles' => 'Singiel',
        'doubles' => 'Debel',
        'mixed'   => 'Mikst',
    ];

    public static array $HANDS = [
        'right' => 'Prawa',
        'left'  => 'Lewa',
    ];

    public function findByMember(int $memberId): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
              WHERE member_id = ? AND club_id = ?"
        );
        $stmt->execute([$memberId, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Upsert profilu zawodnika.
     * Multi-tenant: wymusza club_id z ClubContext.
     */
    public function upsert(int $memberId, array $data): bool
    {
        $clubId = $this->clubId();
        if ($clubId === null) return false;

        $existing = $this->findByMember($memberId);
        $payload = [
            'discipline'    => $data['discipline']    ?? 'singles',
            'hand'          => $data['hand']          ?? 'right',
            'bwf_points'    => (int)($data['bwf_points'] ?? 0),
            'national_rank' => isset($data['national_rank']) && $data['national_rank'] !== ''
                                ? (int)$data['national_rank'] : null,
        ];

        if ($existing) {
            $stmt = $this->db->prepare(
                "UPDATE `{$this->table}`
                    SET discipline=?, hand=?, bwf_points=?, national_rank=?
                  WHERE member_id=? AND club_id=?"
            );
            return $stmt->execute([
                $payload['discipline'], $payload['hand'],
                $payload['bwf_points'], $payload['national_rank'],
                $memberId, $clubId,
            ]);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}`
                (member_id, club_id, discipline, hand, bwf_points, national_rank)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $memberId, $clubId,
            $payload['discipline'], $payload['hand'],
            $payload['bwf_points'], $payload['national_rank'],
        ]);
    }

    /** Ranking klubowy po BWF points DESC. */
    public function clubRanking(int $limit = 100): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT sbm.*, m.first_name, m.last_name, m.member_number
               FROM `{$this->table}` sbm
               JOIN members m ON m.id = sbm.member_id
              WHERE sbm.club_id = ?
           ORDER BY sbm.bwf_points DESC, sbm.national_rank ASC
              LIMIT " . (int)$limit
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
