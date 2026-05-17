<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Models\PaymentModel;
use App\Models\StudioClassBookingModel;
use App\Models\StudioClassScheduleModel;
use App\Models\StudioMemberPassModel;
use App\Models\StudioPassTypeModel;
use App\Sports\Studio\PassExhaustedException;

/**
 * Portal zawodnika — zajecia studio (yoga / fitness / pilates).
 *
 * URL: /portal/studio/...
 *
 * Akcje:
 *  - mySchedule    Moje nadchodzace klasy
 *  - classCatalog  Pelny tygodniowy harmonogram (wszystkie 3 sporty)
 *  - book          POST: zapisz sie na klase
 *  - cancel        POST: anuluj rezerwacje
 *  - myPasses      Moje karnety
 *  - buyPass       Katalog karnetow + zakup
 */
class PortalStudioController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        MemberAuth::requireLogin();
    }

    private function requireMember(): array
    {
        $mid    = MemberAuth::id();
        $clubId = MemberAuth::clubId();
        if ($mid === null || $clubId === null) {
            Session::flash('error', 'Wybierz klub aby kontynuowac.');
            $this->redirect('portal/club-select');
        }
        return ['member_id' => (int)$mid, 'club_id' => (int)$clubId];
    }

    public function mySchedule(): void
    {
        $ctx = $this->requireMember();
        $bookings = (new StudioClassBookingModel())->upcomingForMember($ctx['member_id'], 30);
        $this->view->setLayout('portal');
        $this->render('portal/studio/my_schedule', [
            'title'    => 'Moje zajecia studio',
            'bookings' => $bookings,
            'dayLabels'=> $this->dayLabels(),
        ]);
    }

    public function classCatalog(): void
    {
        $this->requireMember();
        $model = new StudioClassScheduleModel();
        $catalog = [];
        foreach (['yoga', 'fitness', 'pilates'] as $sport) {
            $catalog[$sport] = $model->weeklyMatrix($sport);
        }
        $this->view->setLayout('portal');
        $this->render('portal/studio/class_catalog', [
            'title'     => 'Klasy studio — katalog',
            'catalog'   => $catalog,
            'dayLabels' => $this->dayLabels(),
            'today'     => date('Y-m-d'),
            'nextDates' => $this->nextDatesForWeek(),
        ]);
    }

    /** Zapisz sie na klase: POST /portal/studio/book */
    public function book(): void
    {
        Csrf::verify();
        $ctx = $this->requireMember();

        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        $date       = trim((string)($_POST['class_date'] ?? ''));
        if ($scheduleId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            Session::flash('error', 'Nieprawidlowe dane rezerwacji.');
            $this->redirect('portal/studio/catalog');
        }

        // Pobierz sport_key z harmonogramu, by dopasowac wlasciwy pass
        $schedModel = new StudioClassScheduleModel();
        $sched = $schedModel->findById($scheduleId);
        if (!$sched) {
            Session::flash('error', 'Klasa nie istnieje.');
            $this->redirect('portal/studio/catalog');
        }

        $passModel = new StudioMemberPassModel();
        $pass = $passModel->activeForMember($ctx['member_id'], $sched['sport_key']);
        if (!$pass) {
            Session::flash('error', 'Brak aktywnego karnetu. Kup karnet, aby zapisac sie na zajecia.');
            $this->redirect('portal/studio/buy-pass');
        }

        try {
            $bookingModel = new StudioClassBookingModel();
            $result = $bookingModel->bookForMember(
                $scheduleId,
                $ctx['member_id'],
                $date,
                (int)$pass['id'],
                true
            );
            if ($result['status'] === 'waitlist') {
                Session::flash('info', 'Klasa pelna — jestes na liscie rezerwowej.');
            } else {
                Session::flash('success', 'Zapisano na zajecia.');
            }
        } catch (PassExhaustedException $e) {
            Session::flash('error', 'Karnet wyczerpany: ' . $e->getMessage());
        } catch (\Throwable $e) {
            Session::flash('error', 'Blad rezerwacji: ' . $e->getMessage());
        }

        $this->redirect('portal/studio/my-schedule');
    }

    /** Anuluj rezerwacje: POST /portal/studio/cancel/:id */
    public function cancel(string $bookingId): void
    {
        Csrf::verify();
        $ctx = $this->requireMember();

        $bookingModel = new StudioClassBookingModel();
        $row = $bookingModel->findById((int)$bookingId);
        if (!$row || (int)$row['member_id'] !== $ctx['member_id']) {
            Session::flash('error', 'Rezerwacja nie znaleziona.');
            $this->redirect('portal/studio/my-schedule');
        }
        $res = $bookingModel->cancelBooking((int)$bookingId);
        if ($res['refunded']) {
            Session::flash('success', 'Anulowano. Wejscie zwrocone na karnet.');
        } else {
            Session::flash('info', 'Anulowano (poza oknem zwrotu — bez refundu wejscia).');
        }
        $this->redirect('portal/studio/my-schedule');
    }

    public function myPasses(): void
    {
        $ctx = $this->requireMember();
        $passes = (new StudioMemberPassModel())->listForMember($ctx['member_id']);
        $this->view->setLayout('portal');
        $this->render('portal/studio/my_passes', [
            'title'  => 'Moje karnety studio',
            'passes' => $passes,
        ]);
    }

    public function buyPass(): void
    {
        $this->requireMember();
        $types = (new StudioPassTypeModel())->listActive(null);
        $this->view->setLayout('portal');
        $this->render('portal/studio/buy_pass', [
            'title' => 'Kup karnet',
            'types' => $types,
        ]);
    }

    /** POST /portal/studio/buy-pass */
    public function buyPassStore(): void
    {
        Csrf::verify();
        $ctx = $this->requireMember();
        $passTypeId = (int)($_POST['pass_type_id'] ?? 0);
        if ($passTypeId <= 0) {
            Session::flash('error', 'Wybierz karnet.');
            $this->redirect('portal/studio/buy-pass');
        }

        // Atomic: insert pass + payment record (jezeli plug-in platnosci aktywny)
        $passModel = new StudioMemberPassModel();
        $db = $passModel->getDb();
        $db->beginTransaction();
        try {
            // Wstepnie: stub bez prawdziwego PaymentGateway integration.
            // Zapis platnosci sluzy do sledzenia revenue + audytu.
            $paymentId = null;
            try {
                $type = (new StudioPassTypeModel())->findById($passTypeId);
                if ($type && (int)$type['price_cents'] > 0) {
                    $payment = new PaymentModel();
                    $paymentId = $payment->insert([
                        'club_id'        => $ctx['club_id'],
                        'member_id'      => $ctx['member_id'],
                        'amount'         => round($type['price_cents'] / 100, 2),
                        'payment_date'   => date('Y-m-d'),
                        'period_year'    => (int)date('Y'),
                        'description'    => 'Karnet studio: ' . $type['name'],
                        'payment_method' => 'online',
                    ]);
                }
            } catch (\Throwable) {
                // Tabela payments moze nie miec wszystkich kolumn — fallback bez payment_id
                $paymentId = null;
            }
            $passModel->purchase($ctx['member_id'], $passTypeId, $paymentId);
            $db->commit();
            Session::flash('success', 'Karnet aktywowany.');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            Session::flash('error', 'Blad zakupu: ' . $e->getMessage());
        }
        $this->redirect('portal/studio/my-passes');
    }

    private function dayLabels(): array
    {
        return [1 => 'Pon', 2 => 'Wt', 3 => 'Śr', 4 => 'Czw', 5 => 'Pt', 6 => 'Sob', 7 => 'Ndz'];
    }

    /** Zwroc daty (Y-m-d) dla najblizszego wystapienia kazdego dnia tygodnia (1..7). */
    private function nextDatesForWeek(): array
    {
        $out = [];
        $today = new \DateTimeImmutable('today');
        $todayDow = (int)$today->format('N'); // 1..7
        for ($dow = 1; $dow <= 7; $dow++) {
            $diff = ($dow - $todayDow + 7) % 7;
            $out[$dow] = $today->modify("+{$diff} days")->format('Y-m-d');
        }
        return $out;
    }
}
