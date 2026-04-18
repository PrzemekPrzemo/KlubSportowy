<?php

namespace App\Helpers;

use App\Models\MemberModel;

class NotificationDispatcher
{
    /**
     * Notify all active club members using an email template.
     *
     * @param int    $clubId       Club ID
     * @param string $templateType Email template type key
     * @param array  $vars         Template variables
     * @return int   Number of members notified
     */
    public static function notifyClubMembers(int $clubId, string $templateType, array $vars): int
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT id, first_name, last_name, email
             FROM members
             WHERE club_id = ? AND status = 'aktywny' AND email IS NOT NULL AND email != ''"
        );
        $stmt->execute([$clubId]);
        $count = 0;

        foreach ($stmt->fetchAll() as $m) {
            $v = array_merge($vars, [
                'first_name' => $m['first_name'],
                'last_name'  => $m['last_name'],
            ]);
            EmailService::queueFromTemplate(
                $clubId,
                $templateType,
                $m['email'],
                $v,
                $m['first_name'] . ' ' . $m['last_name']
            );
            $count++;
        }

        return $count;
    }
}
