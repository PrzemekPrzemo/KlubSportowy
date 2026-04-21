<?php

namespace App\Sports\Cycling\Models;

use App\Models\ClubScopedModel;

class CyclingFtpModel extends ClubScopedModel
{
    protected string $table = 'cycling_ftp_tests';

    public static array $PROTOCOLS = [
        '20min'  => '20-minutowy (x 0.95)',
        '60min'  => '60-minutowy (pełny FTP)',
        '8min'   => '8-minutowy (x 0.90)',
        'ramp'   => 'Ramp Test',
        'field'  => 'Test polowy',
    ];

    public function listForClub(?int $memberId = null): array
    {
        $sql = "SELECT ft.*, m.first_name, m.last_name, m.member_number
                FROM cycling_ftp_tests ft
                JOIN members m ON m.id = ft.member_id
                WHERE ft.club_id = ?";
        $params = [$this->clubId()];
        if ($memberId !== null) {
            $sql .= " AND ft.member_id = ?";
            $params[] = $memberId;
        }
        $sql .= " ORDER BY ft.test_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function latestForMember(int $memberId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM cycling_ftp_tests
             WHERE club_id = ? AND member_id = ?
             ORDER BY test_date DESC LIMIT 1"
        );
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetch() ?: null;
    }

    public static function wattsPerKg(int $watts, ?float $weight): ?float
    {
        if (!$weight || $weight <= 0) return null;
        return round($watts / (float)$weight, 2);
    }

    public static function fitnessCategory(float $wattsPerKg, string $gender = 'M'): string
    {
        // Andrew Coggan's FTP classification (male reference)
        if ($gender === 'K') {
            if ($wattsPerKg >= 5.0) return 'World Class';
            if ($wattsPerKg >= 4.3) return 'Exceptional';
            if ($wattsPerKg >= 3.6) return 'Bardzo dobry';
            if ($wattsPerKg >= 2.9) return 'Dobry';
            if ($wattsPerKg >= 2.3) return 'Średni';
            return 'Początkujący';
        }
        if ($wattsPerKg >= 5.6) return 'World Class';
        if ($wattsPerKg >= 4.8) return 'Exceptional';
        if ($wattsPerKg >= 4.0) return 'Bardzo dobry';
        if ($wattsPerKg >= 3.2) return 'Dobry';
        if ($wattsPerKg >= 2.5) return 'Średni';
        return 'Początkujący';
    }
}
