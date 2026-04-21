<?php

namespace App\Sports\Boxing\Models;

use App\Models\ClubScopedModel;

class BoxingResultModel extends ClubScopedModel
{
    protected string $table = 'boxing_results';

    public static array $CATEGORIES = [
        'amateur' => 'Amatorski',
        'youth'   => 'Młodzieżowy',
        'elite'   => 'Elite',
    ];

    public static array $WEIGHT_CLASSES = [
        '-46' => 'Słomkowa (do 46 kg)',
        '-49' => 'Papierowa (do 49 kg)',
        '-52' => 'Kogucia (do 52 kg)',
        '-56' => 'Lekka (do 56 kg)',
        '-60' => 'Lekko-półśrednia (do 60 kg)',
        '-63' => 'Półśrednia (do 63 kg)',
        '-67' => 'Średnia (do 67 kg)',
        '-71' => 'Półciężka lekka (do 71 kg)',
        '-75' => 'Półciężka (do 75 kg)',
        '-80' => 'Ciężka lekka (do 80 kg)',
        '-86' => 'Ciężka (do 86 kg)',
        '-92' => 'Ciężka plus (do 92 kg)',
        '+92' => 'Super ciężka (ponad 92 kg)',
    ];

    public static array $RESULTS = [
        'win'  => ['label' => 'Wygrana',         'class' => 'success'],
        'loss' => ['label' => 'Porażka',         'class' => 'danger'],
        'draw' => ['label' => 'Remis',           'class' => 'secondary'],
        'nc'   => ['label' => 'No Contest',      'class' => 'warning'],
        'dq'   => ['label' => 'Dyskwalifikacja', 'class' => 'dark'],
    ];

    public static array $METHODS = [
        'ko'       => 'KO',
        'tko'      => 'TKO',
        'points'   => 'Punkty',
        'decision' => 'Decyzja',
        'disq'     => 'Dyskwalifikacja',
        'nc'       => 'No Contest',
    ];

    public function listForClub(?int $memberId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT br.*, m.first_name, m.last_name, m.member_number
                FROM boxing_results br
                JOIN members m ON m.id = br.member_id
                WHERE br.club_id = ?";
        $params = [$clubId];
        if ($memberId !== null) {
            $sql .= " AND br.member_id = ?";
            $params[] = $memberId;
        }
        $sql .= " ORDER BY br.competition_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function recordForMember(int $memberId): array
    {
        $clubId = $this->clubId();
        $stmt   = $this->db->prepare(
            "SELECT result, COUNT(*) AS cnt
             FROM boxing_results
             WHERE club_id = ? AND member_id = ? AND result IS NOT NULL
             GROUP BY result"
        );
        $stmt->execute([$clubId, $memberId]);
        $rec = ['win' => 0, 'loss' => 0, 'draw' => 0, 'nc' => 0, 'dq' => 0];
        foreach ($stmt->fetchAll() as $r) {
            $rec[$r['result']] = (int)$r['cnt'];
        }
        return $rec;
    }

    public function clubRecord(): array
    {
        $clubId = $this->clubId();
        $stmt   = $this->db->prepare(
            "SELECT m.id, m.first_name, m.last_name, m.member_number,
                    SUM(CASE WHEN br.result = 'win'  THEN 1 ELSE 0 END) AS wins,
                    SUM(CASE WHEN br.result = 'loss' THEN 1 ELSE 0 END) AS losses,
                    SUM(CASE WHEN br.result = 'draw' THEN 1 ELSE 0 END) AS draws,
                    COUNT(br.id) AS total
             FROM members m
             JOIN boxing_results br ON br.member_id = m.id
             WHERE br.club_id = ? AND br.result IS NOT NULL
             GROUP BY m.id
             ORDER BY wins DESC, total DESC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
