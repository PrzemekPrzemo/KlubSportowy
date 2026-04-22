<?php

namespace App\Sports\Mma\Models;

use App\Models\ClubScopedModel;

class MmaResultModel extends ClubScopedModel
{
    protected string $table = 'mma_results';

    public static array $RESULTS = [
        'W'  => ['label' => 'Wygrana',    'class' => 'success'],
        'L'  => ['label' => 'Porażka',    'class' => 'danger'],
        'D'  => ['label' => 'Remis',      'class' => 'secondary'],
        'NC' => ['label' => 'No Contest', 'class' => 'warning'],
    ];

    public static array $METHODS = [
        'KO'                  => 'KO (knockout)',
        'TKO'                 => 'TKO',
        'submission'          => 'Submission (poddanie)',
        'decision_unanimous'  => 'Decyzja jednogłośna',
        'decision_split'      => 'Decyzja większościowa',
        'decision_majority'   => 'Decyzja podzielona',
        'DQ'                  => 'Dyskwalifikacja',
        'NC'                  => 'No Contest',
    ];

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT r.*, m.first_name, m.last_name, m.member_number
                FROM mma_results r
                JOIN members m ON m.id = r.member_id
                WHERE r.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) { $sql .= " AND r.member_id = ?"; $params[] = $memberId; }
        $sql .= " ORDER BY r.event_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function recordForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT result, COUNT(*) AS cnt
             FROM mma_results
             WHERE club_id = ? AND member_id = ? AND result IS NOT NULL
             GROUP BY result"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        $rec = ['W' => 0, 'L' => 0, 'D' => 0, 'NC' => 0];
        foreach ($stmt->fetchAll() as $r) $rec[$r['result']] = (int)$r['cnt'];
        return $rec;
    }

    public function winMethods(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT method, COUNT(*) AS cnt
             FROM mma_results
             WHERE club_id = ? AND member_id = ? AND result = 'W' AND method IS NOT NULL
             GROUP BY method
             ORDER BY cnt DESC"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetchAll();
    }
}
