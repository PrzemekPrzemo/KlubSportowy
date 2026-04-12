<?php

namespace App\Controllers\Api;

use App\Models\MemberModel;

class MembersApiController extends BaseApiController
{
    public function index(): void
    {
        $this->requireScope('members:read');
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $q      = trim($_GET['q'] ?? '');
        $status = $_GET['status'] ?? '';
        $result = (new MemberModel())->search($q, $status ?: null, null, $page, 50);
        $this->paginated($result);
    }

    public function show(string $id): void
    {
        $this->requireScope('members:read');
        $member = (new MemberModel())->withSports((int)$id);
        if (empty($member)) {
            $this->error('Nie znaleziono zawodnika.', 404);
        }
        $this->json(['data' => $member]);
    }
}
