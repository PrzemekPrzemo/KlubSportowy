<?php

namespace App\Controllers\Api\V2;

use App\Models\TrainingModel;

class TrainingsV2Controller extends ApiV2BaseController
{
    public function index(): void
    {
        $this->requireScope('trainings:read');
        [$page, $perPage] = $this->pageParams(50);

        $clubSportId = isset($_GET['sport_section_id']) && $_GET['sport_section_id'] !== ''
            ? (int)$_GET['sport_section_id']
            : null;
        $from = trim((string)($_GET['from'] ?? '')) ?: null;

        // Walidacja prostego formatu YYYY-MM-DD lub YYYY-MM-DD HH:MM:SS
        if ($from !== null && !preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $from)) {
            $this->error('Invalid `from` date format. Expected YYYY-MM-DD.', 400, 'invalid_param');
        }

        $result = (new TrainingModel())->listForClub($clubSportId, $from, $page, $perPage);
        $this->json($this->paginated($result, $perPage));
    }
}
