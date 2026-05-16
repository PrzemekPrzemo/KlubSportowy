<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Database;
use App\Models\TrainerAvailabilityModel;
use App\Models\TrainerLeaveModel;
use App\Models\TrainerScheduleConflictModel;

/**
 * /trainer/schedule
 *
 * Perspektywa trenera (read-only): wlasna dostepnosc, nadchodzace treningi,
 * urlopy, wykryte konflikty.
 */
class MyScheduleController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireRole(['trener', 'instruktor']);
    }

    public function index(): void
    {
        $userId = (int)Auth::id();
        $clubId = ClubContext::current();

        $availability = (new TrainerAvailabilityModel())->forUser($userId, $clubId);
        $leaves       = (new TrainerLeaveModel())->upcomingForUser($userId, 20);
        $conflicts    = (new TrainerScheduleConflictModel())->unresolvedForUser($userId, 50);

        // Nadchodzace treningi (cross-club — trener moze prowadzic w paru klubach)
        $stmt = Database::pdo()->prepare(
            "SELECT t.id, t.name, t.start_time, t.end_time, t.club_id,
                    c.name AS club_name
             FROM trainings t
             LEFT JOIN clubs c ON c.id = t.club_id
             WHERE t.instructor_id = ?
               AND t.status IN ('zaplanowany','w_trakcie')
               AND t.start_time >= NOW()
             ORDER BY t.start_time ASC
             LIMIT 50"
        );
        $stmt->execute([$userId]);
        $upcoming = $stmt->fetchAll();

        $this->render('trainer/schedule/index', [
            'title'        => 'Moja dostepnosc',
            'availability' => $availability,
            'leaves'       => $leaves,
            'conflicts'    => $conflicts,
            'upcoming'     => $upcoming,
        ]);
    }
}
