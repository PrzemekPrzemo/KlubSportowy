<?php
namespace App\Sports\TableTennis\Models;
use App\Models\ClubScopedModel;
class TableTennisResultModel extends ClubScopedModel {
    protected string $table = 'table_tennis_results';
    public static array $CATEGORIES = ['singles'=>'Singel','doubles'=>'Debel','mixed_doubles'=>'Mikst','team'=>'Drużyna'];
    public static array $LEAGUE_CLASSES = [
        'ekstraklasa' => 'Ekstraklasa',
        '1_liga'      => '1 Liga',
        '2_liga'      => '2 Liga',
        '3_liga'      => '3 Liga',
        'okregowa'    => 'Okręgowa',
        'zawody'      => 'Zawody otwarte',
        'mp'          => 'Mistrzostwa Polski',
    ];
    public function listForClub(): array {
        $stmt = $this->db->prepare("SELECT ttr.*, m.first_name, m.last_name, m.member_number FROM table_tennis_results ttr JOIN members m ON m.id = ttr.member_id WHERE ttr.club_id = ? ORDER BY ttr.competition_date DESC, m.last_name");
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
