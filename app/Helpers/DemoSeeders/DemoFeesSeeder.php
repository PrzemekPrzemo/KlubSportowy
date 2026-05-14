<?php

declare(strict_types=1);

namespace App\Helpers\DemoSeeders;

use App\Helpers\Database;
use PDO;

/**
 * Seeds fee rates and payment history (last 6 months).
 *
 * Distribution:
 *  - 85% paid on time
 *  - 10% in arrears (no payment for some past month)
 *  - 5% paid in advance (current month already paid)
 *
 * Mix of payment methods: przelew (Stripe-like), gotowka, blik.
 */
final class DemoFeesSeeder
{
    public static function seed(array &$context): array
    {
        $db = Database::pdo();
        $clubId = (int)$context['club_id'];
        $createdBy = $context['admin_user_id'] ?? null;

        // ── 1. Fee rate definitions ───────────────────────────────────────
        $blueprints = [
            ['Skladka miesieczna A',      80.00,  'monthly',  'skladka'],
            ['Skladka miesieczna B',     120.00,  'monthly',  'skladka'],
            ['Skladka miesieczna junior', 60.00,  'monthly',  'skladka'],
            ['Skladka roczna',           800.00,  'yearly',   'skladka'],
            ['Wpisowe',                  100.00,  'one_time', 'wpisowe'],
            ['Oplata licencja',           50.00,  'yearly',   'licencja'],
        ];

        $rateInsert = $db->prepare(
            "INSERT INTO fee_rates (club_id, sport_id, name, amount, period, fee_type, is_active, description, created_at)
             VALUES (?, NULL, ?, ?, ?, ?, 1, ?, NOW())"
        );

        $rateIds = [];
        foreach ($blueprints as [$name, $amount, $period, $type]) {
            $rateInsert->execute([
                $clubId, $name, $amount, $period, $type,
                '[DEMO] Auto-utworzona stawka demo.',
            ]);
            $rateIds[$name] = (int)$db->lastInsertId();
        }

        // ── 2. Payment history ────────────────────────────────────────────
        $paymentInsert = $db->prepare(
            "INSERT INTO payments
                (club_id, member_id, fee_rate_id, sport_id, amount, payment_date, period_year, period_month, method, reference, notes, created_by, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())"
        );

        $methods = ['przelew', 'przelew', 'przelew', 'blik', 'karta', 'gotowka']; // bias toward przelew
        $today = new \DateTimeImmutable('today');
        $paymentCount = 0;
        $arrearsCount = 0;
        $advanceCount = 0;

        $memberIds = $context['member_ids'] ?? [];
        if (empty($memberIds)) {
            return ['fee_rates' => count($rateIds), 'payments' => 0];
        }

        foreach ($memberIds as $idx => $mid) {
            // Determine member profile
            $roll = ($idx * 13 + 7) % 100;
            $isArrears = $roll < 10;          // 10%
            $isAdvance = $roll >= 95;          // 5%
            // The rest (85%) are paid-on-time.

            $rateName = match (true) {
                ($idx % 5) === 0 => 'Skladka miesieczna junior',
                ($idx % 3) === 0 => 'Skladka miesieczna A',
                default          => 'Skladka miesieczna B',
            };
            $rateId = $rateIds[$rateName];
            $amount = $rateName === 'Skladka miesieczna junior' ? 60.00
                : ($rateName === 'Skladka miesieczna A' ? 80.00 : 120.00);

            // Seed 6 months back
            for ($mOff = 6; $mOff >= 1; $mOff--) {
                $period = $today->modify("-{$mOff} months");
                $year   = (int)$period->format('Y');
                $month  = (int)$period->format('m');

                // Arrears skip a random month in the middle
                if ($isArrears && $mOff === 3) {
                    $arrearsCount++;
                    continue;
                }

                $payDate = $period->modify('+' . (5 + ($idx % 20)) . ' days');
                $method  = $methods[($idx + $mOff) % count($methods)];
                $ref     = strtoupper($method) . '-' . $year . $month . '-' . $mid;

                $paymentInsert->execute([
                    $clubId, $mid, $rateId, null, $amount,
                    $payDate->format('Y-m-d'),
                    $year, $month, $method, $ref,
                    '[DEMO] Skladka okresowa.',
                    $createdBy,
                ]);
                $paymentCount++;
            }

            // Advance — pay current + next month
            if ($isAdvance) {
                for ($mOff = 0; $mOff >= -1; $mOff--) {
                    $period = $today->modify("{$mOff} months");
                    $year   = (int)$period->format('Y');
                    $month  = (int)$period->format('m');
                    $paymentInsert->execute([
                        $clubId, $mid, $rateId, null, $amount,
                        $today->format('Y-m-d'),
                        $year, $month, 'przelew', 'PRZEDPLATA-' . $mid . '-' . $month,
                        '[DEMO] Przedplata.',
                        $createdBy,
                    ]);
                    $paymentCount++;
                    $advanceCount++;
                }
            }
        }

        return [
            'fee_rates' => count($rateIds),
            'payments'  => $paymentCount,
            'arrears_simulated' => $arrearsCount,
            'advance_simulated' => $advanceCount,
        ];
    }
}
