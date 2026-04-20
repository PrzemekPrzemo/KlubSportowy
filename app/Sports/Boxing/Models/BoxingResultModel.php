<?php
namespace App\Sports\Boxing\Models;
use App\Models\ClubScopedModel;
class BoxingResultModel extends ClubScopedModel {
    protected string $table = 'boxing_results';
    public static array $CATEGORIES = ['amateur'=>'Amatorski','youth'=>'Młodzieżowy','elite'=>'Elite'];
    public static array $WEIGHT_CLASSES = [
        '-46'  => 'Słomkowa (do 46 kg)',
        '-49'  => 'Papierowa (do 49 kg)',
        '-52'  => 'Kogucia (do 52 kg)',
        '-56'  => 'Lekka (do 56 kg)',
        '-60'  => 'Lekko-półśrednia (do 60 kg)',
        '-63'  => 'Półśrednia (do 63 kg)',
        '-67'  => 'Średnia (do 67 kg)',
        '-71'  => 'Półciężka lekka (do 71 kg)',
        '-75'  => 'Półciężka (do 75 kg)',
        '-80'  => 'Ciężka lekka (do 80 kg)',
        '-86'  => 'Ciężka (do 86 kg)',
        '-92'  => 'Ciężka plus (do 92 kg)',
        '+92'  => 'Super ciężka (ponad 92 kg)',
    ];
    public function listForClub(): array {
        $stmt = $this->db->prepare("SELECT br.*, m.first_name, m.last_name, m.member_number FROM boxing_results br JOIN members m ON m.id = br.member_id WHERE br.club_id = ? ORDER BY br.competition_date DESC, m.last_name");
        $stmt->execute([$this->clubId()]);
        return $stmt->fetchAll();
    }
}
