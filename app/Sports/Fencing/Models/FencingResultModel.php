<?php
namespace App\Sports\Fencing\Models;
use App\Models\ClubScopedModel;
class FencingResultModel extends ClubScopedModel {
    protected string $table = 'fencing_results';
    public static array $CATEGORIES = [
        'foil'  => 'Floret',
        'epee'  => 'Szpada',
        'sabre' => 'Szabla',
    ];
    public static array $ROUNDS = [
        'Finał', 'Półfinał', 'Top 8', 'Top 16', 'Top 32', 'Eliminacje', 'Grupy',
    ];
    public function listForClub(): array {
        $stmt = $this->db->prepare("SELECT fr.*, m.first_name, m.last_name, m.member_number FROM fencing_results fr JOIN members m ON m.id = fr.member_id WHERE fr.club_id = ? ORDER BY fr.competition_date DESC, m.last_name");
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
