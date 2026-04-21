<?php

namespace App\Models;

class CoachCertificationModel extends ClubScopedModel
{
    protected string $table = 'coach_certifications';

    public static array $LEVELS = [
        'instruktor_sportu'           => ['label' => 'Instruktor sportu',          'class' => 'info'],
        'instruktor_rekreacji'        => ['label' => 'Instruktor rekreacji',       'class' => 'info'],
        'trener_klasy_II'             => ['label' => 'Trener klasy II',            'class' => 'primary'],
        'trener_klasy_I'              => ['label' => 'Trener klasy I',             'class' => 'success'],
        'trener_klasy_mistrzowskiej'  => ['label' => 'Trener klasy mistrzowskiej', 'class' => 'warning'],
        'sedzia_III'                  => ['label' => 'Sędzia klasy III',           'class' => 'secondary'],
        'sedzia_II'                   => ['label' => 'Sędzia klasy II',            'class' => 'secondary'],
        'sedzia_I'                    => ['label' => 'Sędzia klasy I',             'class' => 'secondary'],
        'sedzia_panstwowy'            => ['label' => 'Sędzia państwowy',           'class' => 'dark'],
        'ratownik_wodny'              => ['label' => 'Ratownik wodny',             'class' => 'danger'],
        'pierwsza_pomoc'              => ['label' => 'Pierwsza pomoc (KPP)',       'class' => 'danger'],
        'inne'                        => ['label' => 'Inne',                       'class' => 'secondary'],
    ];

    public function listForClub(?string $sportKey = null, ?string $level = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT cc.*,
                       m.first_name AS member_first, m.last_name AS member_last, m.member_number,
                       u.full_name  AS user_name,
                       DATEDIFF(cc.valid_until, CURDATE()) AS days_remaining
                FROM coach_certifications cc
                LEFT JOIN members m ON m.id = cc.member_id
                LEFT JOIN users u   ON u.id = cc.user_id
                WHERE cc.club_id = ?";
        $params = [$clubId];
        if ($sportKey !== null && $sportKey !== '') {
            $sql .= " AND cc.sport_key = ?";
            $params[] = $sportKey;
        }
        if ($level !== null && array_key_exists($level, self::$LEVELS)) {
            $sql .= " AND cc.cert_level = ?";
            $params[] = $level;
        }
        $sql .= " ORDER BY cc.valid_until ASC, cc.sport_key";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function forMember(int $memberId): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT cc.*, DATEDIFF(cc.valid_until, CURDATE()) AS days_remaining
             FROM coach_certifications cc
             WHERE cc.club_id = ? AND cc.member_id = ?
             ORDER BY cc.valid_until ASC, cc.sport_key"
        );
        $stmt->execute([$clubId, $memberId]);
        return $stmt->fetchAll();
    }

    public function expiringSoon(int $days = 60): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT cc.*,
                    m.first_name AS member_first, m.last_name AS member_last,
                    u.full_name  AS user_name,
                    DATEDIFF(cc.valid_until, CURDATE()) AS days_remaining
             FROM coach_certifications cc
             LEFT JOIN members m ON m.id = cc.member_id
             LEFT JOIN users u   ON u.id = cc.user_id
             WHERE cc.club_id = ?
               AND cc.valid_until IS NOT NULL
               AND cc.valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY cc.valid_until ASC"
        );
        $stmt->execute([$clubId, $days]);
        return $stmt->fetchAll();
    }

    public function summary(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT sport_key, cert_level, COUNT(*) AS cnt
             FROM coach_certifications
             WHERE club_id = ?
             GROUP BY sport_key, cert_level
             ORDER BY sport_key, cert_level"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
