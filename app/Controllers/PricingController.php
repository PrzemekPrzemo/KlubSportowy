<?php

namespace App\Controllers;

use App\Helpers\Database;

/**
 * Q.1 — Publiczna strona cennika.
 *
 * Pobiera plany z DB i renderuje stronę porównawczą. Bez logowania.
 * URL: /cennik (PL) — alias do /pricing.
 */
class PricingController extends BaseController
{
    public function index(): void
    {
        $db = Database::pdo();
        $stmt = $db->query(
            "SELECT * FROM subscription_plans
              WHERE is_active = 1
              ORDER BY sort_order ASC, id ASC"
        );
        $plans = $stmt->fetchAll();

        // Decode features JSON do array dla widoku
        foreach ($plans as &$p) {
            $p['features_decoded'] = !empty($p['features']) ? (json_decode($p['features'], true) ?: []) : [];
        }
        unset($p);

        $this->view->setLayout('public');
        $this->view->render('pricing/index', [
            'title'   => 'Cennik — ClubDesk',
            'plans'   => $plans,
            'appName' => 'ClubDesk',
        ]);
    }
}
