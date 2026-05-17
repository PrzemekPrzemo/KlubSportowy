<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;

/**
 * TrainerAttendanceController — flow trenera do wpisywania obecnosci.
 *
 * Dostepne dla rol: trener, instruktor, zarzad, admin.
 *
 * Defense-in-depth:
 *  - GET grid wymaga aby trening byl prowadzony przez bieżącego usera
 *    (instructor_id = current_user) ALBO user jest zarzad/admin/super admin.
 *  - Cross-club guard: trainings.club_id musi byc rowny ClubContext::current().
 *  - Trener moze wpisac historyczna obecnosc do 7 dni wstecz. Po tym
 *    tylko zarzad/admin (lub super admin) moze modyfikowac.
 *  - Kazda zmiana statusu loguje sie do tenant_access_log z severity=info
 *    i operation=write (action='attendance_marked').
 */
class TrainerAttendanceController extends BaseController
{
    /** Historyczny limit edycji dla trenera/instruktora (dni). */
    public const PAST_EDIT_LIMIT_DAYS = 7;

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['trener', 'instruktor', 'zarzad', 'admin']);
    }

    /**
     * GET /trainer/dashboard
     */
    public function dashboard(): void
    {
        $userId = (int)Auth::id();
        $clubId = (int)ClubContext::current();
        $db     = Database::pdo();

        // Today
        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');

        $stmtToday = $db->prepare(
            "SELECT t.id, t.name, t.start_time, t.end_time, t.location, t.club_id,
                    (SELECT COUNT(*) FROM training_attendees ta WHERE ta.training_id = t.id) AS total_attendees,
                    (SELECT COUNT(*) FROM training_attendees ta
                       WHERE ta.training_id = t.id
                         AND ta.status IN ('obecny','nieobecny','spozniony','wypisany')) AS marked
             FROM trainings t
             WHERE t.instructor_id = ?
               AND t.club_id = ?
               AND DATE(t.start_time) = ?
             ORDER BY t.start_time ASC"
        );
        $stmtToday->execute([$userId, $clubId, $today]);
        $todayTrainings = $stmtToday->fetchAll();

        // Upcoming week
        $weekEnd = (new \DateTimeImmutable('today'))->modify('+7 days')->format('Y-m-d');
        $stmtWeek = $db->prepare(
            "SELECT t.id, t.name, t.start_time, t.end_time, t.location, t.club_id
             FROM trainings t
             WHERE t.instructor_id = ?
               AND t.club_id = ?
               AND DATE(t.start_time) > ?
               AND DATE(t.start_time) <= ?
               AND t.status IN ('zaplanowany','w_trakcie')
             ORDER BY t.start_time ASC
             LIMIT 30"
        );
        $stmtWeek->execute([$userId, $clubId, $today, $weekEnd]);
        $upcomingWeek = $stmtWeek->fetchAll();

        // Recently marked (audit) — last 20 z tenant_access_log
        $recent = [];
        try {
            $stmtRecent = $db->prepare(
                "SELECT occurred_at, table_name, notes
                 FROM tenant_access_log
                 WHERE user_id = ?
                   AND active_club_id = ?
                   AND notes LIKE 'attendance_marked%'
                 ORDER BY occurred_at DESC
                 LIMIT 20"
            );
            $stmtRecent->execute([$userId, $clubId]);
            $recent = $stmtRecent->fetchAll();
        } catch (\Throwable) {
            $recent = [];
        }

        // Cross-club today (treningi trenera w innych klubach dzisiaj)
        $stmtCross = $db->prepare(
            "SELECT t.id, t.name, t.start_time, t.club_id, c.name AS club_name
             FROM trainings t
             LEFT JOIN clubs c ON c.id = t.club_id
             WHERE t.instructor_id = ?
               AND t.club_id <> ?
               AND DATE(t.start_time) = ?
             ORDER BY t.start_time ASC
             LIMIT 20"
        );
        $stmtCross->execute([$userId, $clubId, $today]);
        $crossClubToday = $stmtCross->fetchAll();

        $this->render('trainer/dashboard', [
            'title'          => 'Panel trenera',
            'todayTrainings' => $todayTrainings,
            'upcomingWeek'   => $upcomingWeek,
            'recent'         => $recent,
            'crossClubToday' => $crossClubToday,
        ]);
    }

    /**
     * GET /trainer/training/:id/attendance
     */
    public function grid(string $id): void
    {
        $trainingId = (int)$id;
        $training   = $this->loadTrainingForCurrentUser($trainingId);
        if ($training === null) return;

        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT ta.id, ta.member_id, ta.status, ta.registered_at, ta.notes,
                    m.first_name, m.last_name, m.member_number, m.photo_path
             FROM training_attendees ta
             JOIN members m ON m.id = ta.member_id
             WHERE ta.training_id = ?
             ORDER BY m.last_name, m.first_name"
        );
        $stmt->execute([$trainingId]);
        $attendees = $stmt->fetchAll();

        $editable = $this->isTrainingEditableByCurrentUser($training);

        $this->render('trainer/attendance/grid', [
            'title'         => 'Obecność: ' . $training['name'],
            'training'      => $training,
            'attendees'     => $attendees,
            'editable'      => $editable,
            'pastLimitDays' => self::PAST_EDIT_LIMIT_DAYS,
        ]);
    }

    /**
     * POST /trainer/training/:id/attendance/save
     *
     * Akceptuje:
     *   status[<attendee_id>] = 'obecny'|'spozniony'|'nieobecny'|'wypisany'|'zapisany'
     *   notes[<attendee_id>]  = string (opcjonalnie)
     *   bulk_action          = 'all_present'|'all_absent' (opcjonalnie, nadpisuje statusy)
     */
    public function save(string $id): void
    {
        Csrf::verify();
        $trainingId = (int)$id;
        $training   = $this->loadTrainingForCurrentUser($trainingId);
        if ($training === null) return;

        if (!$this->isTrainingEditableByCurrentUser($training)) {
            Session::flash('error',
                'Nie mozesz edytowac obecnosci dla tego treningu — '
                . 'minelo ' . self::PAST_EDIT_LIMIT_DAYS . ' dni od daty treningu. '
                . 'Skontaktuj sie z zarzadem klubu.'
            );
            $this->redirect('trainer/training/' . $trainingId . '/attendance');
        }

        $db = Database::pdo();

        $allowedStatuses = ['zapisany','obecny','nieobecny','spozniony','wypisany'];
        $statuses = $_POST['status'] ?? [];
        $notes    = $_POST['notes']  ?? [];
        $bulk     = (string)($_POST['bulk_action'] ?? '');

        $stmt = $db->prepare("SELECT id FROM training_attendees WHERE training_id = ?");
        $stmt->execute([$trainingId]);
        $validIds = array_map(static fn(array $r): int => (int)$r['id'], $stmt->fetchAll());

        $touched = 0;
        $userId  = (int)Auth::id();
        $clubId  = (int)ClubContext::current();

        $db->beginTransaction();
        try {
            $update = $db->prepare(
                "UPDATE training_attendees SET status = ?, notes = ? WHERE id = ? AND training_id = ?"
            );

            foreach ($validIds as $aid) {
                $status = (string)($statuses[$aid] ?? '');
                if ($bulk === 'all_present')      $status = 'obecny';
                elseif ($bulk === 'all_absent')   $status = 'nieobecny';

                if (!in_array($status, $allowedStatuses, true)) {
                    continue;
                }

                $note = isset($notes[$aid]) ? trim((string)$notes[$aid]) : '';
                if ($note === '') $note = null;
                if ($note !== null && mb_strlen($note) > 255) {
                    $note = mb_substr($note, 0, 255);
                }

                $update->execute([$status, $note, $aid, $trainingId]);
                $touched++;
            }

            // Audit log
            try {
                $log = $db->prepare(
                    "INSERT INTO tenant_access_log
                        (user_id, username, active_club_id, table_name, operation,
                         request_path, request_method, severity, notes)
                     VALUES (?, ?, ?, 'training_attendees', 'write', ?, 'POST', 'info', ?)"
                );
                $log->execute([
                    $userId,
                    (string)(Auth::user()['username'] ?? ''),
                    $clubId,
                    (string)($_SERVER['REQUEST_URI'] ?? '/trainer/training/' . $trainingId . '/attendance/save'),
                    'attendance_marked training_id=' . $trainingId . ' touched=' . $touched,
                ]);
            } catch (\Throwable $e) {
                error_log('tenant_access_log insert failed (attendance): ' . $e->getMessage());
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Blad zapisu obecnosci: ' . $e->getMessage());
            $this->redirect('trainer/training/' . $trainingId . '/attendance');
        }

        // Trigger achievements (best-effort)
        try {
            if (class_exists(\App\Helpers\Achievements\AchievementEvaluator::class)) {
                $stmt = $db->prepare(
                    "SELECT DISTINCT member_id FROM training_attendees WHERE training_id = ?"
                );
                $stmt->execute([$trainingId]);
                foreach ($stmt->fetchAll() as $row) {
                    \App\Helpers\Achievements\AchievementEvaluator::evaluateForMember((int)$row['member_id'], 'attendance');
                }
            }
        } catch (\Throwable $e) {
            error_log('Achievements after attendance save failed: ' . $e->getMessage());
        }

        Session::flash('success', 'Zapisano obecnosc (' . $touched . ' wpisow).');
        $this->redirect('trainer/training/' . $trainingId . '/attendance');
    }

    /**
     * GET /trainer/trainings/today — quick shortcut
     */
    public function todayList(): void
    {
        $userId = (int)Auth::id();
        $clubId = (int)ClubContext::current();
        $today  = (new \DateTimeImmutable('today'))->format('Y-m-d');

        $stmt = Database::pdo()->prepare(
            "SELECT t.id, t.name, t.start_time, t.end_time, t.location,
                    (SELECT COUNT(*) FROM training_attendees ta WHERE ta.training_id = t.id) AS total_attendees,
                    (SELECT COUNT(*) FROM training_attendees ta
                       WHERE ta.training_id = t.id
                         AND ta.status IN ('obecny','nieobecny','spozniony','wypisany')) AS marked
             FROM trainings t
             WHERE t.instructor_id = ?
               AND t.club_id = ?
               AND DATE(t.start_time) = ?
             ORDER BY t.start_time ASC"
        );
        $stmt->execute([$userId, $clubId, $today]);
        $list = $stmt->fetchAll();

        $this->render('trainer/dashboard', [
            'title'          => 'Dzisiejsze treningi',
            'todayTrainings' => $list,
            'upcomingWeek'   => [],
            'recent'         => [],
            'crossClubToday' => [],
        ]);
    }

    /**
     * GET /trainer/members — roster widget: zawodnicy w sekcjach trenera.
     */
    public function members(): void
    {
        $userId = (int)Auth::id();
        $clubId = (int)ClubContext::current();
        $db     = Database::pdo();

        $filterSection = isset($_GET['club_sport_id']) ? (int)$_GET['club_sport_id'] : 0;

        // Sport sections instruktora — wnioskowane z trainings: distinct club_sport_id
        // gdzie instructor_id = current_user AND club_id = current_club.
        // (Aplikacja nie posiada osobnej tabeli sport_section_trainers.)
        $stmtSec = $db->prepare(
            "SELECT DISTINCT cs.id, s.name AS sport_name, s.sport_key
             FROM trainings t
             JOIN club_sports cs ON cs.id = t.club_sport_id
             JOIN sports s       ON s.id = cs.sport_id
             WHERE t.instructor_id = ?
               AND t.club_id = ?
               AND cs.id IS NOT NULL
             ORDER BY s.name"
        );
        $stmtSec->execute([$userId, $clubId]);
        $sections = $stmtSec->fetchAll();

        // Zarzad/admin: pokazuje wszystkie sekcje klubu (defense-in-depth: nie cross-club).
        if (empty($sections) && Auth::hasRole(['zarzad','admin'])) {
            $stmtAll = $db->prepare(
                "SELECT cs.id, s.name AS sport_name, s.sport_key
                 FROM club_sports cs
                 JOIN sports s ON s.id = cs.sport_id
                 WHERE cs.club_id = ?
                 ORDER BY s.name"
            );
            $stmtAll->execute([$clubId]);
            $sections = $stmtAll->fetchAll();
        }

        $sectionIds = array_map(static fn(array $r): int => (int)$r['id'], $sections);

        $members = [];
        if (!empty($sectionIds)) {
            $useIds = $filterSection > 0 && in_array($filterSection, $sectionIds, true)
                ? [$filterSection]
                : $sectionIds;

            $placeholders = implode(',', array_fill(0, count($useIds), '?'));

            $sql = "SELECT m.id, m.first_name, m.last_name, m.member_number, m.email, m.phone, m.photo_path,
                           m.status, m.club_id,
                           (SELECT MAX(ta.registered_at) FROM training_attendees ta
                              JOIN trainings t2 ON t2.id = ta.training_id
                              WHERE ta.member_id = m.id
                                AND t2.club_id = m.club_id
                                AND ta.status IN ('obecny','spozniony')) AS last_attendance,
                           (SELECT ROUND(
                               100.0 * SUM(CASE WHEN ta2.status IN ('obecny','spozniony') THEN 1 ELSE 0 END)
                                     / NULLIF(COUNT(ta2.id), 0)
                              , 1)
                              FROM training_attendees ta2
                              JOIN trainings t3 ON t3.id = ta2.training_id
                              WHERE ta2.member_id = m.id
                                AND t3.club_id = m.club_id
                                AND ta2.status IN ('obecny','spozniony','nieobecny','wypisany')
                           ) AS attendance_pct,
                           (SELECT mme.valid_until
                              FROM member_medical_exams mme
                              WHERE mme.member_id = m.id
                              ORDER BY mme.valid_until DESC
                              LIMIT 1) AS medical_valid_until
                    FROM members m
                    JOIN member_sports ms ON ms.member_id = m.id
                    WHERE m.club_id = ?
                      AND ms.club_sport_id IN ($placeholders)
                      AND m.status IN ('aktywny','urlop')
                    GROUP BY m.id
                    ORDER BY m.last_name, m.first_name
                    LIMIT 500";

            $params = array_merge([$clubId], $useIds);
            $stmtM = $db->prepare($sql);
            $stmtM->execute($params);
            $members = $stmtM->fetchAll();
        }

        $this->render('trainer/members/roster', [
            'title'         => 'Moi zawodnicy',
            'sections'      => $sections,
            'members'       => $members,
            'filterSection' => $filterSection,
        ]);
    }

    /**
     * Loaduje trening i waliduje wszystkie guards:
     *  - istnieje
     *  - jest w aktualnym klubie (cross-club guard)
     *  - bieżący user jest instructor_id ALBO zarzad/admin/super admin
     *
     * Redirectuje z flash i zwraca null w razie odmowy.
     *
     * @return array<string,mixed>|null
     */
    private function loadTrainingForCurrentUser(int $trainingId): ?array
    {
        if ($trainingId <= 0) {
            Session::flash('error', 'Nieprawidłowy identyfikator treningu.');
            $this->redirect('trainer/dashboard');
        }

        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT t.*, c.name AS club_name
             FROM trainings t
             LEFT JOIN clubs c ON c.id = t.club_id
             WHERE t.id = ? LIMIT 1"
        );
        $stmt->execute([$trainingId]);
        $training = $stmt->fetch();
        if (!$training) {
            Session::flash('error', 'Nie znaleziono treningu.');
            $this->redirect('trainer/dashboard');
        }

        // Cross-club guard: training musi nalezec do aktualnego klubu.
        $currentClub = (int)ClubContext::current();
        if ((int)$training['club_id'] !== $currentClub) {
            Session::flash('error',
                'Ten trening nalezy do innego klubu. Przelacz klub aby zobaczyc obecnosci.'
            );
            $this->redirect('trainer/dashboard');
        }

        // Permission guard: instructor of this training, OR zarzad/admin/super admin.
        $userId       = (int)Auth::id();
        $isPrivileged = Auth::isSuperAdmin() || Auth::hasRole(['zarzad','admin']);
        $isOwner      = (int)($training['instructor_id'] ?? 0) === $userId;

        if (!$isPrivileged && !$isOwner) {
            Session::flash('error',
                'Nie jestes prowadzacym tego treningu. Tylko trener prowadzacy moze wpisac obecnosc.'
            );
            $this->redirect('trainer/dashboard');
        }

        return is_array($training) ? $training : null;
    }

    /**
     * Trener/instruktor moze wpisac obecnosc tylko do PAST_EDIT_LIMIT_DAYS dni
     * od daty treningu. Po tym tylko zarzad/admin/super admin.
     */
    private function isTrainingEditableByCurrentUser(array $training): bool
    {
        if (Auth::isSuperAdmin() || Auth::hasRole(['zarzad','admin'])) {
            return true;
        }
        try {
            $start = new \DateTimeImmutable((string)$training['start_time']);
        } catch (\Throwable) {
            return true;
        }
        $now = new \DateTimeImmutable('now');
        if ($start > $now) {
            return true; // przyszly trening — zapis dozwolony
        }
        $diffDays = (int)$now->diff($start)->format('%a');
        return $diffDays <= self::PAST_EDIT_LIMIT_DAYS;
    }
}
