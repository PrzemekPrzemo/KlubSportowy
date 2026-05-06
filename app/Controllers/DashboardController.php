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

        // X.4 — Pulse tiles: aktualne saldo + akcje na dziś
        $pulse = $this->pulseTiles($clubId);

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
            'pulse'            => $pulse,
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

    /**
     * X.4 — Tiles z dynamicznymi danymi: zaległości, wpłaty w m-cu,
     * nowi członkowie 30 dni, frekwencja miesięczna. Tylko niezerowe pokazujemy.
     */
    private function pulseTiles(int $clubId): array
    {
        $db = \App\Helpers\Database::pdo();
        $tiles = [];

        // 1. Saldo zaległości (suma overdue + pending past due_date)
        try {
            $stmt = $db->prepare(
                "SELECT
                    COALESCE(SUM(net_amount - paid_amount), 0) AS amount,
                    COUNT(*) AS cnt
                 FROM payment_dues
                 WHERE club_id = ?
                   AND (status = 'overdue'
                        OR (status IN ('pending','partial') AND due_date < CURDATE()))"
            );
            $stmt->execute([$clubId]);
            $row = $stmt->fetch();
            if (!empty($row) && (float)$row['amount'] > 0) {
                $tiles[] = [
                    'key'    => 'overdue',
                    'icon'   => 'bi-exclamation-triangle-fill',
                    'color'  => 'danger',
                    'label'  => 'Zaległości',
                    'value'  => format_money($row['amount']),
                    'sub'    => (int)$row['cnt'] . ' '
                        . ((int)$row['cnt'] === 1 ? 'należność' : ((int)$row['cnt'] < 5 ? 'należności' : 'należności')),
                    'href'   => url('fees/dues?status=overdue'),
                ];
            }
        } catch (\Throwable) {}

        // 2. Płatności w tym miesiącu
        try {
            $stmt = $db->prepare(
                "SELECT
                    COALESCE(SUM(amount), 0) AS amount,
                    COUNT(*) AS cnt
                 FROM payments
                 WHERE club_id = ?
                   AND payment_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"
            );
            $stmt->execute([$clubId]);
            $row = $stmt->fetch();
            if (!empty($row) && (int)$row['cnt'] > 0) {
                $tiles[] = [
                    'key'    => 'this_month',
                    'icon'   => 'bi-cash-stack',
                    'color'  => 'success',
                    'label'  => 'Wpłaty w ' . [
                        1=>'styczniu',2=>'lutym',3=>'marcu',4=>'kwietniu',5=>'maju',6=>'czerwcu',
                        7=>'lipcu',8=>'sierpniu',9=>'wrześniu',10=>'październiku',11=>'listopadzie',12=>'grudniu',
                    ][(int)date('n')],
                    'value'  => format_money($row['amount']),
                    'sub'    => (int)$row['cnt'] . ' wpłat',
                    'href'   => url('fees'),
                ];
            }
        } catch (\Throwable) {}

        // 3. Nowi członkowie ostatnie 30 dni
        try {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM members
                  WHERE club_id = ?
                    AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    AND status = 'aktywny'"
            );
            $stmt->execute([$clubId]);
            $cnt = (int)$stmt->fetchColumn();
            if ($cnt > 0) {
                $tiles[] = [
                    'key'    => 'new_members',
                    'icon'   => 'bi-person-plus-fill',
                    'color'  => 'info',
                    'label'  => 'Nowi zawodnicy',
                    'value'  => '+' . $cnt,
                    'sub'    => 'ostatnie 30 dni',
                    'href'   => url('members'),
                ];
            }
        } catch (\Throwable) {}

        // 4. Najbliższy trening
        try {
            $stmt = $db->prepare(
                "SELECT t.start_time, s.name AS sport_name, s.color AS sport_color
                 FROM trainings t
                 LEFT JOIN club_sports cs ON cs.id = t.club_sport_id
                 LEFT JOIN sports s       ON s.id = cs.sport_id
                 WHERE t.club_id = ?
                   AND t.start_time >= NOW()
                   AND t.status != 'odwolany'
                 ORDER BY t.start_time ASC
                 LIMIT 1"
            );
            $stmt->execute([$clubId]);
            $row = $stmt->fetch();
            if (!empty($row)) {
                $start = strtotime($row['start_time']);
                $diff  = $start - time();
                $when  = $diff < 86400
                    ? 'za ' . max(1, (int)floor($diff / 3600)) . ' h'
                    : 'za ' . (int)floor($diff / 86400) . ' dni';
                $tiles[] = [
                    'key'    => 'next_training',
                    'icon'   => 'bi-stopwatch-fill',
                    'color'  => 'primary',
                    'label'  => 'Najbliższy trening',
                    'value'  => $when,
                    'sub'    => $row['sport_name'] ?? '—',
                    'href'   => url('trainings'),
                ];
            }
        } catch (\Throwable) {}

        // Q.2.3 — Proaktywna sugestia addona gdy klub bliski limitu (>= 90%)
        try {
            $sub = new \App\Models\SubscriptionModel();
            $memberInfo = $sub->memberLimitInfo($clubId);
            if ($memberInfo['limit'] !== null && $memberInfo['percent'] >= 90 && $memberInfo['remaining'] <= 10) {
                $tiles[] = [
                    'key'    => 'limit_warning_members',
                    'icon'   => 'bi-arrow-up-circle-fill',
                    'color'  => 'warning',
                    'label'  => 'Limit zawodników: ' . $memberInfo['percent'] . '%',
                    'value'  => $memberInfo['used'] . '/' . $memberInfo['limit'],
                    'sub'    => 'Dokup +50 za 49 zł/m-c',
                    'href'   => url('club/subscription/addons'),
                ];
            }
            $sportInfo = $sub->sportLimitInfo($clubId);
            if ($sportInfo['limit'] !== null && $sportInfo['used'] >= $sportInfo['limit']) {
                $tiles[] = [
                    'key'    => 'limit_warning_sports',
                    'icon'   => 'bi-trophy-fill',
                    'color'  => 'warning',
                    'label'  => 'Limit sekcji osiągnięty',
                    'value'  => $sportInfo['used'] . '/' . $sportInfo['limit'],
                    'sub'    => 'Dokup +1 sekcję za 25 zł/m-c',
                    'href'   => url('club/subscription/addons'),
                ];
            }
        } catch (\Throwable) {}

        return $tiles;
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
