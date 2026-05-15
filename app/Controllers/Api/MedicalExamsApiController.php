<?php

namespace App\Controllers\Api;

use App\Helpers\Database;

class MedicalExamsApiController extends BaseApiController
{
    public function index(): void
    {
        $this->requireMember();

        $stmt = Database::pdo()->prepare(
            "SELECT id, exam_type, exam_date, valid_until, doctor_name, notes, document_path,
                    DATEDIFF(valid_until, CURDATE()) AS days_until_expiry
             FROM member_medical_exams
             WHERE member_id = ? AND club_id = ?
             ORDER BY valid_until ASC"
        );
        $stmt->execute([$this->memberId, $this->clubId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['days_until_expiry'] = (int)$r['days_until_expiry'];
        }
        unset($r);

        $this->json(['data' => $rows]);
    }
}
