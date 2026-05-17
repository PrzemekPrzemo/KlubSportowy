<?php

namespace Tests\Unit;

use App\Helpers\TrainingSignupService;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 *
 * Testy logiki member-self signup. Sprawdza:
 *  - poprawne ustawienie deadlinu (godzinowy odstep przed startem),
 *  - zachowanie out-of-band wartosci (odwolany trening, brak start_time, deadline=0),
 *  - obecnosc + multitenant guard w kodzie serwisu (presence test).
 *
 * Logika atomic counter / FOR UPDATE / waitlist / promote wymaga DB i jest
 * pokryta przez Integration tests (gdy dostepny MySQL); tutaj sprawdzamy
 * deterministyczna logike deadline ktora jest pure-PHP, oraz strukture serwisu.
 */
class MemberTrainingSignupTest extends TestCase
{
    private function training(string $start, int $deadlineH = 2, array $overrides = []): array
    {
        return array_merge([
            'id'                    => 1,
            'club_id'               => 10,
            'name'                  => 'Trening A',
            'start_time'            => $start,
            'status'                => 'zaplanowany',
            'signup_enabled'        => 1,
            'waitlist_enabled'      => 1,
            'max_participants'      => 10,
            'signup_deadline_hours' => $deadlineH,
        ], $overrides);
    }

    public function testBeforeDeadlineWhenWellInAdvance(): void
    {
        $start = (new \DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s');
        $this->assertTrue(TrainingSignupService::isBeforeDeadline($this->training($start, 2)));
    }

    public function testDeadlinePassedWhenStartIsNow(): void
    {
        $start = (new \DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');
        // deadline=2h przed startem → 30 min przed startem to JUZ za pozno.
        $this->assertFalse(TrainingSignupService::isBeforeDeadline($this->training($start, 2)));
    }

    public function testDeadlineExactlyAtCutoffIsClosed(): void
    {
        // Test deterministyczny: $now = $start - 2h, $cutoff = $start - 2h.
        // $now < $cutoff => false (rowne → nie jestesmy juz "przed" deadlinem).
        $now   = new \DateTimeImmutable('2025-05-17 10:00:00');
        $start = '2025-05-17 12:00:00';
        $this->assertFalse(TrainingSignupService::isBeforeDeadline($this->training($start, 2), $now));
    }

    public function testZeroDeadlineAllowsUntilStart(): void
    {
        $now   = new \DateTimeImmutable('2025-05-17 11:59:00');
        $start = '2025-05-17 12:00:00';
        $this->assertTrue(TrainingSignupService::isBeforeDeadline($this->training($start, 0), $now));
    }

    public function testMissingStartTimeReturnsFalse(): void
    {
        $this->assertFalse(TrainingSignupService::isBeforeDeadline($this->training('', 2)));
    }

    public function testServiceClassExposesPublicAPI(): void
    {
        // Smoke test: kluczowe metody istnieja (wymagane przez controller).
        $this->assertTrue(method_exists(TrainingSignupService::class, 'signup'));
        $this->assertTrue(method_exists(TrainingSignupService::class, 'cancel'));
        $this->assertTrue(method_exists(TrainingSignupService::class, 'isBeforeDeadline'));
    }

    public function testSignupContainsMultiTenantGuard(): void
    {
        // Statyczny audit: kod metody signup() porownuje club_id treningu z club_id membera.
        $src = file_get_contents(__DIR__ . '/../../app/Helpers/TrainingSignupService.php');
        $this->assertNotFalse($src);
        $this->assertStringContainsString('club_id', $src);
        $this->assertMatchesRegularExpression(
            '/\(int\)\$training\[\'club_id\'\]\s*!==\s*\$memberClubId/',
            $src,
            'Brak guard porownujacego club_id treningu z club_id membera (multi-tenant).'
        );
    }

    public function testSignupContainsForUpdateLock(): void
    {
        // Statyczny audit: race-safe count uzywa SELECT ... FOR UPDATE.
        $src = file_get_contents(__DIR__ . '/../../app/Helpers/TrainingSignupService.php');
        $this->assertNotFalse($src);
        $this->assertStringContainsString('FOR UPDATE', $src,
            'Brak SELECT ... FOR UPDATE w TrainingSignupService — wyscig moze przekroczyc max_participants.');
    }

    public function testCancelHasAutoPromoteWaitlist(): void
    {
        // Statyczny audit: cancel() promuje pierwszego z waitlist.
        $src = file_get_contents(__DIR__ . '/../../app/Helpers/TrainingSignupService.php');
        $this->assertNotFalse($src);
        $this->assertStringContainsString("status = 'waitlist'", $src);
        $this->assertMatchesRegularExpression(
            "/UPDATE\s+training_attendees\s+SET\s+status\s*=\s*'signed_up'/i",
            $src,
            'Brak promocji rezerwowego na signed_up po anulowaniu.'
        );
    }

    public function testControllerRoutesRegistered(): void
    {
        $routes = file_get_contents(__DIR__ . '/../../public/index.php');
        $this->assertNotFalse($routes);
        $this->assertStringContainsString('/portal/training/:id/signup', $routes);
        $this->assertStringContainsString('/portal/training/:id/cancel', $routes);
    }

    public function testControllerEndpointsUseCsrf(): void
    {
        $src = file_get_contents(__DIR__ . '/../../app/Controllers/MemberPortalController.php');
        $this->assertNotFalse($src);
        // Wymagaj Csrf::verify() w obu nowych akcjach.
        $this->assertMatchesRegularExpression(
            '/function\s+signupTraining[^}]*Csrf::verify\(\)/s',
            $src,
            'signupTraining() musi miec Csrf::verify().'
        );
        $this->assertMatchesRegularExpression(
            '/function\s+cancelTraining[^}]*Csrf::verify\(\)/s',
            $src,
            'cancelTraining() musi miec Csrf::verify().'
        );
    }
}
