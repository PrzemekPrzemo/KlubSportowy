<?php

namespace App\Sports\CrossFit\Models;

use App\Models\ClubScopedModel;

class CrossFitPrModel extends ClubScopedModel
{
    protected string $table = 'crossfit_prs';

    public static array $COMMON_MOVEMENTS = [
        'Back Squat','Front Squat','Deadlift','Clean','Clean & Jerk','Snatch','Overhead Squat',
        'Press','Push Press','Push Jerk','Bench Press','Pull-ups','Muscle-ups',
        'Handstand Push-ups','Double Unders','Box Jump',
    ];

    public function listForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM crossfit_prs WHERE club_id=? AND member_id=?
             ORDER BY movement, pr_date DESC"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetchAll();
    }

    public function setRecord(array $data): void
    {
        $memberId = (int)$data['member_id'];
        $movement = trim($data['movement']);

        // Check if better than current best for same movement+unit
        $existing = $this->db->prepare(
            "SELECT * FROM crossfit_prs WHERE club_id=? AND member_id=? AND movement=? AND unit=?
             ORDER BY CAST(pr_value AS DECIMAL(10,3)) DESC LIMIT 1"
        );
        $existing->execute([$this->clubId(), $memberId, $movement, $data['unit']]);
        $current = $existing->fetch();

        // Always insert new PR; old records stay as history
        $this->insert([
            'member_id' => $memberId,
            'movement'  => $movement,
            'pr_value'  => trim($data['pr_value']),
            'unit'      => $data['unit'],
            'pr_date'   => $data['pr_date'] ?? date('Y-m-d'),
            'notes'     => $data['notes'] ?? null,
        ]);
    }

    public function clubPrBoard(string $movement): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, m.first_name, m.last_name
             FROM crossfit_prs p
             JOIN members m ON m.id = p.member_id
             WHERE p.club_id=? AND p.movement=? AND p.unit='kg'
             ORDER BY CAST(p.pr_value AS DECIMAL(10,3)) DESC
             LIMIT 20"
        );
        $stmt->execute([$this->clubId(), $movement]);
        return $stmt->fetchAll();
    }

    public function topByMember(int $memberId, int $limit = 5): array
    {
        // Return the best (most recent) PR per movement
        $stmt = $this->db->prepare(
            "SELECT p1.*
             FROM crossfit_prs p1
             WHERE p1.club_id=? AND p1.member_id=?
               AND p1.id = (
                 SELECT p2.id FROM crossfit_prs p2
                 WHERE p2.club_id=p1.club_id AND p2.member_id=p1.member_id AND p2.movement=p1.movement
                 ORDER BY p2.pr_date DESC, p2.id DESC LIMIT 1
               )
             GROUP BY p1.movement
             ORDER BY p1.pr_date DESC
             LIMIT ?"
        );
        $stmt->execute([$this->clubId(), $memberId, $limit]);
        return $stmt->fetchAll();
    }
}
