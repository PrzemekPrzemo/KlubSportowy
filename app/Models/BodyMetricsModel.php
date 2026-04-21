<?php

namespace App\Models;

use App\Models\Traits\EncryptsFields;

class BodyMetricsModel extends ClubScopedModel
{
    use EncryptsFields;

    protected string $table = 'body_metrics';

    protected static array $ENCRYPTED_FIELDS = ['notes', 'measured_by'];

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

    public function listForMember(int $memberId, int $limit = 50): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM body_metrics
             WHERE club_id = ? AND member_id = ?
             ORDER BY measured_at DESC, id DESC
             LIMIT " . max(1, (int)$limit)
        );
        $stmt->execute([$clubId, $memberId]);
        return $this->decryptRows($stmt->fetchAll());
    }

    public function latestForMember(int $memberId): ?array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM body_metrics
             WHERE club_id = ? AND member_id = ?
             ORDER BY measured_at DESC, id DESC
             LIMIT 1"
        );
        $stmt->execute([$clubId, $memberId]);
        return $this->decryptRow($stmt->fetch() ?: null);
    }

    /**
     * Waga potrzebna dla Sinclair / W/kg — może być null gdy brak pomiarów.
     */
    public function currentWeight(int $memberId): ?float
    {
        $row = $this->latestForMember($memberId);
        if (!$row || $row['weight_kg'] === null) return null;
        return (float)$row['weight_kg'];
    }

    public static function calcBmi(?float $weightKg, ?int $heightCm): ?float
    {
        if (!$weightKg || !$heightCm || $heightCm <= 0) return null;
        $m = $heightCm / 100;
        return round($weightKg / ($m * $m), 1);
    }

    public static function bmiCategory(float $bmi): string
    {
        if ($bmi < 18.5) return 'Niedowaga';
        if ($bmi < 25.0) return 'Prawidłowa';
        if ($bmi < 30.0) return 'Nadwaga';
        if ($bmi < 35.0) return 'Otyłość I';
        if ($bmi < 40.0) return 'Otyłość II';
        return 'Otyłość III';
    }

    /**
     * Historia wagi — do wykresu (weight_kg per data, ostatnie N miesięcy).
     */
    public function weightHistory(int $memberId, int $months = 12): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT measured_at, weight_kg
             FROM body_metrics
             WHERE club_id = ? AND member_id = ?
               AND weight_kg IS NOT NULL
               AND measured_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             ORDER BY measured_at ASC"
        );
        $stmt->execute([$clubId, $memberId, max(1, $months)]);
        return $stmt->fetchAll();
    }

    public function listForClub(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT bm.*, m.first_name, m.last_name, m.member_number
                FROM body_metrics bm
                JOIN members m ON m.id = bm.member_id
                WHERE bm.club_id = ?";
        $params = [$clubId];

        if ($dateFrom) { $sql .= " AND bm.measured_at >= ?"; $params[] = $dateFrom; }
        if ($dateTo)   { $sql .= " AND bm.measured_at <= ?"; $params[] = $dateTo; }

        $sql .= " ORDER BY bm.measured_at DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $this->decryptRows($stmt->fetchAll());
    }
}
