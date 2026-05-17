<?php

namespace App\Sports\Support\Models;

use App\Models\ClubScopedModel;

class SailingRegattaRaceModel extends ClubScopedModel
{
    protected string $table = 'sport_sailing_regatta_races';

    public static array $STATUSES = [
        'finished' => 'Ukończono',
        'DNS'      => 'DNS — nie wystartował',
        'DNF'      => 'DNF — nie ukończył',
        'DSQ'      => 'DSQ — dyskwalifikacja',
        'OCS'      => 'OCS — falstart',
        'RDG'      => 'RDG — punkty wynegocjowane',
    ];

    public function listForClub(?int $tournamentId = null, ?string $boatClass = null, int $limit = 200): array
    {
        $sql = "SELECT r.*, m.first_name, m.last_name, m.member_number
                FROM sport_sailing_regatta_races r
                JOIN members m ON m.id = r.member_id
                WHERE r.club_id = ?";
        $params = [$this->clubId()];
        if ($tournamentId !== null) {
            $sql .= " AND r.tournament_id = ?";
            $params[] = $tournamentId;
        }
        if ($boatClass !== null && $boatClass !== '') {
            $sql .= " AND r.boat_class = ?";
            $params[] = $boatClass;
        }
        $sql .= " ORDER BY r.race_date DESC, r.race_number ASC LIMIT " . max(1, (int)$limit);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Low-point scoring z odrzuceniem N najgorszych wyników:
     *   suma punktów - sum(N najgorszych race-points).
     * Wynik mniejszy = lepiej.
     */
    public function regattaStandings(?int $tournamentId, ?string $boatClass = null, int $dropWorst = 1): array
    {
        $sql = "SELECT r.member_id, m.first_name, m.last_name, m.member_number,
                       COUNT(*) AS races_count, SUM(r.points) AS total_raw,
                       GROUP_CONCAT(r.points ORDER BY r.points DESC) AS points_csv
                FROM sport_sailing_regatta_races r
                JOIN members m ON m.id = r.member_id
                WHERE r.club_id = ?";
        $params = [$this->clubId()];
        if ($tournamentId !== null) {
            $sql .= " AND r.tournament_id = ?";
            $params[] = $tournamentId;
        }
        if ($boatClass !== null && $boatClass !== '') {
            $sql .= " AND r.boat_class = ?";
            $params[] = $boatClass;
        }
        $sql .= " GROUP BY r.member_id, m.first_name, m.last_name, m.member_number";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $pts = array_map('floatval', explode(',', (string)$row['points_csv']));
            rsort($pts);
            $drop = 0.0;
            for ($i = 0; $i < $dropWorst && $i < count($pts); $i++) {
                $drop += $pts[$i];
            }
            $row['drop_worst'] = round($drop, 2);
            $row['total_net'] = round((float)$row['total_raw'] - $drop, 2);
            unset($row['points_csv']);
        }
        unset($row);
        usort($rows, fn($a, $b) => $a['total_net'] <=> $b['total_net']);
        return $rows;
    }
}
