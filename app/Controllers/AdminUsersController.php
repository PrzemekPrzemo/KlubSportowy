<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\ActivityLogModel;
use App\Models\UserModel;

class AdminUsersController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function index(): void
    {
        $db = Database::pdo();
        $rows = $db->query(
            "SELECT id, username, email, full_name, phone, is_active, last_login, created_at
             FROM users
             WHERE is_super_admin = 1
             ORDER BY is_active DESC, username ASC"
        )->fetchAll();

        $this->render('admin/users/index', [
            'title'   => 'Super administratorzy',
            'rows'    => $rows,
            'currentUserId' => (int)Auth::id(),
        ]);
    }

    public function create(): void
    {
        $this->render('admin/users/create', [
            'title' => 'Nowy super admin',
        ]);
    }

    public function store(): void
    {
        Csrf::verify();

        $username = trim((string)($_POST['username'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $phone    = trim((string)($_POST['phone'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirm'] ?? '');

        $errors = [];
        if ($username === '' || !preg_match('/^[a-zA-Z0-9_.-]{3,40}$/', $username)) {
            $errors[] = 'Nazwa użytkownika: 3–40 znaków (litery, cyfry, _ . -).';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Nieprawidłowy e-mail.';
        }
        if ($fullName === '') {
            $errors[] = 'Pełna nazwa jest wymagana.';
        }
        if (strlen($password) < 12) {
            $errors[] = 'Hasło musi mieć co najmniej 12 znaków.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Hasła nie są identyczne.';
        }

        $userModel = new UserModel();
        if (empty($errors) && ($userModel->findByUsername($username) || $userModel->findByEmail($email))) {
            $errors[] = 'Użytkownik z takim loginem lub e-mailem już istnieje.';
        }

        if (!empty($errors)) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect('admin/users/create');
        }

        $userId = $userModel->create([
            'username'       => $username,
            'email'          => $email,
            'full_name'      => $fullName,
            'phone'          => $phone !== '' ? $phone : null,
            'password'       => $password,
            'is_super_admin' => 1,
            'is_active'      => 1,
        ]);

        (new ActivityLogModel())->log('super_admin_create', 'user', $userId, "username={$username}");
        Session::flash('success', 'Super admin utworzony.');
        $this->redirect('admin/users');
    }

    public function deactivate(string $id): void
    {
        Csrf::verify();
        $uid = (int)$id;
        if ($uid === (int)Auth::id()) {
            Session::flash('error', 'Nie możesz dezaktywować własnego konta.');
            $this->redirect('admin/users');
        }

        $db = Database::pdo();
        $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = ? AND is_super_admin = 1");
        $stmt->execute([$uid]);

        (new ActivityLogModel())->log('super_admin_deactivate', 'user', $uid);
        Session::flash('success', 'Konto dezaktywowane.');
        $this->redirect('admin/users');
    }

    public function activate(string $id): void
    {
        Csrf::verify();
        $uid = (int)$id;
        $db = Database::pdo();
        $stmt = $db->prepare("UPDATE users SET is_active = 1 WHERE id = ? AND is_super_admin = 1");
        $stmt->execute([$uid]);

        (new ActivityLogModel())->log('super_admin_activate', 'user', $uid);
        Session::flash('success', 'Konto aktywowane.');
        $this->redirect('admin/users');
    }

    public function resetPassword(string $id): void
    {
        Csrf::verify();
        $uid = (int)$id;

        // Generate 16-char temp password
        $temp = bin2hex(random_bytes(8));
        $hash = password_hash($temp, PASSWORD_BCRYPT);

        $db = Database::pdo();
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ? AND is_super_admin = 1");
        $stmt->execute([$hash, $uid]);

        (new ActivityLogModel())->log('super_admin_reset_password', 'user', $uid);
        Session::flash('warning', "Nowe hasło tymczasowe: {$temp} (zapisz — pokazywane tylko raz).");
        $this->redirect('admin/users');
    }
}
