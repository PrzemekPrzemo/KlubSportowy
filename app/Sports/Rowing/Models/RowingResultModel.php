<?php
namespace App\Sports\Rowing\Models;
use App\Models\ClubScopedModel;
class RowingResultModel extends ClubScopedModel {
    protected string $table = 'rowing_results';
    public static array $CATEGORIES = ['single_scull'=>'Jedynka','double_scull'=>'Dwójka podwójna','four'=>'Czwórka','eight'=>'Ósemka','coxless_pair'=>'Dwójka bez sternika'];
    public static array $BOAT_TYPES = [
        '1x'  => '1x — jedynka (skiff)',
        '2x'  => '2x — dwójka podwójna',
        '2-'  => '2- — dwójka bez sternika',
        '2+'  => '2+ — dwójka ze sternikiem',
        '4x'  => '4x — czwórka podwójna',
        '4-'  => '4- — czwórka bez sternika',
        '4+'  => '4+ — czwórka ze sternikiem',
        '8+'  => '8+ — ósemka',
    ];
    public static array $DISTANCES = [500, 1000, 2000, 5000, 6000];
    public static function formatTime(?int $ms): string
    {
        if ($ms === null) return '—';
        $minutes = intdiv($ms, 60000);
        $seconds = intdiv($ms % 60000, 1000);
        $centis  = intdiv($ms % 1000, 10);
        return sprintf('%d:%02d.%02d', $minutes, $seconds, $centis);
    }
    public function listForClub(): array {
        $stmt = $this->db->prepare("SELECT rr.*, m.first_name, m.last_name, m.member_number FROM rowing_results rr JOIN members m ON m.id = rr.member_id WHERE rr.club_id = ? ORDER BY rr.competition_date DESC, m.last_name");
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
