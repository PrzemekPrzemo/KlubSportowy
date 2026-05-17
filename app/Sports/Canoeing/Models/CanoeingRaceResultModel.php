<?php

namespace App\Sports\Canoeing\Models;

use App\Models\ClubScopedModel;

/**
 * Wyniki wyscigow kajakarskich — czas + kary + ranking.
 */
class CanoeingRaceResultModel extends ClubScopedModel
{
    protected string $table = 'sport_canoeing_race_results';

    public const DISTANCES = [
        200  => '200 m',
        500  => '500 m',
        1000 => '1000 m',
        2000 => '2000 m',
        5000 => '5000 m',
    ];

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT r.*, m.first_name, m.last_name, m.member_number,
                    t.name AS tournament_name, t.date_start AS tournament_date
             FROM `{$this->table}` r
             JOIN members m       ON m.id = r.member_id
             JOIN tournaments t   ON t.id = r.tournament_id
             WHERE r.club_id = ?
             ORDER BY t.date_start DESC, r.distance_m ASC, r.finish_time_ms ASC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function listForTournament(int $tournamentId, ?int $distance = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT r.*, m.first_name, m.last_name, m.member_number
                FROM `{$this->table}` r
                JOIN members m ON m.id = r.member_id
                WHERE r.club_id = ? AND r.tournament_id = ?";
        $params = [$clubId, $tournamentId];
        if ($distance !== null) {
            $sql .= " AND r.distance_m = ?";
            $params[] = $distance;
        }
        $sql .= " ORDER BY r.distance_m ASC, r.finish_time_ms ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listForMember(int $memberId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT r.*, t.name AS tournament_name, t.date_start AS tournament_date
             FROM `{$this->table}` r
             JOIN tournaments t ON t.id = r.tournament_id
             WHERE r.club_id = ? AND r.member_id = ?
             ORDER BY t.date_start DESC, r.distance_m ASC"
        );
        $stmt->execute([$clubId, $memberId]);
        return $stmt->fetchAll();
    }

    public function rerankTournament(int $tournamentId, int $distance, string $boatClass): void
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT id, finish_time_ms, penalties_seconds FROM `{$this->table}`
             WHERE club_id = ? AND tournament_id = ? AND distance_m = ? AND boat_class = ?
             ORDER BY (finish_time_ms + CAST(penalties_seconds * 1000 AS UNSIGNED)) ASC"
        );
        $stmt->execute([$clubId, $tournamentId, $distance, $boatClass]);
        $rows = $stmt->fetchAll();
        $rank = 1;
        $upd  = $this->db->prepare(
            "UPDATE `{$this->table}` SET `rank` = ? WHERE id = ? AND club_id = ?"
        );
        foreach ($rows as $row) {
            $upd->execute([$rank++, (int)$row['id'], $clubId]);
        }
    }

    public static function formatTime(int $ms): string
    {
        $totalSeconds = (int)floor($ms / 1000);
        $remMs = $ms % 1000;
        $h = (int)floor($totalSeconds / 3600);
        $m = (int)floor(($totalSeconds % 3600) / 60);
        $s = $totalSeconds % 60;
        if ($h > 0) {
            return sprintf('%d:%02d:%02d.%03d', $h, $m, $s, $remMs);
        }
        return sprintf('%d:%02d.%03d', $m, $s, $remMs);
    }
}
