<?php

namespace App\Helpers;

use PDO;

class DemoSeeder
{
    /**
     * Seed demo data for a given club.
     * Creates 10 members, 3 payments, 2 trainings, and 1 event.
     */
    public static function seed(int $clubId): void
    {
        $db = Database::pdo();

        // --- 10 example members ---
        $memberIds = [];
        $members = [
            ['Jan',     'Kowalski',   'jan.kowalski@demo.test'],
            ['Anna',    'Nowak',      'anna.nowak@demo.test'],
            ['Piotr',   'Wisniewski', 'piotr.wisniewski@demo.test'],
            ['Maria',   'Wojcik',     'maria.wojcik@demo.test'],
            ['Tomasz',  'Kaminski',   'tomasz.kaminski@demo.test'],
            ['Katarzyna','Lewandowska','katarzyna.lewandowska@demo.test'],
            ['Michal',  'Zielinski',  'michal.zielinski@demo.test'],
            ['Agnieszka','Szymanska', 'agnieszka.szymanska@demo.test'],
            ['Krzysztof','Wozniak',   'krzysztof.wozniak@demo.test'],
            ['Monika',  'Dabrowski',  'monika.dabrowski@demo.test'],
        ];

        $stmt = $db->prepare(
            "INSERT INTO members (club_id, first_name, last_name, email, birth_date, gender, status, joined_date, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 'aktywny', CURDATE(), NOW())"
        );

        foreach ($members as $i => $m) {
            $birthYear = 1985 + ($i % 15);
            $gender = ($i % 2 === 0) ? 'M' : 'K';
            $stmt->execute([
                $clubId,
                $m[0],
                $m[1],
                $m[2],
                "{$birthYear}-06-15",
                $gender,
            ]);
            $memberIds[] = (int)$db->lastInsertId();
        }

        // --- 3 payments ---
        if (count($memberIds) >= 3) {
            $payStmt = $db->prepare(
                "INSERT INTO payments (club_id, member_id, amount, payment_date, method, status, description, created_at)
                 VALUES (?, ?, ?, CURDATE(), 'przelew', 'oplacone', ?, NOW())"
            );
            $payStmt->execute([$clubId, $memberIds[0], 150.00, 'Skladka demo - styczen']);
            $payStmt->execute([$clubId, $memberIds[1], 150.00, 'Skladka demo - styczen']);
            $payStmt->execute([$clubId, $memberIds[2], 200.00, 'Skladka demo - luty']);
        }

        // --- 2 trainings ---
        $trainStmt = $db->prepare(
            "INSERT INTO trainings (club_id, title, training_date, start_time, end_time, location, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $nextMonday = date('Y-m-d', strtotime('next monday'));
        $nextWednesday = date('Y-m-d', strtotime('next wednesday'));
        $trainStmt->execute([$clubId, 'Trening ogolnorozwojowy', $nextMonday, '17:00:00', '19:00:00', 'Hala sportowa']);
        $trainStmt->execute([$clubId, 'Trening specjalistyczny', $nextWednesday, '18:00:00', '20:00:00', 'Boisko glowne']);

        // --- 1 event ---
        $eventStmt = $db->prepare(
            "INSERT INTO events (club_id, title, event_date, location, description, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $nextSaturday = date('Y-m-d', strtotime('next saturday'));
        $eventStmt->execute([
            $clubId,
            'Turniej demonstracyjny',
            $nextSaturday,
            'Stadion Miejski',
            'Przykladowe wydarzenie wygenerowane automatycznie dla demo.',
        ]);
    }
}
