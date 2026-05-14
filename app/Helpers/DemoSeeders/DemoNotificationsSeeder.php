<?php

declare(strict_types=1);

namespace App\Helpers\DemoSeeders;

use App\Helpers\Database;
use PDO;
use Throwable;

/**
 * Seeds notification_rules (if the table exists) and a handful of in-app
 * notifications so the bell icon in navbar shows real items.
 */
final class DemoNotificationsSeeder
{
    public static function seed(array &$context): array
    {
        $db = Database::pdo();
        $clubId = (int)$context['club_id'];
        $adminUserId = $context['admin_user_id'] ?? null;

        $stats = ['rules' => 0, 'notifications' => 0];

        // ── notification_rules ────────────────────────────────────────────
        if (self::tableExists($db, 'notification_rules')) {
            $rules = [
                ['fee_reminder',     'days_after_due',     3,  'email'],
                ['fee_reminder',     'days_after_due',     7,  'both'],
                ['fee_reminder',     'days_after_due',     14, 'sms'],
                ['license_expiry',   'days_before_expiry', 30, 'email'],
                ['medical_expiry',   'days_before_expiry', 14, 'email'],
                ['event_reminder',   'days_before_expiry', 2,  'email'],
            ];
            $ins = $db->prepare(
                "INSERT IGNORE INTO notification_rules
                    (club_id, template_type, trigger_event, days_offset, channel, is_active, max_per_target, notes, created_at)
                 VALUES (?,?,?,?,?,1,1,'[DEMO] Auto-utworzona regula demo.',NOW())"
            );
            foreach ($rules as [$tpl, $trig, $off, $chan]) {
                $ins->execute([$clubId, $tpl, $trig, $off, $chan]);
                $stats['rules']++;
            }
        }

        // ── in-app notifications for admin ────────────────────────────────
        if ($adminUserId !== null) {
            $items = [
                ['payment_due',  'Zaleglosci w skladkach', 'Masz 8 zawodnikow z zaleglosciami za ostatni miesiac.', '/payments?status=overdue'],
                ['license_soon', 'Licencje do odnowienia', 'Konczy sie 3 licencje federacji w ciagu 30 dni.',       '/licenses?status=expiring'],
                ['event_today',  'Trening dzisiaj o 17:00', 'Trening sekcji pilki noznej rozpoczyna sie za 2h.',    '/trainings'],
                ['tournament',   'Wyniki turnieju gotowe',   'Turniej koszykowki #3 zostal zakonczony — rankingi przeliczone.', '/rankings'],
                ['new_member',   'Nowy zawodnik',            'Dolaczyl nowy zawodnik do sekcji siatkowki.',         '/members'],
            ];
            $ins = $db->prepare(
                "INSERT INTO notifications (club_id, user_id, type, title, body, link, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? HOUR))"
            );
            foreach ($items as $i => [$type, $title, $body, $link]) {
                $ins->execute([$clubId, $adminUserId, $type, $title, $body, $link, $i * 4]);
                $stats['notifications']++;
            }
        }

        return $stats;
    }

    private static function tableExists(PDO $db, string $name): bool
    {
        try {
            $r = $db->prepare("SHOW TABLES LIKE ?");
            $r->execute([$name]);
            return (bool)$r->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }
}
