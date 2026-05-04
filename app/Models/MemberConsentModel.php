<?php

namespace App\Models;

class MemberConsentModel extends ClubScopedModel
{
    protected string $table = 'member_consents';

    public static function TYPES(): array
    {
        return [
            'rodo'         => 'Przetwarzanie danych osobowych (RODO)',
            'marketing'    => 'Komunikacja marketingowa',
            'wizerunek'    => 'Publikacja wizerunku (zdjęcia/filmy)',
            'newsletter'   => 'Newsletter klubowy',
            'profilowanie' => 'Profilowanie i analiza statystyk',
        ];
    }

    public function forMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM member_consents WHERE member_id = ? ORDER BY consent_type"
        );
        $stmt->execute([$memberId]);
        $rows = $stmt->fetchAll();
        $map = [];
        foreach ($rows as $r) { $map[$r['consent_type']] = $r; }
        return $map;
    }

    public function grant(int $clubId, int $memberId, string $type): void
    {
        $sql = "INSERT INTO member_consents (club_id, member_id, consent_type, granted, granted_at, ip_address)
                VALUES (?, ?, ?, 1, NOW(), ?)
                ON DUPLICATE KEY UPDATE granted = 1, granted_at = NOW(), revoked_at = NULL, ip_address = VALUES(ip_address)";
        $this->db->prepare($sql)->execute([$clubId, $memberId, $type, $_SERVER['REMOTE_ADDR'] ?? null]);
    }

    public function revoke(int $clubId, int $memberId, string $type): void
    {
        $sql = "UPDATE member_consents SET granted = 0, revoked_at = NOW() WHERE club_id = ? AND member_id = ? AND consent_type = ?";
        $this->db->prepare($sql)->execute([$clubId, $memberId, $type]);
    }

    public function allForMember(int $memberId, int $clubId): array
    {
        $existing = [];
        $stmt = $this->db->prepare(
            "SELECT * FROM member_consents WHERE member_id = ? AND club_id = ? ORDER BY consent_type"
        );
        $stmt->execute([$memberId, $clubId]);
        foreach ($stmt->fetchAll() as $r) {
            $existing[$r['consent_type']] = $r;
        }
        $result = [];
        foreach (self::TYPES() as $key => $label) {
            $result[$key] = $existing[$key] ?? [
                'consent_type' => $key,
                'granted'      => 0,
                'granted_at'   => null,
                'revoked_at'   => null,
                'ip_address'   => null,
            ];
            $result[$key]['label'] = $label;
        }
        return $result;
    }

    public function setConsent(int $memberId, int $clubId, string $type, bool $granted, string $ip): void
    {
        if ($granted) {
            $sql = "INSERT INTO member_consents (club_id, member_id, consent_type, granted, granted_at, revoked_at, ip_address)
                    VALUES (?, ?, ?, 1, NOW(), NULL, ?)
                    ON DUPLICATE KEY UPDATE granted = 1, granted_at = NOW(), revoked_at = NULL, ip_address = VALUES(ip_address)";
            $this->db->prepare($sql)->execute([$clubId, $memberId, $type, $ip]);
        } else {
            $sql = "INSERT INTO member_consents (club_id, member_id, consent_type, granted, granted_at, revoked_at, ip_address)
                    VALUES (?, ?, ?, 0, NULL, NOW(), ?)
                    ON DUPLICATE KEY UPDATE granted = 0, revoked_at = NOW(), ip_address = VALUES(ip_address)";
            $this->db->prepare($sql)->execute([$clubId, $memberId, $type, $ip]);
        }
    }

    public function listForClub(?string $type = null, int $page = 1, int $perPage = 30): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT mc.*, m.first_name, m.last_name, m.member_number
                FROM member_consents mc
                JOIN members m ON m.id = mc.member_id
                WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND mc.club_id = ?"; $params[] = $clubId; }
        if ($type) { $sql .= " AND mc.consent_type = ?"; $params[] = $type; }
        $sql .= " ORDER BY m.last_name, mc.consent_type";
        return $this->paginate($sql, $params, $page, $perPage);
    }
}
