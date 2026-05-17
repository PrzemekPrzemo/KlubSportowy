<?php

namespace App\Sports\Fencing\Models;

use App\Models\ClubScopedModel;

/**
 * Pools (group stage) szermiercze przed direct elimination.
 * Tabela `sport_fencing_pools`. FK do `tournaments`.
 */
class FencingPoolModel extends ClubScopedModel
{
    protected string $table = 'sport_fencing_pools';

    public function listForTournament(int $tournamentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM sport_fencing_pools
             WHERE club_id = ? AND tournament_id = ?
             ORDER BY pool_number"
        );
        $stmt->execute([$this->clubId(), $tournamentId]);
        return $stmt->fetchAll();
    }

    public function create(int $tournamentId, int $poolNumber, string $weapon): int
    {
        if (!array_key_exists($weapon, FencingMemberModel::$WEAPONS)) {
            $weapon = 'epee';
        }
        return $this->insert([
            'tournament_id' => $tournamentId,
            'pool_number'   => max(1, $poolNumber),
            'weapon'        => $weapon,
        ]);
    }

    public function tournamentBelongsToClub(int $tournamentId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM tournaments WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$tournamentId, (int)$this->clubId()]);
        return (bool)$stmt->fetchColumn();
    }
}
