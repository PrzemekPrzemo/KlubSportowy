<?php

namespace App\Sports\Esport\Models;

use App\Models\ClubScopedModel;

/**
 * Profile graczy esportowych — per (member_id, game_code).
 */
class EsportMemberProfileModel extends ClubScopedModel
{
    protected string $table = 'sport_esport_member_profiles';

    public function listForClub(?string $gameCode = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT p.*, m.first_name, m.last_name, m.member_number,
                       g.display_name AS game_display_name, g.genre AS game_genre
                FROM `{$this->table}` p
                JOIN members m              ON m.id = p.member_id
                LEFT JOIN sport_esport_games g
                       ON g.game_code = p.game_code
                      AND (g.club_id IS NULL OR g.club_id = p.club_id)
                WHERE p.club_id = ?";
        $params = [$clubId];
        if ($gameCode !== null && $gameCode !== '') {
            $sql .= " AND p.game_code = ?";
            $params[] = $gameCode;
        }
        $sql .= " ORDER BY p.elo_rating DESC, p.wins DESC, m.last_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listForMember(int $memberId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT p.*, g.display_name AS game_display_name, g.genre AS game_genre
             FROM `{$this->table}` p
             LEFT JOIN sport_esport_games g
                    ON g.game_code = p.game_code
                   AND (g.club_id IS NULL OR g.club_id = p.club_id)
             WHERE p.member_id = ? AND p.club_id = ?
             ORDER BY p.elo_rating DESC"
        );
        $stmt->execute([$memberId, $clubId]);
        return $stmt->fetchAll();
    }

    public function leaderboard(string $gameCode, int $limit = 20): array
    {
        $limit = max(1, min(200, $limit));
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT p.*, m.first_name, m.last_name, m.member_number
             FROM `{$this->table}` p
             JOIN members m ON m.id = p.member_id
             WHERE p.club_id = ? AND p.game_code = ?
             ORDER BY p.elo_rating DESC, p.wins DESC
             LIMIT {$limit}"
        );
        $stmt->execute([$clubId, $gameCode]);
        return $stmt->fetchAll();
    }

    public function findForMemberGame(int $memberId, string $gameCode): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE member_id = ? AND club_id = ? AND game_code = ?
             LIMIT 1"
        );
        $stmt->execute([$memberId, $clubId, $gameCode]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsertProfile(int $memberId, string $gameCode, array $data): int
    {
        $existing = $this->findForMemberGame($memberId, $gameCode);
        $allowed = [
            'in_game_name' => trim((string)($data['in_game_name'] ?? '')),
            'platform'     => array_key_exists($data['platform'] ?? '', EsportGameModel::PLATFORMS) ? $data['platform'] : 'pc',
            'rank_tier'    => isset($data['rank_tier']) && $data['rank_tier'] !== '' ? trim((string)$data['rank_tier']) : null,
            'stream_url'   => isset($data['stream_url']) && $data['stream_url'] !== '' ? trim((string)$data['stream_url']) : null,
        ];
        if (isset($data['elo_rating']))   $allowed['elo_rating']   = (int)$data['elo_rating'];
        if (isset($data['hours_played'])) $allowed['hours_played'] = (int)$data['hours_played'];
        if (isset($data['wins']))         $allowed['wins']         = (int)$data['wins'];
        if (isset($data['losses']))       $allowed['losses']       = (int)$data['losses'];

        if ($existing !== null) {
            $this->update((int)$existing['id'], $allowed);
            return (int)$existing['id'];
        }
        $allowed['member_id'] = $memberId;
        $allowed['game_code'] = $gameCode;
        if (!isset($allowed['elo_rating'])) $allowed['elo_rating'] = 1000;
        return $this->insert($allowed);
    }
}
