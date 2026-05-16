<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\NotificationDispatcher;
use App\Helpers\Scheduling\TrainerScheduleService;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Models\SportModel;
use App\Models\TrainingAttendeeModel;
use App\Models\TrainingModel;

class TrainingsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $from  = $_GET['from'] ?? null;
        $csId  = isset($_GET['sport']) ? (int)$_GET['sport'] : null;
        $pagination = (new TrainingModel())->listForClub($csId, $from, $page, 25);
        $sports = (new SportModel())->listForClub($this->currentClub());

        $this->render('trainings/index', [
            'title'      => 'Treningi',
            'pagination' => $pagination,
            'sports'     => $sports,
            'sportFilter'=> $csId,
        ]);
    }

    public function create(): void
    {
        $sports = (new SportModel())->listForClub($this->currentClub());
        $this->render('trainings/form', [
            'title'    => 'Nowy trening',
            'training' => null,
            'sports'   => $sports,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;
        $data['created_by'] = Auth::id();

        // Conflict detection — warning, nie blokuje (admin moze nadpisac).
        $force = !empty($_POST['force_save']);
        $conflicts = $this->scanForConflicts($data, null);
        if (!empty($conflicts) && !$force) {
            Session::flash('warning', $this->formatConflictsForFlash($conflicts));
            Session::set('pending_training_data', $data);
            $this->redirect('trainings/create');
        }

        $id = (new TrainingModel())->insert($data);

        // Persistuj konflikty (audit) jezeli byly ignorowane przy zapisie
        if (!empty($conflicts) && !empty($data['instructor_id'])) {
            (new TrainerScheduleService())->persistConflicts(
                (int)$data['instructor_id'],
                (int)ClubContext::current(),
                $id,
                $conflicts
            );
        }

        // Notify club members about future trainings
        if (!empty($data['start_time']) && strtotime($data['start_time']) > time()) {
            $clubId = ClubContext::current();
            if ($clubId) {
                NotificationDispatcher::notifyClubMembers($clubId, 'new_training', [
                    'training_name' => $data['name'],
                    'training_date' => $data['start_time'],
                    'training_location' => $data['location'] ?? '',
                ]);
            }
        }

        Session::flash('success', 'Trening dodany.');
        $this->redirect('trainings/' . $id);
    }

    public function show(string $id): void
    {
        $training = (new TrainingModel())->withAttendees((int)$id);
        if (!$training) {
            Session::flash('error', 'Nie znaleziono treningu.');
            $this->redirect('trainings');
        }
        $members = (new MemberModel())->search('', 'aktywny', null, 1, 500)['data'] ?? [];
        $this->render('trainings/show', [
            'title'    => $training['name'],
            'training' => $training,
            'members'  => $members,
        ]);
    }

    public function edit(string $id): void
    {
        $training = (new TrainingModel())->findById((int)$id);
        if (!$training) {
            Session::flash('error', 'Nie znaleziono.');
            $this->redirect('trainings');
        }
        $sports = (new SportModel())->listForClub($this->currentClub());
        $this->render('trainings/form', [
            'title'    => 'Edycja treningu',
            'training' => $training,
            'sports'   => $sports,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $data = $this->parsePost();
        if ($data === null) return;

        $force = !empty($_POST['force_save']);
        $conflicts = $this->scanForConflicts($data, (int)$id);
        if (!empty($conflicts) && !$force) {
            Session::flash('warning', $this->formatConflictsForFlash($conflicts));
            Session::set('pending_training_data', $data);
            $this->redirect('trainings/' . $id . '/edit');
        }

        (new TrainingModel())->update((int)$id, $data);

        if (!empty($conflicts) && !empty($data['instructor_id'])) {
            (new TrainerScheduleService())->persistConflicts(
                (int)$data['instructor_id'],
                (int)ClubContext::current(),
                (int)$id,
                $conflicts
            );
        }

        Session::flash('success', 'Zapisano zmiany.');
        $this->redirect('trainings/' . $id);
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new TrainingModel())->delete((int)$id);
        Session::flash('success', 'Trening usunięty.');
        $this->redirect('trainings');
    }

    public function addAttendee(string $id): void
    {
        Csrf::verify();
        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId > 0) {
            (new TrainingAttendeeModel())->register((int)$id, $memberId);
            Session::flash('success', 'Zawodnik zapisany.');
        }
        $this->redirect('trainings/' . $id);
    }

    public function removeAttendee(string $id, string $attendeeId): void
    {
        Csrf::verify();
        $db = \App\Helpers\Database::pdo();
        $stmt = $db->prepare("DELETE FROM training_attendees WHERE id = ? AND training_id = ?");
        $stmt->execute([(int)$attendeeId, (int)$id]);
        Session::flash('success', 'Usunięto wpis obecności.');
        $this->redirect('trainings/' . $id);
    }

    public function markAttendance(string $id): void
    {
        Csrf::verify();
        $statuses = $_POST['status'] ?? [];
        $model = new TrainingAttendeeModel();
        $touchedMembers = [];
        foreach ($statuses as $attendeeId => $status) {
            $model->setStatus((int)$attendeeId, (string)$status);
            // Zebierz member_id dla pozniejszej ewaluacji achievements.
            try {
                $db = \App\Helpers\Database::pdo();
                $stmt = $db->prepare("SELECT member_id FROM training_attendees WHERE id = ? LIMIT 1");
                $stmt->execute([(int)$attendeeId]);
                $mid = (int)($stmt->fetchColumn() ?: 0);
                if ($mid > 0) $touchedMembers[$mid] = true;
            } catch (\Throwable) {
                // ignore
            }
        }

        // Trigger achievements (attendance category) dla zmienionych czlonkow.
        try {
            if (class_exists(\App\Helpers\Achievements\AchievementEvaluator::class)) {
                foreach (array_keys($touchedMembers) as $mid) {
                    \App\Helpers\Achievements\AchievementEvaluator::evaluateForMember((int)$mid, 'attendance');
                }
            }
        } catch (\Throwable $e) {
            error_log('Achievements trigger after markAttendance failed: ' . $e->getMessage());
        }

        Session::flash('success', 'Obecność zapisana.');
        $this->redirect('trainings/' . $id);
    }

    private function parsePost(): ?array
    {
        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? '') ?: null,
            'location'    => trim($_POST['location'] ?? '') ?: null,
            'start_time'  => trim($_POST['start_time'] ?? ''),
            'end_time'    => trim($_POST['end_time'] ?? '') ?: null,
            'max_participants' => !empty($_POST['max_participants']) ? (int)$_POST['max_participants'] : null,
            'instructor_id'    => !empty($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : null,
            'sport_id'         => !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : null,
            'club_sport_id'    => !empty($_POST['club_sport_id']) ? (int)$_POST['club_sport_id'] : null,
            'status'      => in_array($_POST['status'] ?? '', ['zaplanowany','w_trakcie','zakonczony','odwolany'], true)
                               ? $_POST['status'] : 'zaplanowany',
        ];
        if ($data['name'] === '' || $data['start_time'] === '') {
            Session::flash('error', 'Nazwa i data rozpoczęcia są wymagane.');
            $this->redirect('trainings/create');
            return null;
        }
        // replace 'T' separator from datetime-local
        $data['start_time'] = str_replace('T', ' ', $data['start_time']);
        if ($data['end_time']) $data['end_time'] = str_replace('T', ' ', $data['end_time']);
        return $data;
    }

    /**
     * Skanuje przyszle konflikty dla zadanych danych treningu.
     * Pomija jezeli brak instructor_id albo treningu w przeszlosci.
     *
     * @return array<int, array<string,mixed>>
     */
    private function scanForConflicts(array $data, ?int $excludeTrainingId): array
    {
        if (empty($data['instructor_id'])) return [];
        if (empty($data['start_time'])) return [];
        try {
            $start = new \DateTimeImmutable((string)$data['start_time']);
            $end   = !empty($data['end_time'])
                ? new \DateTimeImmutable((string)$data['end_time'])
                : $start->modify('+1 hour');
        } catch (\Throwable) {
            return [];
        }
        $clubId = ClubContext::current();
        return (new TrainerScheduleService())->detectConflicts(
            (int)$data['instructor_id'],
            $start,
            $end,
            $excludeTrainingId,
            $clubId ? (int)$clubId : null
        );
    }

    private function formatConflictsForFlash(array $conflicts): string
    {
        $count = count($conflicts);
        $lines = array_map(static fn(array $c): string => '• ' . ($c['details'] ?? $c['type']), array_slice($conflicts, 0, 5));
        return sprintf(
            'Wykryto %d konflikt(ow) terminu trenera: <br>%s<br>'
            . 'Zaznacz "Zapisz mimo to" w formularzu, aby kontynuowac.',
            $count,
            implode('<br>', $lines)
        );
    }
}
