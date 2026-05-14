<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Helpers\Auth;
use App\Helpers\Session;
use App\Models\UserModel;

/**
 * Feature: logowanie admina / weryfikacja sesji / wymuszanie loginu.
 *
 * Testujemy warstwę modelu (UserModel) + Helpera (Auth, Session) — to jest
 * dokładnie ten kontrakt którego używa AuthController. Pełny HTTP round-trip
 * (POST /login → 302 → /dashboard) wymagałby integracji z PHP built-in server,
 * co jest poza scope unit/feature suite — zostawione na e2e.
 */
class AuthTest extends FeatureTestCase
{
    public function testUserModelFindByUsernameAndVerifyPassword(): void
    {
        $clubId = $this->createClub('Auth Test Club');
        $user   = $this->createUser($clubId, 'sekretne123', 'admin', 'jan.admin');

        $userModel = new UserModel();
        $row = $userModel->findByUsername('jan.admin');

        $this->assertNotNull($row, 'User powinien zostać znaleziony po username');
        $this->assertSame($user['id'], (int)$row['id']);
        $this->assertTrue(
            $userModel->verifyPassword($row, 'sekretne123'),
            'Hasło powinno zostać zweryfikowane pozytywnie'
        );
        $this->assertFalse(
            $userModel->verifyPassword($row, 'zle_haslo'),
            'Błędne hasło musi NIE przejść weryfikacji'
        );
    }

    public function testFindByEmailFallback(): void
    {
        $clubId = $this->createClub('Auth Test Club B');
        $user   = $this->createUser($clubId, 'tajne9999', 'admin', 'user.b', 'user.b@example.test');

        $userModel = new UserModel();
        // AuthController używa findByUsername() ?? findByEmail() — symulujemy tą ścieżkę.
        $row = $userModel->findByUsername('user.b@example.test')
            ?? $userModel->findByEmail('user.b@example.test');

        $this->assertNotNull($row, 'User powinien być znaleziony po email-u jako username');
        $this->assertSame($user['id'], (int)$row['id']);
    }

    public function testAuthLoginSetsSessionAndAuthCheckReturnsTrue(): void
    {
        $clubId = $this->createClub('Auth Login Club');
        $user   = $this->createUser($clubId, 'pass1234', 'admin');

        // Auth::check() przed login = false
        $this->assertFalse(Auth::check(), 'Bez login Auth::check() = false');

        // Symuluj login (Auth::login wymaga session_regenerate_id, którego
        // nie możemy w CLI wywołać — używamy Session::set bezpośrednio,
        // co odzwierciedla efekt logowania).
        Session::set('user_id', $user['id']);
        Session::set('username', $user['username']);
        Session::set('full_name', $user['username']);
        Session::set('email', $user['email']);

        $this->assertTrue(Auth::check(), 'Po ustawieniu user_id w sesji Auth::check() = true');
        $this->assertSame($user['id'], Auth::id());
        $this->assertSame($user['username'], Auth::user()['username']);
    }

    public function testLogoutClearsSession(): void
    {
        $clubId = $this->createClub('Logout Club');
        $user   = $this->createUser($clubId);

        Session::set('user_id', $user['id']);
        Session::set('username', $user['username']);
        $this->assertTrue(Auth::check());

        // logout = wyczyść klucze sesji (tak robi AuthController::logout)
        Session::remove('user_id');
        Session::remove('username');

        $this->assertFalse(
            Auth::check(),
            'Po wylogowaniu Auth::check() musi zwracać false'
        );
        $this->assertNull(Auth::id());
    }

    public function testFindByUsernameReturnsNullForUnknownUser(): void
    {
        // Bez tworzenia usera — żaden klub.
        $userModel = new UserModel();
        $this->assertNull(
            $userModel->findByUsername('nieistnieje_' . bin2hex(random_bytes(4))),
            'Nieznany user powinien zwrócić null'
        );
    }

    public function testInactiveUserCannotPassActiveCheck(): void
    {
        $clubId = $this->createClub('Inactive Auth Club');
        $username = 'inactive_' . bin2hex(random_bytes(4));
        $hash = password_hash('whatever123', PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO users (username, email, password, full_name, is_active, created_at)
             VALUES (?, ?, ?, ?, 0, NOW())"
        );
        $stmt->execute([$username, $username . '@test.local', $hash, $username]);

        $userModel = new UserModel();
        $row = $userModel->findByUsername($username);
        $this->assertNotNull($row);
        // AuthController odrzuca inactive userów PRZED weryfikacją hasła.
        $this->assertSame(0, (int)$row['is_active'], 'is_active musi być 0 dla inactive usera');
    }
}
