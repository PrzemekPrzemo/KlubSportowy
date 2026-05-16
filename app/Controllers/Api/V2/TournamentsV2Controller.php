<?php

namespace App\Controllers\Api\V2;

use App\Models\TournamentModel;

class TournamentsV2Controller extends ApiV2BaseController
{
    public function index(): void
    {
        $this->requireScope('tournaments:read');
        $sportKey = trim((string)($_GET['sport_key'] ?? '')) ?: null;

        $list = (new TournamentModel())->listForClub($sportKey);
        $this->json([
            'data' => $list,
            'meta' => ['total' => count($list)],
        ]);
    }
}
