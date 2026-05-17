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

        // Treningi (cross-club — trener moze prowadzic w paru klubach).
        // Lista obejmuje +- 14 dni od dzisiaj — tak, by trener mogl wpisac
        // zaleglosci (past) i widziec planowane (future).
        $stmt = Database::pdo()->prepare(
            "SELECT t.id, t.name, t.start_time, t.end_time, t.club_id,
                    c.name AS club_name,
                    (SELECT COUNT(*) FROM training_attendees ta WHERE ta.training_id = t.id) AS total_attendees,
                    (SELECT COUNT(*) FROM training_attendees ta
                       WHERE ta.training_id = t.id
                         AND ta.status IN ('obecny','nieobecny','spozniony','wypisany')) AS marked_attendees
             FROM trainings t
             LEFT JOIN clubs c ON c.id = t.club_id
             WHERE t.instructor_id = ?
               AND t.start_time >= DATE_SUB(NOW(), INTERVAL 14 DAY)
               AND t.start_time <= DATE_ADD(NOW(), INTERVAL 30 DAY)
             ORDER BY t.start_time ASC
             LIMIT 100"
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
