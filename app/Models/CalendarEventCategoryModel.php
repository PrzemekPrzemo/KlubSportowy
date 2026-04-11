<?php

namespace App\Models;

class CalendarEventCategoryModel extends ClubScopedModel
{
    protected string $table = 'calendar_event_categories';

    public function seedDefaults(int $clubId): void
    {
        $defaults = [
            ['name' => 'Mecz ligowy', 'color' => '#dc3545', 'icon' => 'bi-flag-fill'],
            ['name' => 'Zawody',       'color' => '#0d6efd', 'icon' => 'bi-trophy'],
            ['name' => 'Trening',      'color' => '#198754', 'icon' => 'bi-stopwatch'],
            ['name' => 'Obóz',         'color' => '#fd7e14', 'icon' => 'bi-suitcase'],
            ['name' => 'Spotkanie',    'color' => '#6c757d', 'icon' => 'bi-people'],
        ];
        foreach ($defaults as $d) {
            $stmt = $this->db->prepare(
                "INSERT IGNORE INTO calendar_event_categories (club_id, name, color, icon) VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([$clubId, $d['name'], $d['color'], $d['icon']]);
        }
    }
}
