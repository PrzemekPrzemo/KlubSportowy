<?php

namespace App\Sports\Fitness;

use App\Sports\Studio\StudioSportModule;

class FitnessModule extends StudioSportModule
{
    public function key(): string { return 'fitness'; }
    public function name(): string { return 'Fitness'; }

    public function defaultClassTemplates(): array
    {
        return [
            ['name' => 'HIIT',          'difficulty' => 'advanced',     'duration_min' => 45, 'day_of_week' => 1, 'time_start' => '19:00:00', 'max_capacity' => 20],
            ['name' => 'CrossTraining', 'difficulty' => 'intermediate', 'duration_min' => 60, 'day_of_week' => 2, 'time_start' => '18:30:00', 'max_capacity' => 16],
            ['name' => 'Cardio Mix',    'difficulty' => 'open',         'duration_min' => 50, 'day_of_week' => 4, 'time_start' => '18:00:00', 'max_capacity' => 25],
            ['name' => 'Body Pump',     'difficulty' => 'intermediate', 'duration_min' => 55, 'day_of_week' => 6, 'time_start' => '09:30:00', 'max_capacity' => 20],
        ];
    }
}
