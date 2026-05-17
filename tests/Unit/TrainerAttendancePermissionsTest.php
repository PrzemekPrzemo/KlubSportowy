<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Statyczny audit guardow trenera dla flow obecnosci.
 *
 * Sprawdza, ze:
 *  - TrainerAttendanceController posiada wymagane bramki dostepu
 *    (requireRole z trener/instruktor/zarzad/admin + requireClubContext).
 *  - Logika ownership-guard (instructor_id check) jest obecna w kodzie.
 *  - Cross-club guard (training.club_id = ClubContext::current()) jest obecny.
 *  - Past-edit limit (PAST_EDIT_LIMIT_DAYS = 7) jest egzekwowany.
 *  - TrainingsController::markAttendance ma analogiczne defense-in-depth guards.
 *
 * Test nie wymaga DB ani HTTP — analizuje zrodla.
 */
class TrainerAttendancePermissionsTest extends TestCase
{
    private string $trainerCtrl;
    private string $trainingsCtrl;
    private string $tournamentResultsCtrl;

    protected function setUp(): void
    {
        $this->trainerCtrl           = (string)file_get_contents(__DIR__ . '/../../app/Controllers/TrainerAttendanceController.php');
        $this->trainingsCtrl         = (string)file_get_contents(__DIR__ . '/../../app/Controllers/TrainingsController.php');
        $this->tournamentResultsCtrl = (string)file_get_contents(__DIR__ . '/../../app/Controllers/TournamentResultsController.php');

        $this->assertNotEmpty($this->trainerCtrl,           'TrainerAttendanceController.php not found');
        $this->assertNotEmpty($this->trainingsCtrl,         'TrainingsController.php not found');
        $this->assertNotEmpty($this->tournamentResultsCtrl, 'TournamentResultsController.php not found');
    }

    public function testTrainerControllerRequiresAuthAndRoles(): void
    {
        $this->assertStringContainsString('requireLogin()',       $this->trainerCtrl);
        $this->assertStringContainsString('requireClubContext()', $this->trainerCtrl);
        $this->assertMatchesRegularExpression(
            "/requireRole\\(\\s*\\[\\s*'trener'\\s*,\\s*'instruktor'\\s*,\\s*'zarzad'\\s*,\\s*'admin'\\s*\\]\\s*\\)/",
            $this->trainerCtrl,
            'TrainerAttendanceController musi miec requireRole([trener,instruktor,zarzad,admin])'
        );
    }

    public function testTrainerControllerHasOwnershipGuard(): void
    {
        // grid/save → loadTrainingForCurrentUser implements:
        //  - cross-club guard (training.club_id <> current)
        //  - ownership guard (instructor_id != current user → reject for non-privileged)
        $this->assertStringContainsString('loadTrainingForCurrentUser', $this->trainerCtrl);
        $this->assertStringContainsString('Cross-club guard',          $this->trainerCtrl);
        $this->assertStringContainsString('instructor_id',             $this->trainerCtrl);
        $this->assertStringContainsString('isPrivileged',              $this->trainerCtrl);
        $this->assertStringContainsString("hasRole(['zarzad','admin'])", $this->trainerCtrl);
    }

    public function testTrainerControllerEnforcesPastEditLimit(): void
    {
        $this->assertStringContainsString('PAST_EDIT_LIMIT_DAYS = 7', $this->trainerCtrl);
        $this->assertStringContainsString('isTrainingEditableByCurrentUser', $this->trainerCtrl);
        $this->assertMatchesRegularExpression(
            '/isSuperAdmin\\(\\).*hasRole.*zarzad.*admin/s',
            $this->trainerCtrl,
            'Past-edit limit musi mieć bypass dla zarzad/admin/super admin'
        );
    }

    public function testTrainerControllerWritesAuditLog(): void
    {
        $this->assertStringContainsString('tenant_access_log',     $this->trainerCtrl);
        $this->assertStringContainsString('attendance_marked',     $this->trainerCtrl);
        $this->assertStringContainsString("'write'",               $this->trainerCtrl);
    }

    public function testTrainerControllerSaveRequiresCsrf(): void
    {
        $this->assertStringContainsString('Csrf::verify()', $this->trainerCtrl);
    }

    public function testTrainingsControllerMarkAttendanceHasCrossClubGuard(): void
    {
        $this->assertMatchesRegularExpression(
            '/markAttendance.*club_id.*instructor_id/s',
            $this->trainingsCtrl,
            'TrainingsController::markAttendance musi sprawdzac club_id + instructor_id'
        );
        $this->assertStringContainsString('ClubContext::current()', $this->trainingsCtrl);
        $this->assertStringContainsString('http_response_code(403)', $this->trainingsCtrl);
    }

    public function testTournamentResultsAllowsInstruktor(): void
    {
        $this->assertMatchesRegularExpression(
            "/requireRole\\(\\s*\\[[^\\]]*'trener'[^\\]]*'instruktor'[^\\]]*\\]\\s*\\)/",
            $this->tournamentResultsCtrl,
            'TournamentResultsController musi pozwalac na trener + instruktor'
        );
    }

    public function testRoutesAreRegistered(): void
    {
        $routes = (string)file_get_contents(__DIR__ . '/../../public/index.php');
        $this->assertStringContainsString('/trainer/dashboard',                              $routes);
        $this->assertStringContainsString('/trainer/training/:id/attendance',                $routes);
        $this->assertStringContainsString('/trainer/training/:id/attendance/save',           $routes);
        $this->assertStringContainsString('/trainer/members',                                $routes);
        $this->assertStringContainsString('/trainer/trainings/today',                        $routes);
    }

    public function testGridViewExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../app/Views/trainer/attendance/grid.php');
        $this->assertFileExists(__DIR__ . '/../../app/Views/trainer/dashboard.php');
        $this->assertFileExists(__DIR__ . '/../../app/Views/trainer/members/roster.php');
    }

    public function testGridViewIsMobileFriendly(): void
    {
        $grid = (string)file_get_contents(__DIR__ . '/../../app/Views/trainer/attendance/grid.php');
        $this->assertStringContainsString('attendance-sticky-bar', $grid);
        $this->assertStringContainsString('@media (min-width: 768px)', $grid);
        $this->assertStringContainsString('all_present', $grid);
        $this->assertStringContainsString('all_absent',  $grid);
    }
}
