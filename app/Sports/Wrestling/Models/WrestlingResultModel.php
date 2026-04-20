<?php

namespace App\Sports\Wrestling\Models;

use App\Models\ClubScopedModel;

class WrestlingResultModel extends ClubScopedModel
{
    protected string $table = 'wrestling_results';

    public static array $STYLES = [
        'freestyle'   => 'Wolny (Freestyle)',
        'greco_roman' => 'Klasyczny (Greco-Roman)',
        'women'       => "Kobiety (Women's Freestyle)",
    ];

    /** UWW senior men freestyle weight classes */
    public static array $WEIGHT_CLASSES_MEN = ['-57','-65','-74','-86','-97','-125'];

    /** UWW senior women freestyle weight classes */
    public static array $WEIGHT_CLASSES_WOMEN = ['-50','-53','-57','-62','-68','-76'];

    /** UWW senior men Greco-Roman weight classes */
    public static array $WEIGHT_CLASSES_GRECO = ['-60','-67','-77','-87','-97','-130'];

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT wr.*, m.first_name, m.last_name, m.member_number
                FROM wrestling_results wr
                JOIN members m ON m.id = wr.member_id
                WHERE wr.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) {
            $sql .= " AND wr.member_id = ?";
            $params[] = $memberId;
        }
        $sql .= " ORDER BY wr.competition_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
