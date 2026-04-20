<?php
namespace App\Sports\Climbing\Models;
use App\Models\ClubScopedModel;
class ClimbingResultModel extends ClubScopedModel {
    protected string $table = 'climbing_results';
    public static array $CATEGORIES = ['lead'=>'Prowadzenie','boulder'=>'Bouldering','speed'=>'Szybkość','combined'=>'Kombinacja'];
    public function listForClub(): array {
        $stmt = $this->db->prepare("SELECT cr.*, m.first_name, m.last_name, m.member_number FROM climbing_results cr JOIN members m ON m.id = cr.member_id WHERE cr.club_id = ? ORDER BY cr.competition_date DESC, m.last_name");
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
