<?php

namespace App\Sports\Climbing\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Sports\Climbing\Models\ClimbingRouteModel;

class RoutesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $includeRetired = !empty($_GET['retired']);
        $model  = new ClimbingRouteModel();
        $routes = $model->listForClub($includeRetired);

        $this->render('climbing/routes/index', [
            'title'          => 'Drogi klubowe — Wspinaczka',
            'routes'         => $routes,
            'types'          => ClimbingRouteModel::$TYPES,
            'frenchGrades'   => ClimbingRouteModel::$FRENCH_GRADES,
            'vGrades'        => ClimbingRouteModel::$V_GRADES,
            'includeRetired' => $includeRetired,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('error', 'Podaj nazwę drogi.');
            $this->redirect('climbing/routes');
        }
        $type = array_key_exists($_POST['type'] ?? '', ClimbingRouteModel::$TYPES)
            ? $_POST['type'] : 'prowadzenie';

        (new ClimbingRouteModel())->insert([
            'name'         => $name,
            'type'         => $type,
            'grade_french' => trim($_POST['grade_french'] ?? '') ?: null,
            'grade_v'      => trim($_POST['grade_v'] ?? '') ?: null,
            'wall_name'    => trim($_POST['wall_name'] ?? '') ?: null,
            'color'        => trim($_POST['color'] ?? '') ?: null,
            'set_by'       => trim($_POST['set_by'] ?? '') ?: null,
            'set_date'     => trim($_POST['set_date'] ?? '') ?: date('Y-m-d'),
            'notes'        => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Droga dodana.');
        $this->redirect('climbing/routes');
    }

    public function retire(string $id): void
    {
        Csrf::verify();
        Database::pdo()->prepare("UPDATE climbing_routes SET retired = 1 WHERE id = ? AND club_id = ?")
            ->execute([(int)$id, $this->currentClub()]);
        Session::flash('success', 'Droga została zdjęta (retired).');
        $this->redirect('climbing/routes');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new ClimbingRouteModel())->delete((int)$id);
        Session::flash('success', 'Droga usunięta.');
        $this->redirect('climbing/routes');
    }
}
