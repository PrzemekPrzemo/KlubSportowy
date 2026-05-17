<?php
namespace App\Sports\Badminton\Models;
use App\Models\ClubScopedModel;
class BadmintonResultModel extends ClubScopedModel {
    protected string $table = 'badminton_results';
    public static array $CATEGORIES = ['singles'=>'Singel','doubles'=>'Debel','mixed_doubles'=>'Mikst','team'=>'Drużyna'];
    public function listForClub(): array {
        $stmt = $this->db->prepare("SELECT br.*, m.first_name, m.last_name, m.member_number FROM badminton_results br JOIN members m ON m.id = br.member_id WHERE br.club_id = ? ORDER BY br.competition_date DESC, m.last_name");
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }

    public function listForMember(int $memberId): array {
        $stmt = $this->db->prepare("SELECT br.*, m.first_name, m.last_name, m.member_number FROM badminton_results br JOIN members m ON m.id = br.member_id WHERE br.club_id = ? AND br.member_id = ? ORDER BY br.competition_date DESC");
        $stmt->execute([$this->clubId(), $memberId]);
        return $stmt->fetchAll();
    }
}
