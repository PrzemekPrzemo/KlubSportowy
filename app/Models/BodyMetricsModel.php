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

    /**
     * Waliduje zestaw pomiarow zawodnika. Zwraca tablice bledow ['pole' => 'komunikat']
     * lub puste tablice gdy wszystko OK. Sprawdza realistyczne zakresy fizjologiczne.
     */
    public static function validate(array $data): array
    {
        $errors = [];

        // weight 20-250 kg
        if (isset($data['weight_kg']) && $data['weight_kg'] !== null && $data['weight_kg'] !== '') {
            $w = (float)$data['weight_kg'];
            if ($w < 20.0 || $w > 250.0) {
                $errors['weight_kg'] = 'Waga powinna miescic sie w przedziale 20–250 kg.';
            }
        }

        // height 100-250 cm
        if (isset($data['height_cm']) && $data['height_cm'] !== null && $data['height_cm'] !== '') {
            $h = (int)$data['height_cm'];
            if ($h < 100 || $h > 250) {
                $errors['height_cm'] = 'Wzrost powinien miescic sie w przedziale 100–250 cm.';
            }
        }

        // body fat 0-70 %
        if (isset($data['body_fat_pct']) && $data['body_fat_pct'] !== null && $data['body_fat_pct'] !== '') {
            $bf = (float)$data['body_fat_pct'];
            if ($bf < 0.0 || $bf > 70.0) {
                $errors['body_fat_pct'] = 'Procent tkanki tluszczowej w zakresie 0–70%.';
            }
        }

        // resting HR 30-200 bpm
        if (isset($data['resting_hr']) && $data['resting_hr'] !== null && $data['resting_hr'] !== '') {
            $hr = (int)$data['resting_hr'];
            if ($hr < 30 || $hr > 200) {
                $errors['resting_hr'] = 'Tetno spoczynkowe w zakresie 30–200 bpm.';
            }
        }

        // wingspan 100-260 cm
        if (isset($data['wingspan_cm']) && $data['wingspan_cm'] !== null && $data['wingspan_cm'] !== '') {
            $ws = (int)$data['wingspan_cm'];
            if ($ws < 100 || $ws > 260) {
                $errors['wingspan_cm'] = 'Rozpietosc ramion w zakresie 100–260 cm.';
            }
        }

        // measured_at: not in the future, not before 1900
        if (isset($data['measured_at']) && $data['measured_at']) {
            $ts = strtotime((string)$data['measured_at']);
            if ($ts === false) {
                $errors['measured_at'] = 'Nieprawidlowy format daty.';
            } elseif ($ts > strtotime('tomorrow')) {
                $errors['measured_at'] = 'Data pomiaru nie moze byc w przyszlosci.';
            } elseif ($ts < strtotime('1900-01-01')) {
                $errors['measured_at'] = 'Data pomiaru jest nierealna.';
            }
        }

        // require at least one measurement
        $hasAny = false;
        foreach (['weight_kg','height_cm','body_fat_pct','resting_hr','wingspan_cm'] as $k) {
            if (isset($data[$k]) && $data[$k] !== null && $data[$k] !== '') {
                $hasAny = true; break;
            }
        }
        if (!$hasAny) {
            $errors['_at_least_one'] = 'Podaj co najmniej jeden pomiar.';
        }

        return $errors;
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
