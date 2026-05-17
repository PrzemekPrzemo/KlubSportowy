<?php

namespace App\Sports\Yoga;

use App\Sports\Studio\StudioSportModule;

class YogaModule extends StudioSportModule
{
    public function key(): string { return 'yoga'; }
    public function name(): string { return 'Joga'; }

    public function defaultClassTemplates(): array
    {
        // Domyslne klasy seedowane przy onboardingu klubu jogi.
        // day_of_week: 1=Mon .. 7=Sun
        return [
            ['name' => 'Yoga Vinyasa',  'difficulty' => 'intermediate', 'duration_min' => 75, 'day_of_week' => 1, 'time_start' => '18:00:00', 'max_capacity' => 15],
            ['name' => 'Yoga Hatha',    'difficulty' => 'beginner',     'duration_min' => 60, 'day_of_week' => 3, 'time_start' => '17:30:00', 'max_capacity' => 18],
            ['name' => 'Yoga Ashtanga', 'difficulty' => 'advanced',     'duration_min' => 90, 'day_of_week' => 5, 'time_start' => '07:00:00', 'max_capacity' => 12],
            ['name' => 'Yin Yoga',      'difficulty' => 'open',         'duration_min' => 75, 'day_of_week' => 6, 'time_start' => '10:00:00', 'max_capacity' => 16],
        ];
    }
}
