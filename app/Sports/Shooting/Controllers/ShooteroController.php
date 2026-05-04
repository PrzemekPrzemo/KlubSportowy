<?php

namespace App\Sports\Shooting\Controllers;

use App\Controllers\BaseController;

/**
 * Strzelectwo: zaawansowane funkcje (zawody, scoringi, kategorie ISSF,
 * harmonogramy strzelnic) realizujemy przez zewnętrzny system shootero.pl.
 * Ten kontroler eksponuje stronę-szortę z linkami i opisem integracji.
 */
class ShooteroController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $this->render('shooting/shootero/index', [
            'title' => 'Integracja: shootero.pl',
        ]);
    }
}
