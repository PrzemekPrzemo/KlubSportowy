<?php

namespace App\Controllers\Api;

use App\Models\DisciplineModel;
use App\Models\SportModel;

class SportsApiController extends BaseApiController
{
    public function index(): void
    {
        $this->requireScope('sports:read');
        $sports = (new SportModel())->listForClub($this->clubId);
        $this->json(['data' => $sports]);
    }

    public function disciplines(string $sportId): void
    {
        $this->requireScope('sports:read');
        $disciplines = (new DisciplineModel())->listForSport((int)$sportId, $this->clubId);
        $this->json(['data' => $disciplines]);
    }

    public function catalog(): void
    {
        $this->requireScope('sports:read');
        $all = (new SportModel())->listActive();
        $this->json(['data' => $all]);
    }
}
