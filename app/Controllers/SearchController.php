<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\ClubContext;
use App\Helpers\Database;

/**
 * Globalny AJAX search — zwraca JSON z wynikami po zawodnikach,
 * wydarzeniach i treningach. Endpoint: GET /api/search?q=...
 */
class SearchController extends BaseController
{
    public function search(): void
    {
        Auth::requireLogin();
        $q = trim($_GET['q'] ?? '');
        if (mb_strlen($q) < 2) {
            $this->json(['results' => []]);
        }

        $clubId = ClubContext::current();
        $db     = Database::pdo();
        $like   = '%' . $q . '%';
        $results = [];

        // Zawodnicy
        $sql = "SELECT id, first_name, last_name, member_number, 'member' AS type
                FROM members WHERE (first_name LIKE ? OR last_name LIKE ? OR member_number LIKE ? OR email LIKE ?)";
        $params = [$like, $like, $like, $like];
        if ($clubId) { $sql .= " AND club_id = ?"; $params[] = $clubId; }
        $sql .= " LIMIT 5";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $results[] = [
                'type'  => 'member',
                'label' => $r['last_name'] . ' ' . $r['first_name'] . ' (#' . $r['member_number'] . ')',
                'url'   => url('members/' . $r['id']),
                'icon'  => 'bi-person',
            ];
        }

        // Wydarzenia
        $sql = "SELECT id, name, event_date, 'event' AS type
                FROM events WHERE name LIKE ?";
        $params = [$like];
        if ($clubId) { $sql .= " AND club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY event_date DESC LIMIT 5";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $results[] = [
                'type'  => 'event',
                'label' => $r['name'] . ' (' . substr($r['event_date'], 0, 10) . ')',
                'url'   => url('events'),
                'icon'  => 'bi-calendar-event',
            ];
        }

        // Treningi
        $sql = "SELECT id, name, start_time FROM trainings WHERE name LIKE ?";
        $params = [$like];
        if ($clubId) { $sql .= " AND club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY start_time DESC LIMIT 5";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt->fetchAll() as $r) {
            $results[] = [
                'type'  => 'training',
                'label' => $r['name'] . ' (' . substr($r['start_time'], 0, 10) . ')',
                'url'   => url('trainings/' . $r['id']),
                'icon'  => 'bi-stopwatch',
            ];
        }

        $this->json(['results' => $results]);
    }
}
