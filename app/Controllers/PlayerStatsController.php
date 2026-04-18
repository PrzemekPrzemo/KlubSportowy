<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Models\MemberModel;
use App\Models\PaymentModel;

/**
 * Panel statystyk per-zawodnik — profil z historią wyników,
 * treningów, wpłat + porównywarka side-by-side.
 */
class PlayerStatsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    /** Profil statystyk zawodnika. */
    public function profile(string $memberId): void
    {
        $member = (new MemberModel())->withSports((int)$memberId);
        if (empty($member)) {
            \App\Helpers\Session::flash('error', 'Nie znaleziono zawodnika.');
            $this->redirect('members');
        }

        $db      = Database::pdo();
        $mid     = (int)$memberId;
        $clubId  = $this->currentClub();

        // Attendance stats
        $attendance = $db->prepare(
            "SELECT ta.status, COUNT(*) AS cnt
             FROM training_attendees ta
             JOIN trainings t ON t.id = ta.training_id
             WHERE ta.member_id = ? AND t.club_id = ?
             GROUP BY ta.status"
        );
        $attendance->execute([$mid, $clubId]);
        $attendanceStats = $attendance->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Payment history (last 2 years)
        $payments = $db->prepare(
            "SELECT period_year, SUM(amount) AS total
             FROM payments WHERE member_id = ? AND club_id = ?
             GROUP BY period_year ORDER BY period_year DESC LIMIT 5"
        );
        $payments->execute([$mid, $clubId]);
        $paymentHistory = $payments->fetchAll();

        // Event participation
        $events = $db->prepare(
            "SELECT ee.status, COUNT(*) AS cnt
             FROM event_entries ee
             JOIN events e ON e.id = ee.event_id
             WHERE ee.member_id = ? AND e.club_id = ?
             GROUP BY ee.status"
        );
        $events->execute([$mid, $clubId]);
        $eventStats = $events->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Medical exams timeline
        $medical = $db->prepare(
            "SELECT exam_date, valid_until, exam_type
             FROM member_medical_exams WHERE member_id = ? AND club_id = ?
             ORDER BY exam_date DESC LIMIT 10"
        );
        $medical->execute([$mid, $clubId]);
        $medicalTimeline = $medical->fetchAll();

        // Licenses
        $licenses = $db->prepare(
            "SELECT ml.*, s.name AS sport_name, f.code AS federation_code
             FROM member_licenses ml
             LEFT JOIN sports s ON s.id = ml.sport_id
             LEFT JOIN federations f ON f.id = ml.federation_id
             WHERE ml.member_id = ? AND ml.club_id = ?
             ORDER BY ml.valid_until DESC"
        );
        $licenses->execute([$mid, $clubId]);
        $licenseList = $licenses->fetchAll();

        // Sport-specific results (from event_results)
        $results = $db->prepare(
            "SELECT er.score, er.place, er.extra, er.created_at,
                    e.name AS event_name, e.event_date, s.name AS sport_name
             FROM event_results er
             JOIN events e ON e.id = er.event_id
             LEFT JOIN sports s ON s.id = e.sport_id
             WHERE er.member_id = ? AND e.club_id = ?
             ORDER BY e.event_date DESC LIMIT 20"
        );
        $results->execute([$mid, $clubId]);
        $resultHistory = $results->fetchAll();

        $this->render('stats/profile', [
            'title'           => 'Statystyki: ' . $member['first_name'] . ' ' . $member['last_name'],
            'member'          => $member,
            'attendanceStats' => $attendanceStats,
            'paymentHistory'  => $paymentHistory,
            'eventStats'      => $eventStats,
            'medicalTimeline' => $medicalTimeline,
            'licenseList'     => $licenseList,
            'resultHistory'   => $resultHistory,
        ]);
    }

    /** Formularz porównywarki. */
    public function compare(): void
    {
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];

        $id1 = isset($_GET['m1']) ? (int)$_GET['m1'] : null;
        $id2 = isset($_GET['m2']) ? (int)$_GET['m2'] : null;

        $stats1 = $id1 ? $this->getCompareStats($id1) : null;
        $stats2 = $id2 ? $this->getCompareStats($id2) : null;

        $this->render('stats/compare', [
            'title'   => 'Porównywarka zawodników',
            'members' => $members,
            'id1'     => $id1,
            'id2'     => $id2,
            'stats1'  => $stats1,
            'stats2'  => $stats2,
        ]);
    }

    private function getCompareStats(int $memberId): ?array
    {
        $member = (new MemberModel())->withSports($memberId);
        if (empty($member)) return null;

        $db     = Database::pdo();
        $clubId = $this->currentClub();

        $trainings = $db->prepare(
            "SELECT COUNT(*) FROM training_attendees ta
             JOIN trainings t ON t.id = ta.training_id
             WHERE ta.member_id = ? AND t.club_id = ? AND ta.status IN ('obecny','zapisany')"
        );
        $trainings->execute([$memberId, $clubId]);
        $trainingCount = (int)$trainings->fetchColumn();

        $eventsCount = $db->prepare(
            "SELECT COUNT(*) FROM event_entries ee
             JOIN events e ON e.id = ee.event_id
             WHERE ee.member_id = ? AND e.club_id = ?"
        );
        $eventsCount->execute([$memberId, $clubId]);

        $avgScore = $db->prepare(
            "SELECT AVG(er.score) FROM event_results er
             JOIN events e ON e.id = er.event_id
             WHERE er.member_id = ? AND e.club_id = ? AND er.score IS NOT NULL"
        );
        $avgScore->execute([$memberId, $clubId]);

        $totalPaid = $db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM payments
             WHERE member_id = ? AND club_id = ?"
        );
        $totalPaid->execute([$memberId, $clubId]);

        return [
            'member'        => $member,
            'trainings'     => $trainingCount,
            'events'        => (int)$eventsCount->fetchColumn(),
            'avg_score'     => round((float)$avgScore->fetchColumn(), 2),
            'total_paid'    => (float)$totalPaid->fetchColumn(),
            'sports_count'  => count($member['sports'] ?? []),
            'join_date'     => $member['join_date'],
        ];
    }
}
