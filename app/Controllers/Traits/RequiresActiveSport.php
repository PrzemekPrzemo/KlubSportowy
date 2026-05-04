<?php

namespace App\Controllers\Traits;

use App\Helpers\ClubContext;
use App\Helpers\Database;
use App\Helpers\Session;

/**
 * Trait dla kontrolerów sportowych — upewnia się, że dany sport
 * jest aktywny (cs.is_active=1) w bieżącym klubie.
 *
 * Użycie w konstruktorze kontrolera sportowego:
 *   $this->requireSportActive('swimming');
 */
trait RequiresActiveSport
{
    protected function requireSportActive(string $sportKey): void
    {
        $clubId = ClubContext::current();
        if (!$clubId) return; // wcześniejsze sprawdzenie requireClubContext()

        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT cs.is_active
             FROM club_sports cs
             JOIN sports s ON s.id = cs.sport_id
             WHERE cs.club_id = ? AND s.`key` = ?
             LIMIT 1"
        );
        $stmt->execute([$clubId, $sportKey]);
        $row = $stmt->fetch();

        if (!$row) {
            Session::flash('error', 'Sekcja sportowa "' . $sportKey . '" nie jest aktywna w tym klubie. Włącz ją w ustawieniach klubu.');
            header('Location: ' . url('sports'));
            exit;
        }
        if ((int)$row['is_active'] === 0) {
            Session::flash('error', 'Sekcja "' . $sportKey . '" jest tymczasowo wyłączona w tym klubie.');
            header('Location: ' . url('sports'));
            exit;
        }
    }
}
