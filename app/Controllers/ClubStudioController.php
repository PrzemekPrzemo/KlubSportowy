<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Models\StudioClassBookingModel;
use App\Models\StudioClassScheduleModel;
use App\Models\StudioMemberPassModel;
use App\Models\StudioPassTypeModel;

/**
 * Admin / instructor controller — zarzadzanie klasami i karnetami studio.
 *
 * URL: /club/studio/{sport}/...  (sport ∈ yoga, fitness, pilates)
 *
 * Akcje:
 *  - schedules            CRUD klas
 *  - passTypes            CRUD typow karnetow
 *  - roster($sched,$date) Lista zapisanych + check-in
 *  - passesReport         Raport sprzedazy / aktywnosci karnetow
 */
class ClubStudioController extends BaseController
{
    private const ALLOWED_SPORTS = ['yoga', 'fitness', 'pilates'];

    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    private function guardSport(string $sport): string
    {
        $sport = strtolower(trim($sport));
        if (!in_array($sport, self::ALLOWED_SPORTS, true)) {
            http_response_code(404);
            echo 'Nieznany sport studio.';
            exit;
        }
        return $sport;
    }

    private function sportName(string $sport): string
    {
        return ['yoga' => 'Joga', 'fitness' => 'Fitness', 'pilates' => 'Pilates'][$sport] ?? ucfirst($sport);
    }

    // ─────────────────────────────────────────────────────────
    // Schedules CRUD
    // ─────────────────────────────────────────────────────────

    public function schedules(string $sport): void
    {
        $sport = $this->guardSport($sport);
        $model = new StudioClassScheduleModel();
        $this->render('club/studio/schedules/index', [
            'title'     => 'Klasy ' . $this->sportName($sport),
            'sport'     => $sport,
            'sportName' => $this->sportName($sport),
            'schedules' => $model->listForSport($sport, false),
            'dayLabels' => $this->dayLabels(),
        ]);
    }

    public function scheduleForm(string $sport, ?string $id = null): void
    {
        $sport = $this->guardSport($sport);
        $row   = null;
        if ($id !== null) {
            $row = (new StudioClassScheduleModel())->findById((int)$id);
            if (!$row || $row['sport_key'] !== $sport) {
                Session::flash('error', 'Klasa nie znaleziona.');
                $this->redirect('club/studio/' . $sport . '/schedules');
            }
        }
        $this->render('club/studio/schedules/form', [
            'title'        => $row ? 'Edytuj klasę' : 'Nowa klasa',
            'sport'        => $sport,
            'sportName'    => $this->sportName($sport),
            'row'          => $row,
            'difficulties' => StudioClassScheduleModel::DIFFICULTIES,
            'dayLabels'    => $this->dayLabels(),
        ]);
    }

    public function scheduleStore(string $sport): void
    {
        Csrf::verify();
        $sport = $this->guardSport($sport);
        $data = $this->scheduleInput($sport);
        (new StudioClassScheduleModel())->insert($data);
        Session::flash('success', 'Klasa dodana.');
        $this->redirect('club/studio/' . $sport . '/schedules');
    }

    public function scheduleUpdate(string $sport, string $id): void
    {
        Csrf::verify();
        $sport = $this->guardSport($sport);
        $model = new StudioClassScheduleModel();
        $row   = $model->findById((int)$id);
        if (!$row || $row['sport_key'] !== $sport) {
            Session::flash('error', 'Klasa nie znaleziona.');
            $this->redirect('club/studio/' . $sport . '/schedules');
        }
        $model->update((int)$id, $this->scheduleInput($sport));
        Session::flash('success', 'Klasa zaktualizowana.');
        $this->redirect('club/studio/' . $sport . '/schedules');
    }

    public function scheduleDelete(string $sport, string $id): void
    {
        Csrf::verify();
        $sport = $this->guardSport($sport);
        $model = new StudioClassScheduleModel();
        $row   = $model->findById((int)$id);
        if ($row && $row['sport_key'] === $sport) {
            $model->delete((int)$id);
            Session::flash('success', 'Klasa usunięta.');
        }
        $this->redirect('club/studio/' . $sport . '/schedules');
    }

    private function scheduleInput(string $sport): array
    {
        $diff = in_array($_POST['difficulty'] ?? 'open', StudioClassScheduleModel::DIFFICULTIES, true)
              ? $_POST['difficulty'] : 'open';
        $day  = max(1, min(7, (int)($_POST['day_of_week'] ?? 1)));
        return [
            'sport_key'          => $sport,
            'name'               => trim((string)($_POST['name'] ?? '')),
            'instructor_user_id' => !empty($_POST['instructor_user_id']) ? (int)$_POST['instructor_user_id'] : null,
            'description'        => trim((string)($_POST['description'] ?? '')) ?: null,
            'difficulty'         => $diff,
            'day_of_week'        => $day,
            'time_start'         => trim((string)($_POST['time_start'] ?? '18:00')) ?: '18:00',
            'duration_min'       => max(15, (int)($_POST['duration_min'] ?? 60)),
            'max_capacity'       => max(1, (int)($_POST['max_capacity'] ?? 15)),
            'room'               => trim((string)($_POST['room'] ?? '')) ?: null,
            'active'             => isset($_POST['active']) ? 1 : 0,
            'recurring'          => 1,
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Pass Types CRUD
    // ─────────────────────────────────────────────────────────

    public function passTypes(string $sport): void
    {
        $sport = $this->guardSport($sport);
        $this->render('club/studio/pass_types/index', [
            'title'     => 'Karnety ' . $this->sportName($sport),
            'sport'     => $sport,
            'sportName' => $this->sportName($sport),
            'types'     => (new StudioPassTypeModel())->listAll($sport),
        ]);
    }

    public function passTypeForm(string $sport, ?string $id = null): void
    {
        $sport = $this->guardSport($sport);
        $row = null;
        if ($id !== null) {
            $row = (new StudioPassTypeModel())->findById((int)$id);
            if (!$row) {
                Session::flash('error', 'Typ karnetu nie znaleziony.');
                $this->redirect('club/studio/' . $sport . '/pass-types');
            }
        }
        $this->render('club/studio/pass_types/form', [
            'title'     => $row ? 'Edytuj karnet' : 'Nowy karnet',
            'sport'     => $sport,
            'sportName' => $this->sportName($sport),
            'row'       => $row,
            'types'     => StudioPassTypeModel::TYPES,
        ]);
    }

    public function passTypeStore(string $sport): void
    {
        Csrf::verify();
        $sport = $this->guardSport($sport);
        $data  = $this->passTypeInput($sport);
        try {
            (new StudioPassTypeModel())->insert($data);
            Session::flash('success', 'Karnet dodany.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Blad zapisu (czy kod karnetu jest unikalny?): ' . $e->getMessage());
        }
        $this->redirect('club/studio/' . $sport . '/pass-types');
    }

    public function passTypeUpdate(string $sport, string $id): void
    {
        Csrf::verify();
        $sport = $this->guardSport($sport);
        $model = new StudioPassTypeModel();
        $row = $model->findById((int)$id);
        if (!$row) {
            Session::flash('error', 'Karnet nie znaleziony.');
            $this->redirect('club/studio/' . $sport . '/pass-types');
        }
        $model->update((int)$id, $this->passTypeInput($sport));
        Session::flash('success', 'Karnet zaktualizowany.');
        $this->redirect('club/studio/' . $sport . '/pass-types');
    }

    public function passTypeDelete(string $sport, string $id): void
    {
        Csrf::verify();
        $sport = $this->guardSport($sport);
        $model = new StudioPassTypeModel();
        $row = $model->findById((int)$id);
        if ($row) {
            try {
                $model->delete((int)$id);
                Session::flash('success', 'Karnet usunięty.');
            } catch (\Throwable) {
                // ON DELETE RESTRICT — istnieja zakupione karnety tego typu
                $model->update((int)$id, ['active' => 0]);
                Session::flash('warning', 'Typ karnetu w użyciu — został oznaczony jako nieaktywny.');
            }
        }
        $this->redirect('club/studio/' . $sport . '/pass-types');
    }

    private function passTypeInput(string $sport): array
    {
        $type = in_array($_POST['type'] ?? '', StudioPassTypeModel::TYPES, true) ? $_POST['type'] : 'multi_class';
        $classes = $type === 'unlimited_period' ? null : max(1, (int)($_POST['classes_count'] ?? 1));
        $priceCents = max(0, (int)round(((float)($_POST['price'] ?? 0)) * 100));
        return [
            'sport_key'     => $sport,
            'code'          => trim((string)($_POST['code'] ?? '')),
            'name'          => trim((string)($_POST['name'] ?? '')),
            'type'          => $type,
            'classes_count' => $classes,
            'validity_days' => max(1, (int)($_POST['validity_days'] ?? 30)),
            'price_cents'   => $priceCents,
            'currency'      => 'PLN',
            'active'        => isset($_POST['active']) ? 1 : 0,
            'sort_order'    => (int)($_POST['sort_order'] ?? 0),
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Roster + check-in
    // ─────────────────────────────────────────────────────────

    public function roster(string $sport, string $scheduleId, ?string $date = null): void
    {
        $sport = $this->guardSport($sport);
        $date  = $date ?: date('Y-m-d');
        $schedModel = new StudioClassScheduleModel();
        $sched = $schedModel->findById((int)$scheduleId);
        if (!$sched || $sched['sport_key'] !== $sport) {
            Session::flash('error', 'Klasa nie znaleziona.');
            $this->redirect('club/studio/' . $sport . '/schedules');
        }

        $bookingModel = new StudioClassBookingModel();
        $this->render('club/studio/roster', [
            'title'      => 'Lista — ' . $sched['name'] . ' / ' . $date,
            'sport'      => $sport,
            'sportName'  => $this->sportName($sport),
            'schedule'   => $sched,
            'date'       => $date,
            'bookings'   => $bookingModel->roster((int)$scheduleId, $date),
            'bookedCount'=> $schedModel->bookedCount((int)$scheduleId, $date),
        ]);
    }

    public function rosterAttend(string $sport, string $bookingId): void
    {
        Csrf::verify();
        $sport = $this->guardSport($sport);
        $model = new StudioClassBookingModel();
        $model->markAttended((int)$bookingId);

        // Wroc na ta sama liste
        $b = $model->findById((int)$bookingId);
        $back = $b
            ? ('club/studio/' . $sport . '/roster/' . (int)$b['schedule_id'] . '/' . $b['class_date'])
            : ('club/studio/' . $sport . '/schedules');
        Session::flash('success', 'Obecność zapisana.');
        $this->redirect($back);
    }

    public function rosterNoShow(string $sport, string $bookingId): void
    {
        Csrf::verify();
        $sport = $this->guardSport($sport);
        $model = new StudioClassBookingModel();
        $model->markNoShow((int)$bookingId);
        $b = $model->findById((int)$bookingId);
        $back = $b
            ? ('club/studio/' . $sport . '/roster/' . (int)$b['schedule_id'] . '/' . $b['class_date'])
            : ('club/studio/' . $sport . '/schedules');
        Session::flash('info', 'Oznaczono jako nieobecny.');
        $this->redirect($back);
    }

    // ─────────────────────────────────────────────────────────
    // Pass report
    // ─────────────────────────────────────────────────────────

    public function passesReport(string $sport): void
    {
        $sport = $this->guardSport($sport);
        $passModel = new StudioMemberPassModel();
        $stats = $passModel->stats($sport);
        $this->render('club/studio/passes_report', [
            'title'     => 'Raport karnetów — ' . $this->sportName($sport),
            'sport'     => $sport,
            'sportName' => $this->sportName($sport),
            'stats'     => $stats,
        ]);
    }

    private function dayLabels(): array
    {
        return [1 => 'Pon', 2 => 'Wt', 3 => 'Śr', 4 => 'Czw', 5 => 'Pt', 6 => 'Sob', 7 => 'Ndz'];
    }
}
