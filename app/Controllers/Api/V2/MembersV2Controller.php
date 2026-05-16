<?php

namespace App\Controllers\Api\V2;

use App\Models\MemberModel;

class MembersV2Controller extends ApiV2BaseController
{
    public function index(): void
    {
        $this->requireScope('members:read');
        [$page, $perPage] = $this->pageParams(50);

        $clubSportId = isset($_GET['sport_section_id']) && $_GET['sport_section_id'] !== ''
            ? (int)$_GET['sport_section_id']
            : null;
        $q      = trim((string)($_GET['q'] ?? ''));
        $status = trim((string)($_GET['status'] ?? '')) ?: null;

        $result = (new MemberModel())->search($q, $status, $clubSportId, $page, $perPage);
        $this->json($this->paginated($result, $perPage));
    }

    public function show(string $id): void
    {
        $this->requireScope('members:read');
        $member = (new MemberModel())->withSports((int)$id);
        if (empty($member)) {
            $this->error('Member not found.', 404, 'not_found');
        }
        $this->json(['data' => $member]);
    }
}
