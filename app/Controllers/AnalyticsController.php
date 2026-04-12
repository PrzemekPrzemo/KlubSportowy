<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Database;

class AnalyticsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function dashboard(): void
    {
        $year = (int)($_GET['year'] ?? date('Y'));
        $data = $this->aggregateData($year);

        if (($_GET['format'] ?? '') === 'json') {
            $this->json($data);
        }

        $this->render('analytics/dashboard', [
            'title'     => 'Analityka klubu',
            'analytics' => $data,
            'year'      => $year,
        ]);
    }

    public function data(): void
    {
        $year = (int)($_GET['year'] ?? date('Y'));
        $this->json($this->aggregateData($year));
    }

    private function aggregateData(int $year): array
    {
        $db     = Database::pdo();
        $clubId = ClubContext::current();

        // Zawodnicy per miesiąc (ostatnie 12 miesięcy)
        $membersPerMonth = [];
        $stmt = $db->prepare(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS cnt
             FROM members
             WHERE club_id = ? AND YEAR(created_at) = ?
             GROUP BY month ORDER BY month"
        );
        $stmt->execute([$clubId, $year]);
        foreach ($stmt->fetchAll() as $row) {
            $membersPerMonth[$row['month']] = (int)$row['cnt'];
        }

        // Płatności per miesiąc
        $paymentsPerMonth = [];
        $stmt = $db->prepare(
            "SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month, SUM(amount) AS total
             FROM payments
             WHERE club_id = ? AND YEAR(payment_date) = ?
             GROUP BY month ORDER BY month"
        );
        $stmt->execute([$clubId, $year]);
        foreach ($stmt->fetchAll() as $row) {
            $paymentsPerMonth[$row['month']] = round((float)$row['total'], 2);
        }

        // Frekwencja na treningach per miesiąc
        $attendancePerMonth = [];
        $stmt = $db->prepare(
            "SELECT DATE_FORMAT(t.start_time, '%Y-%m') AS month,
                    COUNT(ta.id) AS cnt
             FROM trainings t
             JOIN training_attendees ta ON ta.training_id = t.id AND ta.status = 'obecny'
             WHERE t.club_id = ? AND YEAR(t.start_time) = ?
             GROUP BY month ORDER BY month"
        );
        $stmt->execute([$clubId, $year]);
        foreach ($stmt->fetchAll() as $row) {
            $attendancePerMonth[$row['month']] = (int)$row['cnt'];
        }

        // Zawodnicy per sport (doughnut)
        $membersPerSport = [];
        $stmt = $db->prepare(
            "SELECT s.name, COUNT(DISTINCT ms.member_id) AS cnt
             FROM member_sports ms
             JOIN club_sports cs ON cs.id = ms.club_sport_id AND cs.club_id = ?
             JOIN sports s ON s.id = cs.sport_id
             GROUP BY s.name ORDER BY cnt DESC"
        );
        $stmt->execute([$clubId]);
        foreach ($stmt->fetchAll() as $row) {
            $membersPerSport[$row['name']] = (int)$row['cnt'];
        }

        // Podsumowania
        $totalMembers = 0;
        $stmt = $db->prepare("SELECT COUNT(*) FROM members WHERE club_id = ?");
        $stmt->execute([$clubId]);
        $totalMembers = (int)$stmt->fetchColumn();

        $totalRevenue = 0;
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE club_id = ? AND YEAR(payment_date) = ?");
        $stmt->execute([$clubId, $year]);
        $totalRevenue = round((float)$stmt->fetchColumn(), 2);

        $avgAttendance = 0;
        $stmt = $db->prepare(
            "SELECT AVG(att) FROM (
                SELECT COUNT(ta.id) AS att
                FROM trainings t
                JOIN training_attendees ta ON ta.training_id = t.id AND ta.status = 'obecny'
                WHERE t.club_id = ? AND YEAR(t.start_time) = ?
                GROUP BY t.id
             ) sub"
        );
        $stmt->execute([$clubId, $year]);
        $avgAttendance = round((float)$stmt->fetchColumn(), 1);

        $activeSports = 0;
        $stmt = $db->prepare("SELECT COUNT(*) FROM club_sports WHERE club_id = ? AND is_active = 1");
        $stmt->execute([$clubId]);
        $activeSports = (int)$stmt->fetchColumn();

        // Uzupełnij miesiące
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = sprintf('%d-%02d', $year, $m);
        }

        $membersGrowth = [];
        $paymentsBars  = [];
        $attendBars    = [];
        foreach ($months as $mo) {
            $membersGrowth[] = $membersPerMonth[$mo] ?? 0;
            $paymentsBars[]  = $paymentsPerMonth[$mo] ?? 0;
            $attendBars[]    = $attendancePerMonth[$mo] ?? 0;
        }

        return [
            'months'           => $months,
            'membersGrowth'    => $membersGrowth,
            'paymentsBars'     => $paymentsBars,
            'attendanceBars'   => $attendBars,
            'membersPerSport'  => $membersPerSport,
            'totalMembers'     => $totalMembers,
            'totalRevenue'     => $totalRevenue,
            'avgAttendance'    => $avgAttendance,
            'activeSports'     => $activeSports,
        ];
    }
}
