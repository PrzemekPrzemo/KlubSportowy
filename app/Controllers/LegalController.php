<?php

namespace App\Controllers;

class LegalController extends BaseController
{
    /** GET /terms */
    public function terms(): void
    {
        $this->view->setLayout('public');
        $this->view->render('legal/terms', [
            'title'   => 'Regulamin serwisu',
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }

    /** GET /privacy */
    public function privacy(): void
    {
        $this->view->setLayout('public');
        $this->view->render('legal/privacy', [
            'title'   => 'Polityka prywatnosci',
            'appName' => (require ROOT_PATH . '/config/app.php')['app_name'] ?? 'KlubSportowy',
        ]);
    }
}
