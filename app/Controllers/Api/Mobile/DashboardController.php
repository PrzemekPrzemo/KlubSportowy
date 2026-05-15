<?php

namespace App\Controllers\Api\Mobile;

use App\Helpers\Database;
use App\Models\MemberNotificationModel;
use App\Models\PaymentDueModel;
use App\Models\TrainingModel;

/**
 * Mobile API v1 — dashboard aggregate (today's trainings, overdue fees,
 * unread notifications, recent activity). Re-uses TrainingModel, PaymentDueModel,
 * MemberNotificationModel.
 */
class DashboardController extends V1Controller
{
    /** GET /api/mobile/v1/dashboard */
    public function index(): void
    {
        $this->requireAuth();
        $today = date('Y-m-d');

        // 1. Today's trainings (max 3).
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT t.id, t.name, t.location, t.start_time, t.end_time, t.status,
                    s.name AS sport_name, s.color AS sport_color,
                    ta.status AS my_status
             FROM trainings t
             LEFT JOIN sports s ON s.id = t.sport_id
             LEFT JOIN training_attendees ta
                 ON ta.training_id = t.id AND ta.member_id = ?
             WHERE t.club_id = ?
               AND DATE(t.start_time) = ?
               AND t.status IN ('zaplanowany','w_trakcie')
             ORDER BY t.start_time ASC
             LIMIT 3"
        );
        $stmt->execute([$this->memberId, $this->clubId, $today]);
        $todayTrainings = $stmt->fetchAll();

        // 2. Overdue / pending fees aggregate.
        $dueModel = new PaymentDueModel();
        $dues = $dueModel->forMember($this->memberId);
        $totalOverdue = 0.0;
        $totalOutstanding = 0.0;
        $overdueCount = 0;
        foreach ($dues as $d) {
            $remaining = (float)$d['net_amount'] - (float)$d['paid_amount'];
            $status    = $d['status'];
            if (in_array($status, ['pending', 'partial', 'overdue'], true)) {
                $totalOutstanding += $remaining;
                $isOverdue = $status === 'overdue'
                    || (in_array($status, ['pending', 'partial'], true) && $d['due_date'] < $today);
                if ($isOverdue) {
                    $totalOverdue += $remaining;
                    $overdueCount++;
                }
            }
        }

        // 3. Unread notifications count.
        $unread = (new MemberNotificationModel())->countUnread($this->memberId, $this->clubId);

        // 4. Recent activity (last 5 notifications).
        $recent = (new MemberNotificationModel())->allForMember($this->memberId, $this->clubId, 5);

        // 5. Upcoming trainings (next 7d, max 5).
        $stmt = $db->prepare(
            "SELECT t.id, t.name, t.start_time, t.location, s.name AS sport_name
             FROM trainings t
             LEFT JOIN sports s ON s.id = t.sport_id
             WHERE t.club_id = ?
               AND t.start_time > NOW()
               AND t.start_time < DATE_ADD(NOW(), INTERVAL 7 DAY)
               AND t.status = 'zaplanowany'
             ORDER BY t.start_time ASC
             LIMIT 5"
        );
        $stmt->execute([$this->clubId]);
        $upcoming = $stmt->fetchAll();

        $this->json([
            'today_trainings'      => $todayTrainings,
            'upcoming_trainings'   => $upcoming,
            'fees' => [
                'total_outstanding' => round($totalOutstanding, 2),
                'total_overdue'     => round($totalOverdue, 2),
                'overdue_count'     => $overdueCount,
            ],
            'notifications' => [
                'unread' => $unread,
                'recent' => array_map(fn($n) => [
                    'id'         => (int)$n['id'],
                    'type'       => $n['type'],
                    'title'      => $n['title'],
                    'body'       => $n['body'],
                    'link'       => $n['link'],
                    'read_at'    => $n['read_at'],
                    'created_at' => $n['created_at'],
                ], $recent),
            ],
        ]);
    }
}
