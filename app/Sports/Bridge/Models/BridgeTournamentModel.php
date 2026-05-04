<?php

namespace App\Sports\Bridge\Models;

use App\Models\ClubScopedModel;

class BridgeTournamentModel extends ClubScopedModel
{
    protected string $table = 'bridge_tournaments';

    public static array $TYPES = [
        'para'         => 'Turniej parowy',
        'team'         => 'Turniej teamowy',
        'indywidualny' => 'Indywidualny',
        'mikst'        => 'Mikst',
        'inny'         => 'Inny',
    ];

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*,
                    CONCAT_WS(' / ', p1.last_name, p2.last_name) AS partnership_name,
                    mi.first_name AS member_first, mi.last_name AS member_last
             FROM bridge_tournaments t
             LEFT JOIN bridge_partnerships pp ON pp.id = t.partnership_id
             LEFT JOIN members p1 ON p1.id = pp.player1_id
             LEFT JOIN members p2 ON p2.id = pp.player2_id
             LEFT JOIN members mi ON mi.id = t.member_id
             WHERE t.club_id = ?
             ORDER BY t.tournament_date DESC"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function totalPzbsPoints(int $memberId): float
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(t.pzbs_points), 0) AS total
             FROM bridge_tournaments t
             LEFT JOIN bridge_partnerships p ON p.id = t.partnership_id
             WHERE t.club_id = ?
               AND (t.member_id = ? OR p.player1_id = ? OR p.player2_id = ?)
               AND t.pzbs_points IS NOT NULL"
        );
        $stmt->execute([$this->clubId(), $memberId, $memberId, $memberId]);
        return (float)$stmt->fetchColumn();
    }

    public function tournamentsForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*,
                    CONCAT_WS(' / ', p1.last_name, p2.last_name) AS partnership_name
             FROM bridge_tournaments t
             LEFT JOIN bridge_partnerships pp ON pp.id = t.partnership_id
             LEFT JOIN members p1 ON p1.id = pp.player1_id
             LEFT JOIN members p2 ON p2.id = pp.player2_id
             WHERE t.club_id = ?
               AND (t.member_id = ? OR pp.player1_id = ? OR pp.player2_id = ?)
             ORDER BY t.tournament_date DESC"
        );
        $stmt->execute([$this->clubId(), $memberId, $memberId, $memberId]);
        return $stmt->fetchAll();
    }
}
