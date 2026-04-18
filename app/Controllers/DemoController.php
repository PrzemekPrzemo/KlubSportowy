<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\DemoSeeder;
use App\Helpers\Session;
use App\Models\ClubModel;
use App\Models\DemoTokenModel;
use App\Models\SportModel;
use App\Models\UserClubModel;
use App\Models\UserModel;

class DemoController extends BaseController
{
    public function index(): void
    {
        $this->requireSuperAdmin();

        $tokens = (new DemoTokenModel())->listActive();
        $sports = (new SportModel())->listActive();

        $this->render('admin/demos', [
            'title'  => 'Demo — tokeny',
            'tokens' => $tokens,
            'sports' => $sports,
        ]);
    }

    public function create(): void
    {
        $this->requireSuperAdmin();
        Csrf::verify();

        $clubName = trim($_POST['club_name'] ?? '');
        if ($clubName === '') {
            $clubName = 'Demo Club ' . date('Y-m-d H:i');
        }
        $selectedSports  = array_values(array_filter((array)($_POST['sports']  ?? [])));
        $selectedModules = array_values(array_filter((array)($_POST['modules'] ?? [])));
        $volume   = in_array($_POST['volume'] ?? '', ['basic', 'standard', 'full']) ? $_POST['volume'] : 'standard';
        $duration = in_array((int)($_POST['duration'] ?? 14), [7, 14, 30]) ? (int)$_POST['duration'] : 14;

        $db = \App\Helpers\Database::pdo();
        $db->beginTransaction();

        try {
            $clubId = (new ClubModel())->insert([
                'name'  => $clubName,
                'city'  => 'Demo',
                'email' => 'demo-' . time() . '@demo.test',
            ]);

            $userModel    = new UserModel();
            $demoUsername = 'demo_' . $clubId;
            $existing     = $userModel->findByUsername($demoUsername);
            if (!$existing) {
                $demoUserId = $userModel->create([
                    'username'  => $demoUsername,
                    'email'     => "demo_{$clubId}@demo.test",
                    'full_name' => 'Użytkownik Demo',
                    'password'  => bin2hex(random_bytes(16)),
                ]);
            } else {
                $demoUserId = (int)$existing['id'];
            }

            (new UserClubModel())->grantRole($demoUserId, $clubId, 'zarzad');

            DemoSeeder::seedEnhanced($clubId, [
                'sports'  => $selectedSports,
                'modules' => $selectedModules,
                'volume'  => $volume,
            ]);

            $token = (new DemoTokenModel())->createToken($clubId, $duration, Auth::id());

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            Session::flash('error', 'Błąd tworzenia demo: ' . $e->getMessage());
            $this->redirect('admin/demos');
        }

        Session::flash('success', "Demo \"{$clubName}\" utworzone. Link gotowy do udostępnienia.");
        $this->redirect('admin/demos');
    }

    public function loginViaToken(string $token): void
    {
        $model = new DemoTokenModel();
        $demo  = $model->findByToken($token);

        if (!$demo) {
            Session::flash('error', 'Link demo jest nieprawidłowy lub wygasł.');
            $this->redirect('auth/login');
        }

        $clubId = (int)$demo['club_id'];

        $db   = \App\Helpers\Database::pdo();
        $stmt = $db->prepare(
            "SELECT u.* FROM users u
             JOIN user_clubs uc ON uc.user_id = u.id
             WHERE uc.club_id = ? AND u.username LIKE 'demo_%'
             LIMIT 1"
        );
        $stmt->execute([$clubId]);
        $user = $stmt->fetch();

        if (!$user) {
            Session::flash('error', 'Nie znaleziono użytkownika demo.');
            $this->redirect('auth/login');
        }

        Auth::login($user);
        Auth::setClub($clubId, 'zarzad');

        $this->redirect('dashboard');
    }

    public function cleanup(): void
    {
        $this->requireSuperAdmin();
        Csrf::verify();

        $deleted = (new DemoTokenModel())->cleanup();
        Session::flash('success', "Wyczyszczono {$deleted} wygasłych tokenów demo.");
        $this->redirect('admin/demos');
    }

    public function delete(string $id): void
    {
        $this->requireSuperAdmin();
        Csrf::verify();

        (new DemoTokenModel())->deleteToken((int)$id);
        Session::flash('success', 'Token demo usunięty.');
        $this->redirect('admin/demos');
    }
}
