<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ClubModel;
use App\Models\DashboardWidgetModel;
use App\Models\EventModel;
use App\Models\MedicalExamModel;
use App\Models\MemberModel;
use App\Models\PaymentModel;
use App\Models\SubscriptionModel;
use App\Models\TrainingModel;

class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $clubId = $this->currentClub();

        if ((new ClubModel())->needsOnboarding($clubId) && !Session::get('skip_onboarding')) {
            $this->redirect('onboarding/step1');
        }

        $stats            = (new ClubModel())->stats($clubId);
        $upcoming         = (new EventModel())->upcomingForClub(5);
        $upcomingTrainings = (new TrainingModel())->upcomingForClub(5);
        $expiringMedical  = (new MedicalExamModel())->expiringSoon(60);
        $sub              = (new SubscriptionModel())->findForClub($clubId);

        // X.2 — Activity feed (10 ostatnich zdarzeń klubu)
        $activityFeed = [];
        try {
            $activityFeed = (new \App\Models\ActivityLogModel())->recentForClub($clubId, 10);
        } catch (\Throwable) {}

        // X.3 — Onboarding checklist (znika gdy 100% complete)
        $onboarding = $this->onboardingChecklist($clubId, $stats);

        // Load widget configuration for current user
        $widgetModel  = new DashboardWidgetModel();
        $widgets      = $widgetModel->getForUser((int)Auth::id());

        $this->render('dashboard/index', [
            'title'            => 'Dashboard',
            'stats'            => $stats,
            'upcoming'         => $upcoming,
            'upcomingTrainings'=> $upcomingTrainings,
            'expiringMedical'  => $expiringMedical,
            'subscription'     => $sub,
            'widgets'          => $widgets,
            'activityFeed'     => $activityFeed,
            'onboarding'       => $onboarding,
        ]);
    }

    /**
     * X.3 — Onboarding checklist na dashboard.
     * Zwraca tablicę kroków + completion %. Frontend chowa widget gdy 100%.
     */
    private function onboardingChecklist(int $clubId, array $stats): array
    {
        $db = \App\Helpers\Database::pdo();

        // 1. Sekcja sportowa
        $hasSport = (int)($stats['sports'] ?? 0) > 0;

        // 2. Zaproszony trener (lub instruktor)
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM user_clubs
              WHERE club_id = ? AND role IN ('trener','instruktor') AND is_active = 1"
        );
        $stmt->execute([$clubId]);
        $hasTrainer = (int)$stmt->fetchColumn() > 0;

        // 3. Zawodnik (z importu lub ręcznie)
        $hasMembers = (int)($stats['members'] ?? 0) > 0;

        // 4. Stawka opłat
        $stmt = $db->prepare("SELECT COUNT(*) FROM fee_rates WHERE club_id = ? AND is_active = 1");
        $stmt->execute([$clubId]);
        $hasFeeRate = (int)$stmt->fetchColumn() > 0;

        // 5. Bramka płatności (lub explicit "manual" by admin)
        $hasGateway = false;
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM club_payment_gateways WHERE club_id = ?");
            $stmt->execute([$clubId]);
            $hasGateway = (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable) {} // tabela może nie istnieć w starszych instalacjach

        // 6. Logo klubu
        $hasLogo = false;
        try {
            $stmt = $db->prepare("SELECT logo_path FROM club_customization WHERE club_id = ? AND logo_path IS NOT NULL");
            $stmt->execute([$clubId]);
            $hasLogo = (bool)$stmt->fetchColumn();
        } catch (\Throwable) {}

        $steps = [
            ['key' => 'club',     'label' => 'Klub utworzony',           'done' => true,         'href' => null,                            'icon' => 'bi-building'],
            ['key' => 'sport',    'label' => 'Dodaj sekcję sportową',     'done' => $hasSport,    'href' => url('sports'),                   'icon' => 'bi-trophy'],
            ['key' => 'trainer',  'label' => 'Zaproś trenera lub instruktora', 'done' => $hasTrainer, 'href' => url('users'),               'icon' => 'bi-person-badge'],
            ['key' => 'members',  'label' => 'Dodaj pierwszego zawodnika', 'done' => $hasMembers, 'href' => url('members/create'),           'icon' => 'bi-people'],
            ['key' => 'fee_rate', 'label' => 'Skonfiguruj stawkę opłat',  'done' => $hasFeeRate,  'href' => url('fees/rates'),              'icon' => 'bi-tag'],
            ['key' => 'gateway',  'label' => 'Włącz płatności online',    'done' => $hasGateway,  'href' => url('club/gateways'),           'icon' => 'bi-credit-card'],
            ['key' => 'logo',     'label' => 'Wgraj logo klubu',          'done' => $hasLogo,     'href' => url('club/branding'),           'icon' => 'bi-image'],
        ];

        $doneCount = count(array_filter($steps, fn($s) => $s['done']));
        $total     = count($steps);

        return [
            'steps'      => $steps,
            'done'       => $doneCount,
            'total'      => $total,
            'percent'    => $total > 0 ? (int)round(100 * $doneCount / $total) : 0,
            'is_complete'=> $doneCount === $total,
        ];
    }

    public function saveWidgets(): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        Csrf::verify();

        $keys    = $_POST['widget_key'] ?? [];
        $visible = $_POST['widget_visible'] ?? [];

        if (!is_array($keys)) {
            Session::flash('error', __('flash.error'));
            $this->redirect('dashboard');
        }

        $widgets = [];
        foreach ($keys as $i => $key) {
            $widgets[] = [
                'widget_key' => $key,
                'is_visible' => isset($visible[$key]) ? 1 : 0,
            ];
        }

        $widgetModel = new DashboardWidgetModel();
        $widgetModel->saveOrder((int)Auth::id(), $widgets);

        Session::flash('success', __('flash.saved'));
        $this->redirect('dashboard');
    }
}
