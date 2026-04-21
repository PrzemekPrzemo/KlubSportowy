<?php

namespace App\Models;

use App\Models\Traits\EncryptsFields;

class AntiDopingModel extends ClubScopedModel
{
    use EncryptsFields;

    protected string $table = 'anti_doping_declarations';

    protected static array $ENCRYPTED_FIELDS = ['witness', 'notes', 'document_path'];

    public function insert(array $data): int
    {
        return parent::insert($this->encryptFields($data));
    }

    public function update(int $id, array $data): bool
    {
        return parent::update($id, $this->encryptFields($data));
    }

    public function findById(int $id): ?array
    {
        return $this->decryptRow(parent::findById($id));
    }

    public static array $DECLARATION_TYPES = [
        'WADA'     => 'WADA (World Anti-Doping Agency)',
        'POLADA'   => 'POLADA (Polska Agencja Antydopingowa)',
        'IWF'      => 'IWF (Podnoszenie ciężarów)',
        'UCI'      => 'UCI (Kolarstwo)',
        'FINA'     => 'FINA (Pływanie)',
        'WTF'      => 'WTF (Taekwondo)',
        'narodowa' => 'Federacja narodowa',
    ];

    /** Sporty wymagające deklaracji anti-doping. */
    public static array $WADA_SPORTS = [
        'weightlifting', 'boxing', 'swimming', 'taekwondo', 'cycling',
        'wrestling', 'judo', 'athletics', 'gymnastics', 'climbing', 'sambo',
    ];

    public function forMember(int $memberId): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM anti_doping_declarations
             WHERE club_id = ? AND member_id = ?
             ORDER BY signed_date DESC
             LIMIT 1"
        );
        $stmt->execute([$clubId, $memberId]);
        return $this->decryptRow($stmt->fetch() ?: null);
    }

    public function listForClub(?string $dateFrom = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT ad.*, m.first_name, m.last_name, m.member_number,
                       DATEDIFF(ad.valid_until, CURDATE()) AS days_remaining
                FROM anti_doping_declarations ad
                JOIN members m ON m.id = ad.member_id
                WHERE ad.club_id = ?";
        $params = [$clubId];
        if ($dateFrom) { $sql .= " AND ad.signed_date >= ?"; $params[] = $dateFrom; }
        $sql .= " ORDER BY ad.valid_until ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->decryptRows($stmt->fetchAll());
    }

    public function expiringSoon(int $days = 30): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT ad.*, m.first_name, m.last_name, m.member_number,
                    DATEDIFF(ad.valid_until, CURDATE()) AS days_remaining
             FROM anti_doping_declarations ad
             JOIN members m ON m.id = ad.member_id
             WHERE ad.club_id = ?
               AND ad.valid_until BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY ad.valid_until ASC"
        );
        $stmt->execute([$clubId, $days]);
        return $this->decryptRows($stmt->fetchAll());
    }

    /**
     * Zwraca zawodników wymagających deklaracji WADA (na podstawie ich sekcji sportowych),
     * którzy nie mają ważnej deklaracji.
     */
    public function membersRequiringDeclaration(): array
    {
        $clubId = $this->clubId();
        $wadaPlaceholders = implode(',', array_fill(0, count(self::$WADA_SPORTS), '?'));
        $stmt = $this->db->prepare(
            "SELECT DISTINCT m.id, m.first_name, m.last_name, m.member_number,
                    GROUP_CONCAT(DISTINCT s.key) AS sports
             FROM members m
             JOIN member_sports ms ON ms.member_id = m.id AND ms.club_id = m.club_id
             JOIN club_sports cs  ON cs.id = ms.club_sport_id
             JOIN sports s        ON s.id = cs.sport_id
             LEFT JOIN anti_doping_declarations ad
                ON ad.member_id = m.id AND ad.club_id = m.club_id
                   AND ad.valid_until >= CURDATE()
             WHERE m.club_id = ?
               AND m.status = 'aktywny'
               AND s.key IN ($wadaPlaceholders)
               AND ad.id IS NULL
             GROUP BY m.id
             ORDER BY m.last_name"
        );
        $stmt->execute(array_merge([$clubId], self::$WADA_SPORTS));
        return $stmt->fetchAll();
    }
}
