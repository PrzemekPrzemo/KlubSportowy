<?php

declare(strict_types=1);

namespace App\Sports\Archery\Models;

use App\Models\ClubScopedModel;

/**
 * sport_archery_member — profil łucznika (typ łuku, dominant eye).
 */
class ArcheryMemberModel extends ClubScopedModel
{
    protected string $table = 'sport_archery_member';

    public static array $BOW_TYPES = [
        'recurve'  => 'Recurve (olimpijski)',
        'compound' => 'Compound (bloczkowy)',
        'barebow'  => 'Barebow (gołe)',
        'longbow'  => 'Longbow (długi)',
    ];

    public static array $EYES = [
        'right' => 'Prawe',
        'left'  => 'Lewe',
    ];

    public function findByMember(int $memberId): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
              WHERE member_id = ? AND club_id = ?"
        );
        $stmt->execute([$memberId, $clubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsert(int $memberId, array $data): bool
    {
        $clubId = $this->clubId();
        if ($clubId === null) return false;

        $bow = isset(self::$BOW_TYPES[$data['bow_type'] ?? ''])
                ? $data['bow_type'] : 'recurve';
        $eye = isset(self::$EYES[$data['dominant_eye'] ?? ''])
                ? $data['dominant_eye'] : 'right';
        $rank = isset($data['national_rank']) && $data['national_rank'] !== ''
                ? (int)$data['national_rank'] : null;

        if ($this->findByMember($memberId)) {
            $stmt = $this->db->prepare(
                "UPDATE `{$this->table}`
                    SET bow_type=?, dominant_eye=?, national_rank=?
                  WHERE member_id=? AND club_id=?"
            );
            return $stmt->execute([$bow, $eye, $rank, $memberId, $clubId]);
        }
        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}` (member_id, club_id, bow_type, dominant_eye, national_rank)
             VALUES (?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$memberId, $clubId, $bow, $eye, $rank]);
    }
}
