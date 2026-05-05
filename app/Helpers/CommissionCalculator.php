<?php

namespace App\Helpers;

use App\Models\TrainerCommissionLogModel;
use App\Models\TrainerCommissionRateModel;
use PDO;

/**
 * Naliczanie prowizji dla trenerów po zaksięgowaniu wpłaty.
 *
 * Wywoływane z PaymentController::store() / FeesController::pay() /
 * GatewayWebhookController::processEvent() — zaraz po INSERT do `payments`.
 *
 * Logika:
 *   1. Znajdź trenerów zawodnika — coach_id z trainings w których uczestniczy
 *      (training_attendees.training_id) lub coach_id z football_teams gdy
 *      member jest w lineup. Domyślnie: wszyscy aktywni trenerzy klubu z
 *      sport_id pasującym do payments.sport_id.
 *   2. Per trener — znajdź `findActiveRate(trainer, sport, fee_type, payment_date)`.
 *   3. Wylicz commission_amount (% × amount LUB fixed_amount).
 *   4. INSERT do trainer_commissions_log (idempotent: UNIQUE payment_id+trainer).
 */
class CommissionCalculator
{
    /**
     * Naliczy prowizje dla wpłaty. Zwraca liczbę utworzonych wpisów logu.
     *
     * @param array $payment   wiersz z `payments` (musi zawierać id, club_id,
     *                         member_id, sport_id, amount, payment_date,
     *                         period_year, period_month, fee_rate_id)
     */
    public static function accrueForPayment(array $payment): int
    {
        $paymentId = (int)$payment['id'];
        $clubId    = (int)$payment['club_id'];
        $memberId  = (int)$payment['member_id'];
        $sportId   = !empty($payment['sport_id']) ? (int)$payment['sport_id'] : null;
        $amount    = (float)$payment['amount'];
        $paidOn    = $payment['payment_date'] ?? date('Y-m-d');
        $year      = (int)($payment['period_year'] ?? date('Y'));
        $month     = !empty($payment['period_month']) ? (int)$payment['period_month'] : null;

        $feeType = self::resolveFeeType($payment);
        if ($feeType === null) {
            return 0;
        }

        $trainers = self::trainersForMember($clubId, $memberId, $sportId);
        if (empty($trainers)) {
            return 0;
        }

        $rateModel = new TrainerCommissionRateModel();
        $logModel  = new TrainerCommissionLogModel();
        $created   = 0;

        foreach ($trainers as $trainerId) {
            if ($logModel->existsForPaymentAndTrainer($paymentId, $trainerId)) {
                continue;
            }

            $rate = $rateModel->findActiveRate($trainerId, $sportId, $feeType, $paidOn);
            if (!$rate) continue;

            $commission = self::calculate(
                $amount,
                (string)$rate['commission_type'],
                (float)$rate['value']
            );
            if ($commission <= 0) continue;

            $logModel->insert([
                'club_id'           => $clubId,
                'trainer_user_id'   => $trainerId,
                'payment_id'        => $paymentId,
                'member_id'         => $memberId,
                'rate_id'           => (int)$rate['id'],
                'commission_type'   => (string)$rate['commission_type'],
                'rate_value'        => (float)$rate['value'],
                'payment_amount'    => $amount,
                'commission_amount' => $commission,
                'period_year'      => $year,
                'period_month'     => $month,
                'status'            => TrainerCommissionLogModel::STATUS_ACCRUED,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * Czysta funkcja matematyczna — pure, łatwa do testowania.
     *
     * @param string $type 'percent' | 'fixed_amount'
     */
    public static function calculate(float $amount, string $type, float $value): float
    {
        if ($amount <= 0 || $value <= 0) {
            return 0.0;
        }
        if ($type === TrainerCommissionRateModel::TYPE_PERCENT) {
            $value = min($value, 100.0);
            return round($amount * ($value / 100.0), 2);
        }
        if ($type === TrainerCommissionRateModel::TYPE_FIXED) {
            return round(min($value, $amount), 2);
        }
        return 0.0;
    }

    /**
     * Pobierz fee_type z payments.fee_rate_id (lub domyślnie 'skladka').
     */
    private static function resolveFeeType(array $payment): ?string
    {
        if (empty($payment['fee_rate_id'])) {
            return 'skladka';
        }
        $db = Database::pdo();
        $stmt = $db->prepare("SELECT fee_type FROM fee_rates WHERE id = ?");
        $stmt->execute([(int)$payment['fee_rate_id']]);
        $type = $stmt->fetchColumn();
        return $type ? (string)$type : 'skladka';
    }

    /**
     * Lista trener_user_id-ów którzy prowadzą zawodnika.
     *
     * Heurystyka:
     *   1. Jeśli zawodnik uczestniczył w treningach (training_attendees) z
     *      ostatnich 90 dni — zwróć distinct coach_id-y.
     *   2. Jeśli sport drużynowy — coach_id z football_teams gdzie zawodnik
     *      jest w lineup (sport-specific, opcjonalnie).
     *   3. Fallback: wszyscy aktywni trenerzy klubu (user_clubs.role='trener').
     *
     * Filtrujemy do trenerów którzy mają stawkę w trainer_commission_rates —
     * inaczej `findActiveRate` i tak zwróci null. Tu zwracamy szeroko.
     */
    private static function trainersForMember(int $clubId, int $memberId, ?int $sportId): array
    {
        $db = Database::pdo();

        // 1. Treningi z ostatnich 90 dni — coache faktycznie prowadzący
        $sql = "SELECT DISTINCT t.coach_id
                FROM training_attendees ta
                JOIN trainings t ON t.id = ta.training_id
                WHERE t.club_id = ?
                  AND ta.member_id = ?
                  AND t.coach_id IS NOT NULL
                  AND t.start_time >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
        $params = [$clubId, $memberId];
        if ($sportId !== null) {
            $sql .= " AND EXISTS (
                        SELECT 1 FROM club_sports cs
                         WHERE cs.id = t.club_sport_id AND cs.sport_id = ?
                      )";
            $params[] = $sportId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $ids = array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));

        if (!empty($ids)) {
            return array_values(array_unique($ids));
        }

        // 2. Fallback: wszyscy aktywni trenerzy klubu z trainer_commission_rates
        //    (zawężamy do tych którzy w ogóle mają stawkę w klubie — tylko ci
        //    mogą cokolwiek zarobić)
        $sql = "SELECT DISTINCT uc.user_id
                FROM user_clubs uc
                JOIN trainer_commission_rates tcr
                     ON tcr.trainer_user_id = uc.user_id
                    AND tcr.club_id = uc.club_id
                    AND tcr.is_active = 1
                WHERE uc.club_id = ?
                  AND uc.role IN ('trener','instruktor')
                  AND uc.is_active = 1";
        $params = [$clubId];
        if ($sportId !== null) {
            $sql .= " AND (tcr.sport_id = ? OR tcr.sport_id IS NULL)";
            $params[] = $sportId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));
    }
}
