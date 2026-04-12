<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\DemoSeeder;
use App\Helpers\Session;
use App\Models\ClubModel;
use App\Models\DemoTokenModel;
use App\Models\UserClubModel;
use App\Models\UserModel;

class DemoController extends BaseController
{
    /**
     * List active demo tokens (super admin only).
     */
    public function index(): void
    {
        $this->requireSuperAdmin();

        $model  = new DemoTokenModel();
        $tokens = $model->listActive();

        $this->render('admin/demos', [
            'title'  => 'Demo — tokeny',
            'tokens' => $tokens,
        ]);
    }

    /**
     * Create a new demo club with seeded data + token (super admin only).
     */
    public function create(): void
    {
        $this->requireSuperAdmin();
        Csrf::verify();

        $db = \App\Helpers\Database::pdo();
        $db->beginTransaction();

        try {
            // Create demo club
            $clubModel = new ClubModel();
            $clubName  = 'Demo Club ' . date('Y-m-d H:i');
            $clubId    = $clubModel->insert([
                'name'  => $clubName,
                'city'  => 'Demo',
                'email' => 'demo-' . time() . '@demo.test',
            ]);

            // Create a demo user for auto-login
            $userModel = new UserModel();
            $demoUsername = 'demo_' . $clubId;
            $existing = $userModel->findByUsername($demoUsername);
            if (!$existing) {
                $demoUserId = $userModel->create([
                    'username'  => $demoUsername,
                    'email'     => "demo_{$clubId}@demo.test",
                    'full_name' => 'Uzytkownik Demo',
                    'password'  => bin2hex(random_bytes(16)),
                ]);
            } else {
                $demoUserId = (int)$existing['id'];
            }

            // Grant role in club
            (new UserClubModel())->grantRole($demoUserId, $clubId, 'zarzad');

            // Seed demo data
            DemoSeeder::seed($clubId);

            // Generate token
            $tokenModel = new DemoTokenModel();
            $token = $tokenModel->createToken($clubId, 7, Auth::id());

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Blad tworzenia demo: ' . $e->getMessage());
            $this->redirect('admin/demos');
        }

        Session::flash('success', "Demo utworzone. Token: {$token}");
        $this->redirect('admin/demos');
    }

    /**
     * Public endpoint: auto-login via demo token.
     * GET /demo/:token
     */
    public function loginViaToken(string $token): void
    {
        $model = new DemoTokenModel();
        $demo  = $model->findByToken($token);

        if (!$demo) {
            Session::flash('error', 'Link demo jest nieprawidlowy lub wygasl.');
            $this->redirect('auth/login');
        }

        $clubId = (int)$demo['club_id'];

        // Find demo user for this club
        $db = \App\Helpers\Database::pdo();
        $stmt = $db->prepare(
            "SELECT u.* FROM users u
             JOIN user_clubs uc ON uc.user_id = u.id
             WHERE uc.club_id = ? AND u.username LIKE 'demo_%'
             LIMIT 1"
        );
        $stmt->execute([$clubId]);
        $user = $stmt->fetch();

        if (!$user) {
            Session::flash('error', 'Nie znaleziono uzytkownika demo.');
            $this->redirect('auth/login');
        }

        // Auto-login
        Auth::login($user);
        Auth::setClub($clubId, 'zarzad');

        $this->redirect('dashboard');
    }

    /**
     * Remove expired demo tokens + their clubs (super admin only).
     */
    public function cleanup(): void
    {
        $this->requireSuperAdmin();
        Csrf::verify();

        $model   = new DemoTokenModel();
        $deleted = $model->cleanup();

        Session::flash('success', "Wyczyszczono {$deleted} wygaslych tokenow demo.");
        $this->redirect('admin/demos');
    }
}
