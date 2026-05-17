<?php

declare(strict_types=1);

namespace App\Sports\Padel\Models;

use App\Models\ClubScopedModel;

/**
 * sport_padel_pairs — pary (debel) z ranking points (new schema, 103_*).
 *
 * UNIQUE(member_a_id, member_b_id) — eliminuje duplikaty (normalizacja
 * "mniejsze id pierwsze" przez canonicalize()).
 *
 * Istnieje równolegle ze starym PadelPairModel/padel_pairs — nowy model
 * jest dla awansu PARTIAL -> FULL i wspolpracuje z modulem rezerwacji
 * oraz portal/admin scorecard pipeline.
 */
class PadelSportPairModel extends ClubScopedModel
{
    protected string $table = 'sport_padel_pairs';

    /**
     * Normalizuj kolejnosc skladnikow pary — UNIQUE(a,b) wymaga determinizmu.
     *
     * @return array{0:int,1:int}
     */
    public static function canonicalize(int $a, int $b): array
    {
        if ($a === $b) return [$a, $b];
        return $a < $b ? [$a, $b] : [$b, $a];
    }

    public function listForClub(bool $activeOnly = true): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT spp.*,
                       ma.first_name AS a_first, ma.last_name AS a_last, ma.member_number AS a_num,
                       mb.first_name AS b_first, mb.last_name AS b_last, mb.member_number AS b_num
                  FROM `{$this->table}` spp
                  JOIN members ma ON ma.id = spp.member_a_id
                  JOIN members mb ON mb.id = spp.member_b_id
                 WHERE spp.club_id = ?";
        $params = [$clubId];
        if ($activeOnly) { $sql .= " AND spp.active = 1"; }
        $sql .= " ORDER BY spp.ranking_points DESC, spp.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findByMembers(int $a, int $b): ?array
    {
        [$ca, $cb] = self::canonicalize($a, $b);
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
              WHERE club_id = ? AND member_a_id = ? AND member_b_id = ?"
        );
        $stmt->execute([$clubId, $ca, $cb]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function listForMember(int $memberId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT spp.*,
                    ma.first_name AS a_first, ma.last_name AS a_last,
                    mb.first_name AS b_first, mb.last_name AS b_last
               FROM `{$this->table}` spp
               JOIN members ma ON ma.id = spp.member_a_id
               JOIN members mb ON mb.id = spp.member_b_id
              WHERE spp.club_id = ?
                AND (spp.member_a_id = ? OR spp.member_b_id = ?)
           ORDER BY spp.ranking_points DESC"
        );
        $stmt->execute([$clubId, $memberId, $memberId]);
        return $stmt->fetchAll();
    }

    /** Tworzy nowa pare (z canonicalize); zwraca id lub 0 jesli pair juz istnieje. */
    public function createPair(int $a, int $b, ?string $name = null, int $rankingPoints = 0): int
    {
        [$ca, $cb] = self::canonicalize($a, $b);
        if ($ca === $cb) return 0;
        $clubId = $this->clubId();
        if ($clubId === null) return 0;
        if ($this->findByMembers($ca, $cb)) return 0;

        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}`
                (club_id, member_a_id, member_b_id, pair_name, ranking_points, active)
             VALUES (?, ?, ?, ?, ?, 1)"
        );
        $stmt->execute([$clubId, $ca, $cb, $name, $rankingPoints]);
        return (int)$this->db->lastInsertId();
    }
}
