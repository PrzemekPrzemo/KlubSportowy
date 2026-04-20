<?php
namespace App\Sports\Cycling\Models;
use App\Models\ClubScopedModel;
class CyclingResultModel extends ClubScopedModel {
    protected string $table = 'cycling_results';
    public static array $CATEGORIES = ['road'=>'Szosa','track'=>'Tor','mtb'=>'MTB','bmx'=>'BMX','cyclocross'=>'Przełaj'];
    public function listForClub(): array {
        $stmt = $this->db->prepare("SELECT cr.*, m.first_name, m.last_name, m.member_number FROM cycling_results cr JOIN members m ON m.id = cr.member_id WHERE cr.club_id = ? ORDER BY cr.competition_date DESC, m.last_name");
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
