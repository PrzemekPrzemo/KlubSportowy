<?php

namespace App\Models;

class DashboardWidgetModel extends BaseModel
{
    protected string $table = 'dashboard_widgets';

    /** Default widget keys in default order. */
    public const DEFAULTS = [
        'stats',
        'upcoming_events',
        'upcoming_trainings',
        'medical_alerts',
        'club_info',
        'announcements',
    ];

    /**
     * Get widget configuration for a user, ordered by position.
     * If user has no saved config, returns defaults.
     */
    public function getForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT widget_key, position, is_visible, config
             FROM `{$this->table}`
             WHERE user_id = ?
             ORDER BY position ASC"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            // Return defaults
            $result = [];
            foreach (self::DEFAULTS as $i => $key) {
                $result[] = [
                    'widget_key' => $key,
                    'position'   => $i,
                    'is_visible' => 1,
                    'config'     => null,
                ];
            }
            return $result;
        }

        return $rows;
    }

    /**
     * Save widget order and visibility for a user.
     *
     * @param int   $userId
     * @param array $widgets Array of ['widget_key' => string, 'is_visible' => 0|1]
     */
    public function saveOrder(int $userId, array $widgets): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}` (user_id, widget_key, position, is_visible)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE position = VALUES(position), is_visible = VALUES(is_visible)"
        );

        foreach ($widgets as $i => $w) {
            $key     = $w['widget_key'] ?? '';
            $visible = (int)($w['is_visible'] ?? 1);
            if ($key === '' || !in_array($key, self::DEFAULTS, true)) {
                continue;
            }
            $stmt->execute([$userId, $key, $i, $visible]);
        }
    }
}
