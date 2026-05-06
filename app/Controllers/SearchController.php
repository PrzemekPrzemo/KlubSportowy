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

        // Z.1 — Sekcje sportowe (klubowe)
        if ($clubId) {
            $stmt = $db->prepare(
                "SELECT cs.id, COALESCE(cs.name, s.name) AS name, s.icon, s.color
                   FROM club_sports cs
                   JOIN sports s ON s.id = cs.sport_id
                  WHERE cs.club_id = ?
                    AND (cs.name LIKE ? OR s.name LIKE ?)
                  LIMIT 5"
            );
            $stmt->execute([$clubId, $like, $like]);
            foreach ($stmt->fetchAll() as $r) {
                $results[] = [
                    'type'  => 'sport',
                    'label' => $r['name'],
                    'url'   => url('sports'),
                    'icon'  => $r['icon'] ?: 'bi-trophy',
                ];
            }
        }

        // Z.1 — Wpłaty (po reference / notes)
        if ($clubId) {
            $sql = "SELECT p.id, p.amount, p.payment_date, p.reference,
                           m.first_name, m.last_name, m.member_number
                    FROM payments p
                    JOIN members m ON m.id = p.member_id
                   WHERE p.club_id = ?
                     AND (p.reference LIKE ? OR p.notes LIKE ?
                          OR m.last_name LIKE ? OR m.first_name LIKE ?)
                   ORDER BY p.payment_date DESC
                   LIMIT 5";
            $stmt = $db->prepare($sql);
            $stmt->execute([$clubId, $like, $like, $like, $like]);
            foreach ($stmt->fetchAll() as $r) {
                $amt = number_format((float)$r['amount'], 2, ',', ' ') . ' zł';
                $results[] = [
                    'type'  => 'payment',
                    'label' => $r['last_name'] . ' ' . $r['first_name'] . ' — ' . $amt
                              . ' (' . substr($r['payment_date'], 0, 10) . ')',
                    'url'   => url('fees'),
                    'icon'  => 'bi-cash-coin',
                ];
            }
        }

        $this->json(['results' => $results]);
    }
}
