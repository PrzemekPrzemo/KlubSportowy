<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\BookableResourceModel;

/**
 * Admin CRUD dla bookable resources (sala, kort, sprzet itp).
 *
 * Wymaga roli zarzad/admin/trener. Multi-tenant via ClubScopedModel.
 */
class BookingResourcesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireRole(['zarzad', 'admin', 'trener']);
        $this->requireClubContext();
    }

    public function index(): void
    {
        $resources = (new BookableResourceModel())->listAll();
        $this->render('club/resources/index', [
            'title'     => 'Zasoby do rezerwacji',
            'resources' => $resources,
        ]);
    }

    public function create(): void
    {
        $this->render('club/resources/form', [
            'title'    => 'Nowy zasób',
            'resource' => null,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $data = $this->collectFromPost();
        if ($data['name'] === '') {
            Session::flash('error', 'Nazwa zasobu jest wymagana.');
            $this->redirect('club/resources/create');
        }
        (new BookableResourceModel())->insert($data);
        Session::flash('success', 'Zasób dodany.');
        $this->redirect('club/resources');
    }

    public function edit(string $id): void
    {
        $resource = (new BookableResourceModel())->findById((int)$id);
        if (!$resource) {
            Session::flash('error', 'Zasób nie istnieje.');
            $this->redirect('club/resources');
        }
        $this->render('club/resources/form', [
            'title'    => 'Edytuj zasób',
            'resource' => $resource,
        ]);
    }

    public function update(string $id): void
    {
        Csrf::verify();
        $model = new BookableResourceModel();
        $resource = $model->findById((int)$id);
        if (!$resource) {
            Session::flash('error', 'Zasób nie istnieje.');
            $this->redirect('club/resources');
        }
        $data = $this->collectFromPost();
        $model->update((int)$id, $data);
        Session::flash('success', 'Zasób zaktualizowany.');
        $this->redirect('club/resources');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        $model = new BookableResourceModel();
        $resource = $model->findById((int)$id);
        if (!$resource) {
            Session::flash('error', 'Zasób nie istnieje.');
            $this->redirect('club/resources');
        }
        // Soft-delete: is_active = 0 (zachowujemy historie bookings)
        $model->update((int)$id, ['is_active' => 0]);
        Session::flash('success', 'Zasób dezaktywowany.');
        $this->redirect('club/resources');
    }

    private function collectFromPost(): array
    {
        $allowedTypes = ['room','court','equipment','field','pool_lane','other'];
        $type = in_array($_POST['type'] ?? '', $allowedTypes, true) ? $_POST['type'] : 'room';

        $weekdays = $_POST['available_weekdays'] ?? ['1','2','3','4','5','6','7'];
        if (!is_array($weekdays)) $weekdays = ['1','2','3','4','5','6','7'];
        $weekdays = array_values(array_filter(array_map('intval', $weekdays), fn($d) => $d >= 1 && $d <= 7));
        if (empty($weekdays)) $weekdays = [1,2,3,4,5,6,7];

        return [
            'name'                 => trim($_POST['name'] ?? ''),
            'type'                 => $type,
            'capacity'             => $_POST['capacity'] !== '' && isset($_POST['capacity']) ? (int)$_POST['capacity'] : null,
            'description'          => trim($_POST['description'] ?? '') ?: null,
            'location'             => trim($_POST['location'] ?? '') ?: null,
            'color'                => preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['color'] ?? '') ? $_POST['color'] : '#6c757d',
            'icon'                 => trim($_POST['icon'] ?? '') ?: null,
            'sport_key'            => trim($_POST['sport_key'] ?? '') ?: null,
            'booking_unit_minutes' => max(15, min(240, (int)($_POST['booking_unit_minutes'] ?? 60))),
            'min_advance_hours'    => max(0, (int)($_POST['min_advance_hours'] ?? 0)),
            'max_advance_days'     => max(1, (int)($_POST['max_advance_days'] ?? 30)),
            'max_duration_minutes' => $_POST['max_duration_minutes'] !== '' && isset($_POST['max_duration_minutes'])
                                       ? (int)$_POST['max_duration_minutes'] : null,
            'requires_approval'    => !empty($_POST['requires_approval']) ? 1 : 0,
            'is_active'            => isset($_POST['is_active']) ? (int)!!$_POST['is_active'] : 1,
            'available_from'       => !empty($_POST['available_from']) ? $_POST['available_from'] : null,
            'available_until'      => !empty($_POST['available_until']) ? $_POST['available_until'] : null,
            'available_weekdays'   => implode(',', $weekdays),
        ];
    }
}
