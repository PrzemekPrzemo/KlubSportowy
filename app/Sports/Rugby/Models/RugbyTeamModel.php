<?php

namespace App\Sports\Rugby\Models;

use App\Models\ClubScopedModel;

class RugbyTeamModel extends ClubScopedModel
{
    protected string $table = 'rugby_teams';

    public static array $CATEGORIES = [
        'senior_m' => 'Seniorzy (M)', 'senior_k' => 'Seniorzy (K)',
        'junior_m' => 'Juniorzy (M)', 'junior_k' => 'Juniorki (K)',
        'U18' => 'U18', 'U16' => 'U16', 'U14' => 'U14', 'dzieci' => 'Dzieci',
    ];

    public static array $FORMATS = [
        '15s'   => 'Rugby 15',
        '7s'    => 'Rugby 7',
        'touch' => 'Touch rugby',
    ];

    public static array $POSITIONS = [
        'filar'           => 'Filar (prop)',
        'hooker'          => 'Hooker',
        'młynarz'         => 'Młynarz (lock)',
        'flanker'         => 'Flanker',
        'numer_8'         => 'Numer 8',
        'łącznik_młyna'   => 'Łącznik młyna (scrum-half)',
        'łącznik_ataku'   => 'Łącznik ataku (fly-half)',
        'środkowy'        => 'Środkowy (centre)',
        'skrzydłowy'      => 'Skrzydłowy (wing)',
        'pełny'           => 'Pełny (fullback)',
        'uniwersalny'     => 'Uniwersalny',
    ];

    public function listForClub(): array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*,
                    (SELECT COUNT(*) FROM rugby_players p WHERE p.team_id = t.id) AS player_count,
                    c.first_name AS coach_first, c.last_name AS coach_last
             FROM rugby_teams t
             LEFT JOIN members c ON c.id = t.coach_id
             WHERE t.club_id = ?
             ORDER BY t.category, t.name"
        );
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function roster(int $teamId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, m.first_name, m.last_name, m.member_number
             FROM rugby_players p
             JOIN members m ON m.id = p.member_id
             WHERE p.team_id = ? AND p.club_id = ?
             ORDER BY p.jersey_number, m.last_name"
        );
        $stmt->execute([$teamId, $this->clubId()]);
        return $stmt->fetchAll();
    }

    public function playerTeam(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT t.*, p.position, p.jersey_number, p.is_captain
             FROM rugby_teams t
             JOIN rugby_players p ON p.team_id = t.id
             WHERE p.member_id = ? AND t.club_id = ? LIMIT 1"
        );
        $stmt->execute([$memberId, $this->clubId()]);
        return $stmt->fetch() ?: null;
    }
}
