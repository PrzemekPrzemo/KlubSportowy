<?php

namespace App\Sports\Chess\Models;

use App\Models\ClubScopedModel;

class ChessRatingModel extends ClubScopedModel
{
    protected string $table = 'chess_ratings';

    public static array $TYPES = [
        'fide_classical' => 'FIDE Classical',
        'fide_rapid'     => 'FIDE Rapid',
        'fide_blitz'     => 'FIDE Blitz',
        'pzszach'        => 'PZSzach',
        'elo_internal'   => 'ELO wewnętrzne',
    ];

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT cr.*, m.first_name, m.last_name, m.member_number
                FROM chess_ratings cr
                JOIN members m ON m.id = cr.member_id
                WHERE cr.club_id = ?
                ORDER BY m.last_name, cr.rating_type, cr.rating_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function latestRatings(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT cr.*, m.first_name, m.last_name, m.member_number
                FROM chess_ratings cr
                JOIN members m ON m.id = cr.member_id
                WHERE cr.club_id = ?
                  AND cr.id = (
                      SELECT id FROM chess_ratings cr2
                      WHERE cr2.club_id = cr.club_id
                        AND cr2.member_id = cr.member_id
                        AND cr2.rating_type = cr.rating_type
                      ORDER BY cr2.rating_date DESC, cr2.id DESC
                      LIMIT 1
                  )
                ORDER BY m.last_name, cr.rating_type";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function historyForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM chess_ratings
             WHERE club_id = ? AND member_id = ?
             ORDER BY rating_type, rating_date DESC, id DESC"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetchAll();
    }
}
