<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\MemberAuth;
use App\Helpers\Session;
use App\Models\BookableResourceModel;
use App\Models\BookingModel;

/**
 * Portal zawodnika — moje rezerwacje + self-booking.
 *
 * Auth: MemberAuth (oddzielne od Auth admina). Klub czerpiemy z MemberAuth::clubId().
 * Member moze anulowac wlasne; nie moze cudzych.
 */
class PortalBookingsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        MemberAuth::requireLogin();
    }

    /**
     * GET /portal/bookings — moje rezerwacje.
     */
    public function index(): void
    {
        $memberId = (int)MemberAuth::id();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pagination = (new BookingModel())->withoutScope()->listForMember($memberId, $page, 20);
        $resources  = (new BookableResourceModel())->withoutScope()->listActive();

        $this->view->setLayout('portal');
        $this->view->render('portal/bookings/index', [
            'title'      => 'Moje rezerwacje',
            'pagination' => $pagination,
            'resources'  => $resources,
            'appName'    => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /**
     * GET /portal/bookings/new?resource_id=X — formularz self-booking.
     */
    public function create(): void
    {
        $resources = (new BookableResourceModel())->withoutScope()->listActive();
        if (empty($resources)) {
            Session::flash('warning', 'Brak dostępnych zasobów do rezerwacji.');
            $this->redirect('portal/bookings');
        }
        $resourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : (int)$resources[0]['id'];

        $this->view->setLayout('portal');
        $this->view->render('portal/bookings/new', [
            'title'      => 'Nowa rezerwacja',
            'resources'  => $resources,
            'resourceId' => $resourceId,
            'appName'    => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /**
     * POST /portal/bookings/store.
     */
    public function store(): void
    {
        Csrf::verify();
        $memberId   = (int)MemberAuth::id();
        $resourceId = (int)($_POST['resource_id'] ?? 0);
        $start      = trim($_POST['start_at'] ?? '');
        $end        = trim($_POST['end_at']   ?? '');
        $title      = trim($_POST['title']    ?? '');

        if ($resourceId === 0 || $start === '' || $end === '' || $title === '') {
            Session::flash('error', 'Uzupełnij wszystkie pola.');
            $this->redirect('portal/bookings/new?resource_id=' . $resourceId);
        }
        $start = str_replace('T', ' ', $start);
        $end   = str_replace('T', ' ', $end);

        $resource = (new BookableResourceModel())->withoutScope()->findById($resourceId);
        if (!$resource) {
            Session::flash('error', 'Zasób nie istnieje.');
            $this->redirect('portal/bookings');
        }
        // Zasob musi byc w tym samym klubie co member.
        $clubId = MemberAuth::clubId();
        if ($clubId !== null && (int)$resource['club_id'] !== (int)$clubId) {
            Session::flash('error', 'Brak uprawnień do tego zasobu.');
            $this->redirect('portal/bookings');
        }

        $model = new BookingModel();
        if (!$model->withoutScope()->isAvailable($resourceId, $start, $end)) {
            Session::flash('error', 'Termin zajęty.');
            $this->redirect('portal/bookings/new?resource_id=' . $resourceId);
        }

        $status = !empty($resource['requires_approval']) ? 'pending' : 'confirmed';
        $bookingId = (new BookingModel())->withoutScope()->insert([
            'club_id'     => (int)$resource['club_id'],
            'resource_id' => $resourceId,
            'member_id'   => $memberId,
            'title'       => $title,
            'description' => trim($_POST['description'] ?? '') ?: null,
            'start_at'    => $start,
            'end_at'      => $end,
            'status'      => $status,
        ]);

        Session::flash('success', 'Rezerwacja utworzona' . ($status === 'pending' ? ' — czeka na akceptację.' : '.'));
        $this->redirect('portal/bookings');
    }

    /**
     * POST /portal/bookings/:id/cancel.
     */
    public function cancel(string $id): void
    {
        Csrf::verify();
        $memberId = (int)MemberAuth::id();
        $model = new BookingModel();
        $booking = $model->withoutScope()->findById((int)$id);
        if (!$booking || (int)$booking['member_id'] !== $memberId) {
            Session::flash('error', 'Nie możesz anulować tej rezerwacji.');
            $this->redirect('portal/bookings');
        }
        $model->withoutScope()->update((int)$id, [
            'status'       => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Rezerwacja anulowana.');
        $this->redirect('portal/bookings');
    }
}
