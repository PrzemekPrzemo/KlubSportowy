<?php

namespace App\Models;

use App\Helpers\Database;
use PDO;

/**
 * Per-zawodnik opt-out z powiadomień. RODO compliance + UX.
 *
 * Zawodnik może:
 *   - całkowicie wyciszyć (template_type=NULL, opted_out=1)
 *   - wyciszyć tylko konkretny template (template_type='fee_reminder')
 *   - wybrać kanał (email/sms/both)
 *
 * Sprawdzanie:
 *   isOptedOut($memberId, $templateType) — true gdy zawodnik nie chce dostawać
 */
class MemberNotificationPrefModel
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::pdo();
    }

    /**
     * Sprawdza czy zawodnik wyciszył ten typ powiadomień (lub globalnie).
     * Używane przez cron przed wysłaniem każdej wiadomości.
     */
    public function isOptedOut(int $memberId, string $templateType, string $channel = 'email'): bool
    {
        $stmt = $this->db->prepare(
            "SELECT template_type, channel, opted_out
             FROM member_notification_prefs
             WHERE member_id = ?
               AND (template_type = ? OR template_type IS NULL)
               AND opted_out = 1"
        );
        $stmt->execute([$memberId, $templateType]);
        foreach ($stmt->fetchAll() as $pref) {
            // Global opt-out (template_type NULL) blokuje wszystko
            if ($pref['template_type'] === null) {
                return true;
            }
            // Channel match: 'both' blokuje email i sms
            $blocked = $pref['channel'] === 'both' || $pref['channel'] === $channel;
            if ($blocked) {
                return true;
            }
        }
        return false;
    }

    public function listForMember(int $memberId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM member_notification_prefs
             WHERE member_id = ?
             ORDER BY template_type"
        );
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    /**
     * Upsert preferencji — używane z portalu zawodnika.
     */
    public function setPreference(int $memberId, int $clubId, ?string $templateType, string $channel, bool $optedOut): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO member_notification_prefs
             (member_id, club_id, template_type, channel, opted_out)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                channel = VALUES(channel),
                opted_out = VALUES(opted_out)"
        );
        return $stmt->execute([$memberId, $clubId, $templateType, $channel, $optedOut ? 1 : 0]);
    }
}
