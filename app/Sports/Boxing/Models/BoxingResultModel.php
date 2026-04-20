<?php
namespace App\Sports\Boxing\Models;
use App\Models\ClubScopedModel;
class BoxingResultModel extends ClubScopedModel {
    protected string $table = 'boxing_results';
    public static array $CATEGORIES = ['amateur'=>'Amatorski','youth'=>'Młodzieżowy','elite'=>'Elite'];
    public function listForClub(): array {
        $stmt = $this->db->prepare("SELECT br.*, m.first_name, m.last_name, m.member_number FROM boxing_results br JOIN members m ON m.id = br.member_id WHERE br.club_id = ? ORDER BY br.competition_date DESC, m.last_name");
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
