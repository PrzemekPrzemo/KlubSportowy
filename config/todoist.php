<?php
// Konfiguracja integracji z Todoist (sync zgloszen z support_reports).
// Override w config/todoist.local.php (gitignored).
return [
    'api_token'        => $_ENV['TODOIST_API_TOKEN'] ?? getenv('TODOIST_API_TOKEN') ?: null,
    'project_id'       => '6gcqjmqj6QM9hQ2x', // ClubDesk.pl project ID
    'default_priority' => 'p3', // p1 = highest, p4 = lowest
    'timeout'          => 10,
];
