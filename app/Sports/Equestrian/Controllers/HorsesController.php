<?php

namespace App\Sports\Equestrian\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Sports\Equestrian\Models\EquestrianHorseModel;
use App\Sports\Equestrian\Models\EquestrianHorseOwnerModel;

class HorsesController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    private const SPORT_CLASSES = [
        'rekreacja'         => 'Rekreacja',
        'sportowa_niska'    => 'Sportowa niska',
        'sportowa_srednia'  => 'Sportowa średnia',
        'sportowa_wysoka'   => 'Sportowa wysoka',
        'pol_profesjonalna' => 'Półprofesjonalna',
        'profesjonalna'     => 'Profesjonalna',
    ];

    public function index(): void
    {
        $horses = (new EquestrianHorseModel())->listForClub();
        $owners = (new EquestrianHorseOwnerModel())->options();
        $this->render('equestrian/horses/index', [
            'title'        => 'Konie — Jeździectwo',
            'horses'       => $horses,
            'disciplines'  => EquestrianHorseModel::$DISCIPLINES,
            'sexOptions'   => EquestrianHorseModel::$SEX,
            'sportClasses' => self::SPORT_CLASSES,
            'owners'       => $owners,
        ]);
    }

    public function store(): void
    {
        Csrf::verify();
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('error', 'Podaj imię konia.');
            $this->redirect('equestrian/horses');
        }

        $sex = array_key_exists($_POST['sex'] ?? '', EquestrianHorseModel::$SEX)
                    ? $_POST['sex'] : 'gelding';

        $sportClass = array_key_exists($_POST['sport_class'] ?? '', self::SPORT_CLASSES)
                    ? $_POST['sport_class'] : 'rekreacja';

        // discipline_focus jest SET — wybor wielu dyscyplin, encode jako CSV
        $disciplines = (array)($_POST['discipline_focus'] ?? []);
        $disciplines = array_filter($disciplines, fn($d) => isset(EquestrianHorseModel::$DISCIPLINES[$d]));
        $disciplineCsv = empty($disciplines) ? null : implode(',', $disciplines);

        (new EquestrianHorseModel())->insert([
            'name'             => $name,
            'breed'            => trim($_POST['breed'] ?? '') ?: null,
            'birth_year'       => !empty($_POST['birth_year']) ? (int)$_POST['birth_year'] : null,
            'color'            => trim($_POST['color'] ?? '') ?: null,
            'sex'              => $sex,
            'passport_no'      => trim($_POST['passport_no']      ?? '') ?: null,
            'pzj_passport_no'  => trim($_POST['pzj_passport_no']  ?? '') ?: null,
            'fei_passport_no'  => trim($_POST['fei_passport_no']  ?? '') ?: null,
            'microchip'        => trim($_POST['microchip']        ?? '') ?: null,
            'height_cm'        => !empty($_POST['height_cm']) ? (int)$_POST['height_cm'] : null,
            'sport_class'      => $sportClass,
            'discipline_focus' => $disciplineCsv,
            'owner_id'         => !empty($_POST['owner_id']) ? (int)$_POST['owner_id'] : null,
            'owner_name'       => trim($_POST['owner_name'] ?? '') ?: null,
            'status'           => 'active',
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
        ]);
        Session::flash('success', 'Koń dodany.');
        $this->redirect('equestrian/horses');
    }

    public function delete(string $id): void
    {
        Csrf::verify();
        (new EquestrianHorseModel())->delete((int)$id);
        Session::flash('success', 'Usunięto.');
        $this->redirect('equestrian/horses');
    }
}
