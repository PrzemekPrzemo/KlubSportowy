<?php

namespace App\Sports\Esport\Models;

use App\Models\ClubScopedModel;

/**
 * Detale meczu esportowego — rozszerzenie `tournament_matches` o pola gry.
 */
class EsportMatchDetailsModel extends ClubScopedModel
{
    protected string $table = 'sport_esport_match_details';

    public function listForTournament(int $tournamentId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT d.*, tm.round, tm.match_number, tm.player1_id, tm.player2_id, tm.winner_id
             FROM `{$this->table}` d
             JOIN tournament_matches tm ON tm.id = d.match_id
             WHERE d.club_id = ? AND tm.tournament_id = ?
             ORDER BY tm.round ASC, tm.match_number ASC"
        );
        $stmt->execute([$clubId, $tournamentId]);
        return $stmt->fetchAll();
    }

    public function findForMatch(int $matchId): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE match_id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$matchId, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsertForMatch(int $matchId, string $gameCode, array $data): int
    {
        $existing = $this->findForMatch($matchId);
        $allowed = [
            'game_code'    => $gameCode,
            'map_name'     => isset($data['map_name']) && $data['map_name'] !== '' ? trim((string)$data['map_name']) : null,
            'duration_min' => isset($data['duration_min']) && $data['duration_min'] !== '' ? (int)$data['duration_min'] : null,
            'home_score'   => isset($data['home_score']) && $data['home_score'] !== '' ? (int)$data['home_score'] : null,
            'away_score'   => isset($data['away_score']) && $data['away_score'] !== '' ? (int)$data['away_score'] : null,
            'stream_url'   => isset($data['stream_url']) && $data['stream_url'] !== '' ? trim((string)$data['stream_url']) : null,
            'vod_url'      => isset($data['vod_url']) && $data['vod_url'] !== '' ? trim((string)$data['vod_url']) : null,
        ];
        if ($existing !== null) {
            $this->update((int)$existing['id'], $allowed);
            return (int)$existing['id'];
        }
        $allowed['match_id'] = $matchId;
        return $this->insert($allowed);
    }
}
