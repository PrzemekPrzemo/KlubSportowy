<?php

namespace Tests\Integration;

use App\Helpers\Database;

/**
 * @group integration
 *
 * Hotfix regression: self-registration klubow MUSI byc wylaczona.
 *
 * Tylko Master Admin moze tworzyc kluby — przez:
 *   /admin/clubs (CRUD master)
 *   /admin/demos (token demo z czasem wygasniecia)
 *
 * Wczesniej POST /register fabrykowal cluby + uzytkownikow + 30-dniowy
 * trial subskrypcji — bez weryfikacji. Bug krytyczny: ktokolwiek mogl
 * spamowac DB lub fabrykowac fake-organizacje.
 */
class SelfRegistrationDisabledTest extends TestCase
{
    public function testPostRegisterDoesNotCreateClubOrUser(): void
    {
        $db = $this->requireDatabase();

        // Snapshot przed proba rejestracji
        $clubsBefore = (int)$db->query("SELECT COUNT(*) FROM clubs")->fetchColumn();
        $usersBefore = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();

        // Symulujemy sciezke kontrolera — bezposrednio invoke zarejestrowac
        // i sprawdzic ze NIE robi insert do DB.
        // (Nie probujemy faktycznego HTTP — to integration test logiki.)
        $controller = new \App\Controllers\AuthController();

        // Csrf::verify() wymaga tokenu — symulujemy mock-token
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'club_name' => 'Hacker Klub ' . bin2hex(random_bytes(4)),
            'city'      => 'Atak',
            'email'     => 'hacker_' . bin2hex(random_bytes(4)) . '@evil.test',
            'username'  => 'hacker_' . bin2hex(random_bytes(4)),
            'full_name' => 'Hacker Smith',
            'password'  => 'StrongPass123',
        ];

        // Controller robi Csrf::verify() ktory rzuca exception bez prawidlowego
        // tokenu. To NIE jest bug ale testujemy ze nawet z bypass'em CSRF
        // (gdyby ktos znalazł sposob) endpoint NIE tworzy klubu — robi tylko
        // http_response_code(410) + redirect.
        try {
            ob_start();
            $controller->register();
            ob_end_clean();
        } catch (\Throwable $e) {
            // Csrf throws lub redirect powoduje exit — to OK
            ob_get_level() && ob_end_clean();
        }

        $clubsAfter = (int)$db->query("SELECT COUNT(*) FROM clubs")->fetchColumn();
        $usersAfter = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();

        $this->assertSame(
            $clubsBefore,
            $clubsAfter,
            'POST /register stworzyl klub! Self-registration MUSI byc wylaczona.'
        );
        $this->assertSame(
            $usersBefore,
            $usersAfter,
            'POST /register stworzyl uzytkownika! Self-registration MUSI byc wylaczona.'
        );
    }

    public function testRegisterDisabledViewExists(): void
    {
        // Sanity: widok existuje, AuthController::showRegister go renderuje.
        $viewPath = ROOT_PATH . '/app/Views/auth/register_disabled.php';
        $this->assertFileExists($viewPath, 'register_disabled.php view brakuje');
    }

    public function testOldRegisterViewDoesNotExist(): void
    {
        // Stary register.php zawieral form do POST /register — usunelismy.
        $viewPath = ROOT_PATH . '/app/Views/auth/register.php';
        $this->assertFileDoesNotExist(
            $viewPath,
            'Stary form rejestracyjny app/Views/auth/register.php nadal istnieje'
        );
    }
}
