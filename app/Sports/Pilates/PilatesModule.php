<?php

namespace App\Sports\Pilates;

use App\Sports\Studio\StudioSportModule;

class PilatesModule extends StudioSportModule
{
    public function key(): string { return 'pilates'; }
    public function name(): string { return 'Pilates'; }

    public function defaultClassTemplates(): array
    {
        return [
            ['name' => 'Pilates Mat',      'difficulty' => 'beginner',     'duration_min' => 60, 'day_of_week' => 2, 'time_start' => '17:00:00', 'max_capacity' => 14],
            ['name' => 'Pilates Reformer', 'difficulty' => 'intermediate', 'duration_min' => 55, 'day_of_week' => 4, 'time_start' => '17:00:00', 'max_capacity' => 8],
            ['name' => 'Pilates Wall',     'difficulty' => 'open',         'duration_min' => 50, 'day_of_week' => 6, 'time_start' => '11:00:00', 'max_capacity' => 12],
        ];
    }
}
