<?php

namespace App\Sports\Weightlifting\Models;

use App\Models\ClubScopedModel;

class WeightliftingResultModel extends ClubScopedModel
{
    protected string $table = 'weightlifting_results';

    /** IWF weight classes — men */
    public static array $WEIGHT_CLASSES_MEN = [
        '-55','-61','-67','-73','-81','-89','-96','-102','-109','+109'
    ];

    /** IWF weight classes — women */
    public static array $WEIGHT_CLASSES_WOMEN = [
        '-45','-49','-55','-59','-64','-71','-76','-81','-87','+87'
    ];

    /**
     * Returns snatch + clean&jerk total, or null if either is missing.
     */
    public static function calcTotal(?float $snatch, ?float $cj): ?float
    {
        if ($snatch === null || $cj === null) {
            return null;
        }
        return round($snatch + $cj, 1);
    }

    /**
     * Sinclair coefficient calculation (IWF 2020–2024 coefficients).
     * Men:   A = 0.722762521, b = 175.508
     * Women: A = 0.787004341, b = 153.655
     */
    public static function sinclair(float $total, float $bodyWeight, string $sex = 'M'): float
    {
        if ($sex === 'F') {
            $A = 0.787004341;
            $b = 153.655;
        } else {
            $A = 0.722762521;
            $b = 175.508;
        }
        if ($bodyWeight >= $b) {
            return $total;
        }
        $x = log10($bodyWeight / $b);
        return round($total * pow(10, $A * $x * $x), 4);
    }

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT wr.*, m.first_name, m.last_name, m.member_number
                FROM weightlifting_results wr
                JOIN members m ON m.id = wr.member_id
                WHERE wr.club_id = ?
                ORDER BY wr.competition_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
