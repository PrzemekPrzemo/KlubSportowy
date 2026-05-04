<?php

namespace App\Sports\Sailing\Models;

use App\Models\ClubScopedModel;

class SailingRaceModel extends ClubScopedModel
{
    protected string $table = 'sailing_races';

    public function listForClub(?int $year = null): array
    {
        $sql    = "SELECT * FROM sailing_races WHERE club_id = ?";
        $params = [$this->clubId()];
        if ($year !== null) { $sql .= " AND YEAR(race_date) = ?"; $params[] = $year; }
        $sql .= " ORDER BY race_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function seasonStandings(int $year): array
    {
        $races = $this->listForClub($year);
        $standings = [];
        foreach ($races as $race) {
            $results = is_string($race['results']) ? json_decode($race['results'], true) : ($race['results'] ?? []);
            if (!is_array($results)) continue;
            foreach ($results as $entry) {
                $boatId = $entry['boat_id'] ?? null;
                if (!$boatId) continue;
                if (!isset($standings[$boatId])) {
                    $standings[$boatId] = ['boat_id' => $boatId, 'boat_name' => $entry['boat_name'] ?? '?', 'races' => 0, 'points' => 0];
                }
                $standings[$boatId]['races']++;
                $standings[$boatId]['points'] += (int)($entry['points'] ?? 0);
            }
        }
        usort($standings, fn($a, $b) => $b['points'] <=> $a['points']);
        return $standings;
    }

    public function racesForMember(int $memberId, int $limit = 10): array
    {
        // Returns races where member participated (via sailing_crew + boat results)
        $stmt = $this->db->prepare(
            "SELECT DISTINCT r.*, sc.role
             FROM sailing_races r
             JOIN sailing_crew sc ON sc.club_id = r.club_id
             WHERE r.club_id = ? AND sc.member_id = ?
             ORDER BY r.race_date DESC
             LIMIT ?"
        );
        $stmt->execute([$this->clubId(), $memberId, $limit]);
        return $stmt->fetchAll();
    }
}
