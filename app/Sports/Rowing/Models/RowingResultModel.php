<?php
namespace App\Sports\Rowing\Models;
use App\Models\ClubScopedModel;
class RowingResultModel extends ClubScopedModel {
    protected string $table = 'rowing_results';
    public static array $CATEGORIES = ['single_scull'=>'Jedynka','double_scull'=>'Dwójka podwójna','four'=>'Czwórka','eight'=>'Ósemka','coxless_pair'=>'Dwójka bez sternika'];
    public function listForClub(): array {
        $stmt = $this->db->prepare("SELECT rr.*, m.first_name, m.last_name, m.member_number FROM rowing_results rr JOIN members m ON m.id = rr.member_id WHERE rr.club_id = ? ORDER BY rr.competition_date DESC, m.last_name");
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
