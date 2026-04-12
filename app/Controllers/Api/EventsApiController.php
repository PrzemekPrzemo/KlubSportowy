<?php

namespace App\Controllers\Api;

use App\Models\EventModel;

class EventsApiController extends BaseApiController
{
    public function index(): void
    {
        $this->requireScope('events:read');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $sportId = isset($_GET['sport_id']) ? (int)$_GET['sport_id'] : null;
        $type    = $_GET['type'] ?? '';
        $from    = $_GET['from'] ?? null;
        $result  = (new EventModel())->listForClub($sportId, $type ?: null, $from, $page, 50);
        $this->paginated($result);
    }

    public function upcoming(): void
    {
        $this->requireScope('events:read');
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        $events = (new EventModel())->upcomingForClub($limit);
        $this->json(['data' => $events]);
    }
}
