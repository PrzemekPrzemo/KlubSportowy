<?php

namespace App\Controllers\Api\V2;

use App\Models\PaymentModel;

class PaymentsV2Controller extends ApiV2BaseController
{
    public function index(): void
    {
        $this->requireScope('payments:read');
        [$page, $perPage] = $this->pageParams(50);

        $memberId = isset($_GET['member_id']) && $_GET['member_id'] !== ''
            ? (int)$_GET['member_id']
            : null;
        $year = isset($_GET['year']) && $_GET['year'] !== ''
            ? (int)$_GET['year']
            : null;

        $result = (new PaymentModel())->listForClub($memberId, $year, $page, $perPage);
        $this->json($this->paginated($result, $perPage));
    }
}
