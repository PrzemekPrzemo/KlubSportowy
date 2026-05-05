<?php

namespace App\Models;

/**
 * Stawki prowizji dla trenerów (% lub kwota stała) per sport.
 *
 * Stawka działa na konkretnym fee_type (skladka / wpisowe / licencja / all).
 * Trener może mieć kilka stawek dla różnych sportów.
 *
 * Pełna izolacja per klub przez ClubScopedModel.
 */
class TrainerCommissionRateModel extends ClubScopedModel
{
    protected string $table = 'trainer_commission_rates';

    public const TYPE_PERCENT = 'percent';
    public const TYPE_FIXED   = 'fixed_amount';

    public static array $TYPES = [
        self::TYPE_PERCENT => 'Procent (%)',
        self::TYPE_FIXED   => 'Kwota stała (PLN)',
    ];

    public static array $APPLIES_TO = [
        'skladka'  => 'Składki członkowskie',
        'wpisowe'  => 'Wpisowe',
        'licencja' => 'Licencje',
        'all'      => 'Wszystkie typy',
    ];

    /**
     * Lista stawek klubu z join do users.
     */
    public function listForClub(?int $trainerId = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT tcr.*,
                       u.username   AS trainer_username,
                       u.full_name  AS trainer_name,
                       s.name       AS sport_name, s.`key` AS sport_key
                FROM trainer_commission_rates tcr
                JOIN users u ON u.id = tcr.trainer_user_id
                LEFT JOIN sports s ON s.id = tcr.sport_id
                WHERE tcr.club_id = ?";
        $params = [$clubId];
        if ($trainerId !== null) {
            $sql .= " AND tcr.trainer_user_id = ?";
            $params[] = $trainerId;
        }
        $sql .= " ORDER BY u.full_name, s.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Znajduje aktywną stawkę dla trenera + (opcjonalnie) sportu + typu opłaty.
     * Sport-specific przeważa nad klubową (sport_id NULL).
     *
     * Zwraca null gdy brak aktywnej stawki.
     */
    public function findActiveRate(int $trainerId, ?int $sportId, string $feeType, string $onDate): ?array
    {
        $clubId = $this->clubId();
        $sql = "SELECT * FROM trainer_commission_rates
                WHERE club_id = ?
                  AND trainer_user_id = ?
                  AND is_active = 1
                  AND valid_from <= ?
                  AND (valid_to IS NULL OR valid_to >= ?)
                  AND applies_to IN (?, 'all')
                  AND (sport_id = ? OR sport_id IS NULL)
                ORDER BY (sport_id IS NULL) ASC  -- sport-specific najpierw
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId, $trainerId, $onDate, $onDate, $feeType, $sportId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Lista trenerów klubu (users z user_clubs.role='trener').
     */
    public function trainersForClub(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT u.id, u.username, u.full_name, u.email
             FROM users u
             JOIN user_clubs uc ON uc.user_id = u.id
             WHERE uc.club_id = ? AND uc.role IN ('trener', 'instruktor') AND uc.is_active = 1
             ORDER BY u.full_name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
