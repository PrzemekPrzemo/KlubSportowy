<?php

declare(strict_types=1);

namespace App\Sports\Golf\Models;

use App\Models\ClubScopedModel;

/**
 * sport_golf_courses — baza pól golfowych dostępnych w klubie.
 */
class GolfCourseModel extends ClubScopedModel
{
    protected string $table = 'sport_golf_courses';

    public function listForClub(): array
    {
        $clubId = $this->clubId();
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
              WHERE club_id = ?
           ORDER BY name ASC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }
}
