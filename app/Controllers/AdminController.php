<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Session;
use App\Models\ClubModel;
use App\Models\SportModel;
use App\Models\SubscriptionModel;

class AdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function dashboard(): void
    {
        $db = \App\Helpers\Database::pdo();
        $metrics = [
            'clubs'        => (int)$db->query("SELECT COUNT(*) FROM clubs")->fetchColumn(),
            'clubs_active' => (int)$db->query("SELECT COUNT(*) FROM clubs WHERE is_active = 1")->fetchColumn(),
            'users'        => (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'members'      => (int)$db->query("SELECT COUNT(*) FROM members WHERE status='aktywny'")->fetchColumn(),
            'sports'       => (int)$db->query("SELECT COUNT(*) FROM sports WHERE is_active = 1")->fetchColumn(),
            'club_sports'  => (int)$db->query("SELECT COUNT(*) FROM club_sports WHERE is_active = 1")->fetchColumn(),
        ];

        $this->render('admin/dashboard', [
            'title'   => 'Panel administratora',
            'metrics' => $metrics,
        ]);
    }

    public function clubs(): void
    {
        $q    = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $data = (new ClubModel())->search($q, $page, 20);

        $this->render('admin/clubs', [
            'title'      => 'Kluby',
            'pagination' => $data,
            'q'          => $q,
        ]);
    }

    public function createClub(): void
    {
        $this->render('admin/club_form', [
            'title' => 'Nowy klub',
            'club'  => null,
        ]);
    }

    public function storeClub(): void
    {
        Csrf::verify();
        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'short_name' => trim($_POST['short_name'] ?? '') ?: null,
            'city'       => trim($_POST['city'] ?? '') ?: null,
            'nip'        => trim($_POST['nip'] ?? '') ?: null,
            'email'      => trim($_POST['email'] ?? '') ?: null,
            'phone'      => trim($_POST['phone'] ?? '') ?: null,
            'address'    => trim($_POST['address'] ?? '') ?: null,
            'is_active'  => 1,
        ];
        if ($data['name'] === '') {
            Session::flash('error', 'Nazwa klubu jest wymagana.');
            $this->redirect('admin/clubs/create');
        }
        $model  = new ClubModel();
        $clubId = $model->insert($data);
        (new \App\Models\ClubCustomizationModel())->ensureExists($clubId);

        // Domyślna subskrypcja trial
        $db   = \App\Helpers\Database::pdo();
        $plan = $db->query("SELECT id FROM subscription_plans WHERE code='trial' LIMIT 1")->fetchColumn();
        if ($plan) {
            $stmt = $db->prepare(
                "INSERT INTO club_subscriptions (club_id, plan_id, valid_until, status, billing_cycle)
                 VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'trial', 'monthly')"
            );
            $stmt->execute([$clubId, (int)$plan]);
        }

        Session::flash('success', 'Klub utworzony.');
        $this->redirect('admin/clubs');
    }

    public function editClub(string $id): void
    {
        $club = (new ClubModel())->findById((int)$id);
        if (!$club) {
            Session::flash('error', 'Nie znaleziono klubu.');
            $this->redirect('admin/clubs');
        }
        $this->render('admin/club_form', [
            'title' => 'Edycja klubu',
            'club'  => $club,
        ]);
    }

    public function updateClub(string $id): void
    {
        Csrf::verify();
        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'short_name' => trim($_POST['short_name'] ?? '') ?: null,
            'city'       => trim($_POST['city'] ?? '') ?: null,
            'nip'        => trim($_POST['nip'] ?? '') ?: null,
            'email'      => trim($_POST['email'] ?? '') ?: null,
            'phone'      => trim($_POST['phone'] ?? '') ?: null,
            'address'    => trim($_POST['address'] ?? '') ?: null,
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
        ];
        (new ClubModel())->update((int)$id, $data);
        Session::flash('success', 'Zapisano zmiany.');
        $this->redirect('admin/clubs');
    }

    public function switchClub(string $id): void
    {
        Csrf::verify();
        $clubId = (int)$id;
        $club = (new ClubModel())->findById($clubId);
        if (!$club) {
            Session::flash('error', 'Nie znaleziono klubu.');
            $this->redirect('admin/clubs');
        }
        \App\Helpers\Auth::setClub($clubId, 'zarzad');
        Session::flash('success', 'Przełączono kontekst na klub: ' . $club['name']);
        $this->redirect('dashboard');
    }

    public function sportsCatalog(): void
    {
        $sports = (new SportModel())->listActive();
        $this->render('admin/sports', [
            'title'  => 'Katalog sportów',
            'sports' => $sports,
        ]);
    }

    public function plans(): void
    {
        $plans = (new SubscriptionModel())->listPlans();
        $this->render('admin/plans', [
            'title' => 'Plany subskrypcyjne',
            'plans' => $plans,
        ]);
    }
}
