<?php

namespace App\Controllers\Api;

use App\Models\PaymentModel;

class PaymentsApiController extends BaseApiController
{
    public function index(): void
    {
        $this->requireScope('payments:read');
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $memberId = isset($_GET['member_id']) ? (int)$_GET['member_id'] : null;
        $year     = isset($_GET['year']) ? (int)$_GET['year'] : null;
        $result   = (new PaymentModel())->listForClub($memberId, $year, $page, 50);
        $this->paginated($result);
    }

    public function summary(): void
    {
        $this->requireScope('payments:read');
        $total = (new PaymentModel())->totalForClubThisYear();
        $this->json(['data' => ['year' => (int)date('Y'), 'total' => $total]]);
    }
}
