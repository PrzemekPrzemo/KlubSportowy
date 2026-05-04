<?php

namespace App\Sports\Powerlifting\Models;

use App\Models\ClubScopedModel;

class PowerliftingResultModel extends ClubScopedModel
{
    protected string $table = 'powerlifting_results';

    public static array $WEIGHT_CLASSES_MEN   = ['-59','-66','-74','-83','-93','-105','-120','+120'];
    public static array $WEIGHT_CLASSES_WOMEN = ['-47','-52','-57','-63','-69','-76','-84','+84'];

    public static array $FEDERATIONS = [
        'IPF'      => 'IPF (World Powerlifting)',
        'WRPF'     => 'WRPF',
        'WPC'      => 'WPC',
        'PZTSS'    => 'PZTSS (Polska)',
        'raw'      => 'Raw',
        'equipped' => 'Equipped',
    ];

    /**
     * Returns sum of squat+bench+deadlift if all three values are provided, otherwise null.
     */
    public static function calcTotal(?float $squat, ?float $bench, ?float $deadlift): ?float
    {
        if ($squat === null || $bench === null || $deadlift === null) {
            return null;
        }
        return round($squat + $bench + $deadlift, 1);
    }

    /**
     * Simplified Wilks formula.
     * Coefficients for men: a=-216.0475144, b=16.2606339, c=-0.002388645, d=-0.00113732, e=7.01863e-06, f=-1.291e-08
     * Coefficients for women: a=594.31747775582, b=-27.23842536447, c=0.82112226871, d=-0.00930733913, e=4.731582e-05, f=-9.054e-08
     */
    public static function wilks(float $total, float $bodyWeight, string $sex = 'M'): float
    {
        if ($sex === 'F') {
            $a =  594.31747775582;
            $b = -27.23842536447;
            $c =   0.82112226871;
            $d =  -0.00930733913;
            $e =   4.731582e-05;
            $f =  -9.054e-08;
        } else {
            $a = -216.0475144;
            $b =   16.2606339;
            $c =   -0.002388645;
            $d =   -0.00113732;
            $e =    7.01863e-06;
            $f =   -1.291e-08;
        }
        $bw  = $bodyWeight;
        $coeff = 500.0 / ($a + $b * $bw + $c * $bw**2 + $d * $bw**3 + $e * $bw**4 + $f * $bw**5);
        return round($total * $coeff, 4);
    }

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT pr.*, m.first_name, m.last_name, m.member_number
                FROM powerlifting_results pr
                JOIN members m ON m.id = pr.member_id
                WHERE pr.club_id = ?
                ORDER BY pr.competition_date DESC, m.last_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
