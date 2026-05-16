<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Scheduling\TrainerScheduleService;
use App\Helpers\Session;
use App\Models\TrainerAvailabilityModel;
use App\Models\TrainerLeaveModel;
use App\Models\TrainerScheduleConflictModel;
use DateTimeImmutable;

/**
 * /club/trainer-schedule
 *
 * Admin klubu (zarzad) zarzadza dostepnoscia trenerow:
 *   - przeglada siatke dostepnosci wszystkich trenerow klubu
 *   - edytuje sloty availability (per-klub lub globalne)
 *   - dodaje urlopy/nieobecnosci
 *   - widzi liste wykrytych konfliktow
 */
class TrainerAvailabilityController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad', 'admin']);
    }

    /** Lista trenerow klubu + siatka dostepnosci. */
    public function index(): void
    {
        $clubId   = $this->currentClub();
        $trainers = $this->trainersForClub($clubId);

        // Doladuj dla kazdego trenera: sloty + najblizsze urlopy
        $availModel = new TrainerAvailabilityModel();
        $leaveModel = new TrainerLeaveModel();
        foreach ($trainers as &$t) {
            $t['availability'] = $availModel->forUser((int)$t['id'], $clubId);
            $t['leaves']       = $leaveModel->upcomingForUser((int)$t['id'], 5);
        }
        unset($t);

        $conflicts = (new TrainerScheduleConflictModel())->unresolvedForClub($clubId, 50);

        $this->render('club/trainer_schedule/index', [
            'title'     => 'Plany trenerow',
            'trainers'  => $trainers,
            'conflicts' => $conflicts,
            'clubId'    => $clubId,
        ]);
    }

    public function editAvailability(string $userId): void
    {
        $uid    = (int)$userId;
        $clubId = $this->currentClub();
        $this->assertTrainerOfClub($uid, $clubId);

        $availability = (new TrainerAvailabilityModel())->forUser($uid, $clubId);
        $trainer      = $this->fetchTrainer($uid);

        $this->render('club/trainer_schedule/edit_availability', [
            'title'        => 'Dostepnosc trenera',
            'trainer'      => $trainer,
            'availability' => $availability,
            'clubId'       => $clubId,
        ]);
    }

    public function storeAvailability(string $userId): void
    {
        Csrf::verify();
        $uid    = (int)$userId;
        $clubId = $this->currentClub();
        $this->assertTrainerOfClub($uid, $clubId);

        // Format POST:
        //   slots[][weekday]    1..7
        //   slots[][time_start] HH:MM
        //   slots[][time_end]   HH:MM
        //   slots[][scope]      'global' | 'club'
        //   slots[][valid_from] YYYY-MM-DD | ''
        //   slots[][valid_until] YYYY-MM-DD | ''
        $raw = $_POST['slots'] ?? [];
        $slots = [];
        foreach ((array)$raw as $r) {
            $weekday = (int)($r['weekday'] ?? 0);
            $ts      = trim((string)($r['time_start'] ?? ''));
            $te      = trim((string)($r['time_end'] ?? ''));
            if ($weekday < 1 || $weekday > 7 || $ts === '' || $te === '' || $ts >= $te) {
                continue;
            }
            $slots[] = [
                'weekday'     => $weekday,
                'time_start'  => $this->normalizeTime($ts),
                'time_end'    => $this->normalizeTime($te),
                'club_id'     => (($r['scope'] ?? 'club') === 'global') ? null : $clubId,
                'valid_from'  => $this->emptyToNull($r['valid_from'] ?? null),
                'valid_until' => $this->emptyToNull($r['valid_until'] ?? null),
            ];
        }

        // Replace tylko sloty per-tego-klub; globalne zostawiamy (chyba ze user wrzucil nowe).
        // Strategia: dzielimy slots na global / per-club, robimy 2 replace'y.
        $availModel    = new TrainerAvailabilityModel();
        $globalSlots   = array_values(array_filter($slots, static fn($s) => $s['club_id'] === null));
        $perClubSlots  = array_values(array_filter($slots, static fn($s) => $s['club_id'] !== null));

        $availModel->replaceForUser($uid, $perClubSlots, $clubId);
        // Globalne (NULL club_id) — replace tylko, jezeli user wyslal jakikolwiek global slot
        // (w przeciwnym razie zostawiamy stary stan globalnej dostepnosci nietkniety).
        if (!empty($globalSlots)) {
            // delete tylko globalne (club_id IS NULL) i wstaw nowe
            $db = $availModel->getDb();
            $db->beginTransaction();
            try {
                $db->prepare("DELETE FROM trainer_availability WHERE user_id = ? AND club_id IS NULL")
                   ->execute([$uid]);
                $ins = $db->prepare(
                    "INSERT INTO trainer_availability
                        (user_id, club_id, weekday, time_start, time_end, valid_from, valid_until)
                     VALUES (?, NULL, ?, ?, ?, ?, ?)"
                );
                foreach ($globalSlots as $s) {
                    $ins->execute([
                        $uid,
                        $s['weekday'],
                        $s['time_start'],
                        $s['time_end'],
                        $s['valid_from'],
                        $s['valid_until'],
                    ]);
                }
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollBack();
                throw $e;
            }
        }

        Session::flash('success', 'Dostepnosc zapisana.');
        $this->redirect('club/trainer-schedule');
    }

    public function addLeave(string $userId): void
    {
        $uid    = (int)$userId;
        $clubId = $this->currentClub();
        $this->assertTrainerOfClub($uid, $clubId);
        $trainer = $this->fetchTrainer($uid);

        $this->render('club/trainer_schedule/leave_form', [
            'title'   => 'Dodaj urlop',
            'trainer' => $trainer,
        ]);
    }

    public function storeLeave(string $userId): void
    {
        Csrf::verify();
        $uid    = (int)$userId;
        $clubId = $this->currentClub();
        $this->assertTrainerOfClub($uid, $clubId);

        $dateFrom = trim((string)($_POST['date_from'] ?? ''));
        $dateTo   = trim((string)($_POST['date_to']   ?? ''));
        $type     = in_array($_POST['leave_type'] ?? '', ['vacation','sick','training','other'], true)
                       ? $_POST['leave_type'] : 'vacation';
        $reason   = trim((string)($_POST['reason'] ?? '')) ?: null;

        if ($dateFrom === '' || $dateTo === '' || $dateFrom > $dateTo) {
            Session::flash('error', 'Daty urlopu sa nieprawidlowe.');
            $this->redirect('club/trainer-schedule/' . $uid . '/leaves/add');
        }

        (new TrainerLeaveModel())->insert([
            'user_id'    => $uid,
            'leave_type' => $type,
            'date_from'  => $dateFrom,
            'date_to'    => $dateTo,
            'reason'     => $reason,
            'approved_by' => Auth::id(),
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        Session::flash('success', 'Urlop zapisany.');
        $this->redirect('club/trainer-schedule');
    }

    public function deleteLeave(string $leaveId): void
    {
        Csrf::verify();
        $id     = (int)$leaveId;
        $clubId = $this->currentClub();

        // Sprawdz ze leave nalezy do trenera tego klubu
        $stmt = (new TrainerLeaveModel())->getDb()->prepare(
            "SELECT tl.user_id FROM trainer_leaves tl
             JOIN user_clubs uc ON uc.user_id = tl.user_id
             WHERE tl.id = ? AND uc.club_id = ? LIMIT 1"
        );
        $stmt->execute([$id, $clubId]);
        if (!$stmt->fetchColumn()) {
            Session::flash('error', 'Brak uprawnien lub urlop nie istnieje.');
            $this->redirect('club/trainer-schedule');
        }
        (new TrainerLeaveModel())->delete($id);
        Session::flash('success', 'Urlop usuniety.');
        $this->redirect('club/trainer-schedule');
    }

    /** AJAX: POST /club/trainings/check-conflicts {trainer_id, starts_at, ends_at, training_id?} */
    public function checkConflicts(): void
    {
        // Brak CSRF dla GET, ale POST z fetch + same-origin — wymagamy csrf
        Csrf::verify();
        $uid     = (int)($_POST['trainer_id'] ?? 0);
        $start   = trim((string)($_POST['starts_at'] ?? ''));
        $end     = trim((string)($_POST['ends_at']   ?? ''));
        $exclude = !empty($_POST['training_id']) ? (int)$_POST['training_id'] : null;
        $clubId  = ClubContext::current();

        if ($uid <= 0 || $start === '') {
            $this->json(['ok' => false, 'error' => 'missing params', 'conflicts' => []], 400);
        }
        try {
            $startDt = new DateTimeImmutable(str_replace('T', ' ', $start));
            $endDt   = $end !== ''
                ? new DateTimeImmutable(str_replace('T', ' ', $end))
                : $startDt->modify('+1 hour');
        } catch (\Throwable) {
            $this->json(['ok' => false, 'error' => 'bad datetime', 'conflicts' => []], 400);
        }

        $svc = new TrainerScheduleService();
        $conflicts = $svc->detectConflicts($uid, $startDt, $endDt, $exclude, $clubId);
        $this->json([
            'ok'        => true,
            'conflicts' => $conflicts,
            'count'     => count($conflicts),
        ]);
    }

    // ── helpers ───────────────────────────────────────────────

    /** @return array<int, array<string,mixed>> */
    private function trainersForClub(int $clubId): array
    {
        $stmt = \App\Helpers\Database::pdo()->prepare(
            "SELECT u.id, u.username, u.full_name, u.email, uc.role
             FROM users u
             JOIN user_clubs uc ON uc.user_id = u.id
             WHERE uc.club_id = ? AND uc.role IN ('trener','instruktor') AND uc.is_active = 1
             ORDER BY u.full_name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    private function fetchTrainer(int $userId): array
    {
        $stmt = \App\Helpers\Database::pdo()->prepare(
            "SELECT id, username, full_name, email FROM users WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) {
            Session::flash('error', 'Nie znaleziono trenera.');
            $this->redirect('club/trainer-schedule');
        }
        return $row;
    }

    private function assertTrainerOfClub(int $userId, int $clubId): void
    {
        $stmt = \App\Helpers\Database::pdo()->prepare(
            "SELECT 1 FROM user_clubs
             WHERE user_id = ? AND club_id = ? AND role IN ('trener','instruktor') AND is_active = 1
             LIMIT 1"
        );
        $stmt->execute([$userId, $clubId]);
        if (!$stmt->fetchColumn()) {
            http_response_code(403);
            echo 'Ten uzytkownik nie jest trenerem tego klubu.';
            exit;
        }
    }

    private function normalizeTime(string $t): string
    {
        // 'HH:MM' -> 'HH:MM:00'
        if (preg_match('/^\d{2}:\d{2}$/', $t)) return $t . ':00';
        return $t;
    }

    private function emptyToNull(?string $v): ?string
    {
        $v = $v !== null ? trim($v) : '';
        return $v === '' ? null : $v;
    }
}
