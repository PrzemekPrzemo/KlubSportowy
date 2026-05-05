<?php

namespace App\Sports\Equestrian\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\MemberModel;
use App\Sports\Equestrian\Models\EquestrianRiderModel;

/**
 * CRUD dla riderow (zawodnikow z licencja PZJ). Powiazani 1:1 z members
 * (member_id UNIQUE w schema). Dla zawodnikow rekreacyjnych bez licencji
 * uzywamy bezposrednio members + equestrian_assignments (rider_id NULL).
 */
class RidersController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $model         = new EquestrianRiderModel();
        $riders        = $model->listForClub();
        $expiring      = $model->expiringSoon(30);
        $availableMembers = $this->availableMembers();

        $this->render('equestrian/riders/index', [
            'title'           => 'Zawodnicy — Jeździectwo (PZJ)',
            'riders'          => $riders,
            'expiringSoon'    => $expiring,
            'availableMembers'=> $availableMembers,
            'licenseClasses'  => EquestrianRiderModel::$LICENSE_CLASSES,
            'disciplines'     => EquestrianRiderModel::$DISCIPLINES,
            'statusOptions'   => EquestrianRiderModel::$STATUS,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Wybierz członka klubu, ktoremu nadać licencję jeździecką.');
            $this->redirect('equestrian/riders');
        }

        $licenseClass = array_key_exists($_POST['license_class'] ?? '', EquestrianRiderModel::$LICENSE_CLASSES)
                      ? $_POST['license_class'] : null;

        $disciplineMain = array_key_exists($_POST['discipline_main'] ?? '', EquestrianRiderModel::$DISCIPLINES)
                       ? $_POST['discipline_main'] : null;

        $handedness = in_array($_POST['handedness'] ?? '', ['left','right'], true) ? $_POST['handedness'] : null;

        try {
            (new EquestrianRiderModel())->insert([
                'member_id'           => $memberId,
                'license_no'          => trim($_POST['license_no'] ?? '') ?: null,
                'license_class'       => $licenseClass,
                'license_valid_until' => trim($_POST['license_valid_until'] ?? '') ?: null,
                'discipline_main'     => $disciplineMain,
                'weight_kg'           => !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : null,
                'height_cm'           => !empty($_POST['height_cm']) ? (int)$_POST['height_cm']  : null,
                'handedness'          => $handedness,
                'status'              => 'aktywny',
                'notes'               => trim($_POST['notes'] ?? '') ?: null,
            ]);
            Session::flash('success', 'Zawodnik dodany.');
        } catch (\Throwable $e) {
            // Najczesciej UNIQUE violation na member_id
            Session::flash('error', 'Ten członek juz jest zarejestrowany jako zawodnik PZJ.');
        }
        $this->redirect('equestrian/riders');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        try {
            (new EquestrianRiderModel())->delete((int)$id);
            Session::flash('success', 'Usunięto zawodnika.');
        } catch (\Throwable) {
            Session::flash('error', 'Nie można usunąć — zawodnik ma starty w zawodach.');
        }
        $this->redirect('equestrian/riders');
    }

    /**
     * Zwraca listę członków klubu którzy NIE są jeszcze zarejestrowani
     * jako jeźdźcy. Used dla dropdown'u przy "Dodaj zawodnika".
     */
    private function availableMembers(): array
    {
        $clubId = (int)$this->currentClub();
        $db     = \App\Helpers\Database::pdo();
        $stmt   = $db->prepare(
            "SELECT m.id, m.first_name, m.last_name, m.member_number
             FROM members m
             LEFT JOIN equestrian_riders r ON r.member_id = m.id
             WHERE m.club_id = ? AND m.status = 'aktywny' AND r.id IS NULL
             ORDER BY m.last_name"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
