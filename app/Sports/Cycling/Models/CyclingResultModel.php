<?php
namespace App\Sports\Cycling\Models;
use App\Models\ClubScopedModel;
class CyclingResultModel extends ClubScopedModel {
    protected string $table = 'cycling_results';
    public static array $CATEGORIES = ['road'=>'Szosa','track'=>'Tor','mtb'=>'MTB','bmx'=>'BMX','cyclocross'=>'Przełaj'];
    public static array $RACE_TYPES = [
        'road'        => 'Szosa (wyścig etapowy)',
        'criterium'   => 'Kryterium',
        'track'       => 'Tor',
        'mtb_xco'     => 'MTB Cross-Country (XCO)',
        'mtb_dh'      => 'MTB Zjazd (DH)',
        'bmx'         => 'BMX',
        'cyclocross'  => 'Przełaj (Cyclocross)',
        'gravel'      => 'Gravel',
    ];
    public static array $UCI_CATEGORIES = [
        'Elite', 'U23', 'Junior', 'Junior (15-16)', 'Masters 30+', 'Masters 40+', 'Masters 50+',
    ];
    public static function formatTime(?float $seconds): string
    {
        if ($seconds === null) return '—';
        $h = intdiv((int)$seconds, 3600);
        $m = intdiv((int)$seconds % 3600, 60);
        $s = (int)$seconds % 60;
        $cs = round(($seconds - floor($seconds)) * 100);
        if ($h > 0) return sprintf('%d:%02d:%02d', $h, $m, $s);
        return sprintf('%d:%02d.%02d', $m, $s, $cs);
    }
    public function listForClub(): array {
        $stmt = $this->db->prepare("SELECT cr.*, m.first_name, m.last_name, m.member_number FROM cycling_results cr JOIN members m ON m.id = cr.member_id WHERE cr.club_id = ? ORDER BY cr.competition_date DESC, m.last_name");
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
