<?php

namespace App\Helpers;

use App\Models\ReferralModel;
use App\Models\ReferralRewardsConfigModel;

/**
 * Aplikuje reward (rabat/miesiac/credit) na koncie referrera.
 *
 * Discount: tworzy drafted billing_invoices.notes-flag (lub credit equivalent)
 * — rzeczywiste mnożenie procentu na fakturze zostanie wykonane podczas
 *   generowania fakturow rekurencyjnych. Tu deklarujemy credit_balance.
 * Months_free: extend club_subscriptions.valid_until o N miesiecy.
 * Credit: upsert balance do club_credits.
 *
 * Idempotentne: jesli reward_applied=1 to nic nie robi.
 */
final class ReferralRewardService
{
    /**
     * Sprawdza czy polecony klub kwalifikuje sie (subskrypcja active >= min_paid_months).
     * Aplikuje reward i loguje status.
     *
     * @param array<string,mixed> $referral wiersz z club_referrals
     * @return bool true gdy reward zostal wlasnie aplikowany
     */
    public static function processQualification(array $referral): bool
    {
        if ((int)($referral['reward_applied'] ?? 0) === 1) {
            return false;
        }
        if (($referral['status'] ?? 'pending') !== 'pending') {
            return false;
        }

        $referredClubId = (int)$referral['referred_club_id'];
        $referrerClubId = (int)$referral['referrer_club_id'];

        $rewardCfg = (new ReferralRewardsConfigModel())->getActiveReward();
        if ($rewardCfg === null) {
            return false;
        }

        $db = Database::pdo();

        // 1) Polecony klub musi miec subskrypcje 'active' (po trial).
        $stmt = $db->prepare(
            "SELECT status, created_at, valid_until
               FROM club_subscriptions WHERE club_id = ? LIMIT 1"
        );
        $stmt->execute([$referredClubId]);
        $sub = $stmt->fetch();
        if (!$sub || ($sub['status'] ?? '') !== 'active') {
            return false;
        }

        // 2) Min liczba miesiecy oplaconych — uproszczone: na bazie roznicy
        //    miedzy created_at a now (ile cykli minelo). Mozna podpiac
        //    realne payments. MVP: jesli status=active to liczy sie 1 miesiac.
        $minMonths = (int)($rewardCfg['min_paid_months'] ?? 1);
        $monthsActive = self::approxActiveMonths($sub);
        if ($monthsActive < $minMonths) {
            return false;
        }

        // 3) Aplikuj reward.
        self::applyForReferrer($referrerClubId, $rewardCfg);

        // 4) Update club_referrals.
        (new ReferralModel())->markQualified(
            (int)$referral['id'],
            (float)$rewardCfg['reward_value'],
            (string)$rewardCfg['reward_type']
        );

        // 5) Best-effort email do referrera.
        self::notifyReferrer($referrerClubId, $referredClubId, $rewardCfg);

        return true;
    }

    /**
     * @param array<string,mixed> $rewardCfg
     */
    public static function applyForReferrer(int $referrerClubId, array $rewardCfg): void
    {
        $type  = (string)($rewardCfg['reward_type'] ?? 'discount');
        $value = (float)($rewardCfg['reward_value'] ?? 0);

        $db = Database::pdo();

        switch ($type) {
            case 'months_free':
                // Extend valid_until o N miesiecy.
                $stmt = $db->prepare(
                    "UPDATE club_subscriptions
                        SET valid_until = DATE_ADD(GREATEST(valid_until, CURDATE()), INTERVAL ? MONTH)
                      WHERE club_id = ?"
                );
                $stmt->execute([(int)$value, $referrerClubId]);
                break;

            case 'credit':
                self::addCredit($referrerClubId, $value);
                break;

            case 'discount':
            default:
                // Discount na nastepna fakture: zapisujemy jako credit equivalent.
                // 1% rabatu = (planowa cena * 1%) — bez ceny planu rozliczymy
                // procent przy nastepnym fakturowaniu. Tu zapisujemy notatke
                // w club_credits jako placeholder (value=0 ale activity_log).
                self::logDiscount($referrerClubId, $value);
                break;
        }

        // Activity log (best-effort)
        try {
            if (class_exists('\\App\\Models\\ActivityLogModel')) {
                $log = new \App\Models\ActivityLogModel();
                $log->insert([
                    'club_id'  => $referrerClubId,
                    'user_id'  => null,
                    'action'   => 'referral_reward_applied',
                    'entity'   => 'referral',
                    'entity_id'=> null,
                    'details'  => json_encode([
                        'type'  => $type,
                        'value' => $value,
                    ], JSON_UNESCAPED_UNICODE),
                ]);
            }
        } catch (\Throwable) {}
    }

    private static function addCredit(int $clubId, float $value): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            "INSERT INTO club_credits (club_id, balance)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)"
        );
        $stmt->execute([$clubId, $value]);
    }

    private static function logDiscount(int $clubId, float $pct): void
    {
        // Trzymamy "informacyjny" wpis w club_credits z balansem 0
        // — rzeczywista logika rabatowa stosuje sie przy nastepnej fakturze.
        $db = Database::pdo();
        $stmt = $db->prepare(
            "INSERT INTO club_credits (club_id, balance)
             VALUES (?, 0)
             ON DUPLICATE KEY UPDATE updated_at = NOW()"
        );
        $stmt->execute([$clubId]);
    }

    /** Heurystyka: ile mniej-wiecej miesiecy klub jest "active". */
    private static function approxActiveMonths(array $sub): int
    {
        $created = isset($sub['created_at']) ? strtotime((string)$sub['created_at']) : false;
        if ($created === false) {
            return 1;
        }
        $days = (int)floor((time() - $created) / 86400);
        return max(1, (int)floor($days / 30));
    }

    private static function notifyReferrer(int $referrerClubId, int $referredClubId, array $rewardCfg): void
    {
        try {
            if (!class_exists('\\App\\Helpers\\EmailService')) {
                return;
            }
            $db = Database::pdo();
            $stmt = $db->prepare("SELECT email, name FROM clubs WHERE id = ? LIMIT 1");
            $stmt->execute([$referrerClubId]);
            $referrer = $stmt->fetch();
            if (!$referrer || empty($referrer['email'])) {
                return;
            }
            $stmt->execute([$referredClubId]);
            $referred = $stmt->fetch();

            @\App\Helpers\EmailService::queueFromTemplate(
                $referrerClubId,
                'referral_qualified',
                (string)$referrer['email'],
                [
                    'referred' => ['name' => (string)($referred['name'] ?? '')],
                    'reward'   => [
                        'value' => (string)$rewardCfg['reward_value'],
                        'type'  => (string)$rewardCfg['reward_type'],
                    ],
                ],
                (string)$referrer['name']
            );
        } catch (\Throwable) {
            // best-effort
        }
    }
}
