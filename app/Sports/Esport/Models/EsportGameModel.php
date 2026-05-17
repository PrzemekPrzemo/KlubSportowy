<?php

namespace App\Sports\Esport\Models;

use App\Helpers\ClubContext;
use App\Models\ClubScopedModel;

/**
 * Katalog gier esportowych.
 *
 * Multi-tenant: `club_id` IS NULL = wpis globalny (widoczny dla kazdego klubu),
 * `club_id` = INT = wpis klubowy wlasny. Listowanie zwraca SUMA: globalne + wlasne klubu.
 */
class EsportGameModel extends ClubScopedModel
{
    protected string $table = 'sport_esport_games';

    public const GENRES = [
        'fps'           => 'FPS',
        'moba'          => 'MOBA',
        'sports'        => 'Sportowe',
        'rts'           => 'RTS',
        'fighting'      => 'Bijatyki',
        'battle_royale' => 'Battle Royale',
        'racing'        => 'Wyscigi',
        'other'         => 'Inne',
    ];

    public const RANKING_SYSTEMS = [
        'elo'         => 'ELO',
        'points'      => 'Punkty',
        'league_rank' => 'Rangi (Bronze..Diamond)',
        'custom'      => 'Inny',
    ];

    public const FORMATS = [
        'single_elim' => 'Single elimination',
        'double_elim' => 'Double elimination',
        'round_robin' => 'Round robin',
        'swiss'       => 'Swiss',
        'bracket_8'   => 'Bracket 8',
        'bracket_16'  => 'Bracket 16',
    ];

    public const PLATFORMS = [
        'pc'          => 'PC',
        'xbox'        => 'Xbox',
        'playstation' => 'PlayStation',
        'switch'      => 'Nintendo Switch',
        'mobile'      => 'Mobile',
        'other'       => 'Inne',
    ];

    /**
     * Listuje gry dostepne w klubie: globalne (club_id IS NULL) + klubowe.
     */
    public function listAvailableForClub(): array
    {
        $clubId = ClubContext::current();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE (club_id IS NULL OR club_id = ?)
               AND active = 1
             ORDER BY (club_id IS NULL) ASC, display_name ASC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function findByCode(string $code): ?array
    {
        $clubId = ClubContext::current();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE game_code = ?
               AND (club_id IS NULL OR club_id = ?)
             ORDER BY (club_id IS NULL) ASC
             LIMIT 1"
        );
        $stmt->execute([$code, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Dodanie wlasnej gry klubowej (club_id z ClubContext via ClubScopedModel::insert).
     */
    public function addClubGame(array $data): int
    {
        $data['game_code']      = strtolower(trim($data['game_code'] ?? ''));
        $data['display_name']   = trim($data['display_name'] ?? '');
        $data['genre']          = array_key_exists($data['genre'] ?? '', self::GENRES) ? $data['genre'] : 'other';
        $data['ranking_system'] = array_key_exists($data['ranking_system'] ?? '', self::RANKING_SYSTEMS) ? $data['ranking_system'] : 'elo';
        $data['default_format'] = array_key_exists($data['default_format'] ?? '', self::FORMATS) ? $data['default_format'] : 'single_elim';
        $data['team_size']      = max(1, (int)($data['team_size'] ?? 1));
        $data['active']         = isset($data['active']) ? (int)(bool)$data['active'] : 1;
        unset($data['id']);
        return $this->insert($data);
    }

    public function deactivate(int $id): bool
    {
        return $this->update($id, ['active' => 0]);
    }
}
