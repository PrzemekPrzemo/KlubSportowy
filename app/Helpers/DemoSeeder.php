<?php

namespace App\Helpers;

use PDO;

class DemoSeeder
{
    /**
     * Basic seed — legacy fallback (10 members, 3 payments, 2 trainings, 1 event).
     */
    public static function seed(int $clubId): void
    {
        $db = Database::pdo();

        $memberIds = [];
        $members = [
            ['Jan',      'Kowalski',    'jan.kowalski@demo.test'],
            ['Anna',     'Nowak',       'anna.nowak@demo.test'],
            ['Piotr',    'Wisniewski',  'piotr.wisniewski@demo.test'],
            ['Maria',    'Wojcik',      'maria.wojcik@demo.test'],
            ['Tomasz',   'Kaminski',    'tomasz.kaminski@demo.test'],
            ['Katarzyna','Lewandowska', 'katarzyna.lewandowska@demo.test'],
            ['Michal',   'Zielinski',   'michal.zielinski@demo.test'],
            ['Agnieszka','Szymanska',   'agnieszka.szymanska@demo.test'],
            ['Krzysztof','Wozniak',     'krzysztof.wozniak@demo.test'],
            ['Monika',   'Dabrowski',   'monika.dabrowski@demo.test'],
        ];

        $stmt = $db->prepare(
            "INSERT INTO members (club_id, member_number, first_name, last_name, email, birth_date, gender, status, join_date, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'aktywny', CURDATE(), NOW())"
        );
        foreach ($members as $i => $m) {
            $stmt->execute([$clubId, 'DEMO-' . $clubId . '-' . ($i + 1), $m[0], $m[1], $m[2], (1985 + ($i % 15)) . '-06-15', ($i % 2 === 0) ? 'M' : 'K']);
            $memberIds[] = (int)$db->lastInsertId();
        }

        if (count($memberIds) >= 3) {
            $payStmt = $db->prepare(
                "INSERT INTO payments (club_id, member_id, amount, payment_date, method, status, description, created_at)
                 VALUES (?, ?, ?, CURDATE(), 'przelew', 'oplacone', ?, NOW())"
            );
            $payStmt->execute([$clubId, $memberIds[0], 150.00, 'Skladka demo - styczen']);
            $payStmt->execute([$clubId, $memberIds[1], 150.00, 'Skladka demo - styczen']);
            $payStmt->execute([$clubId, $memberIds[2], 200.00, 'Skladka demo - luty']);
        }

        $trainStmt = $db->prepare(
            "INSERT INTO trainings (club_id, title, training_date, start_time, end_time, location, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $trainStmt->execute([$clubId, 'Trening ogolnorozwojowy', date('Y-m-d', strtotime('next monday')), '17:00:00', '19:00:00', 'Hala sportowa']);
        $trainStmt->execute([$clubId, 'Trening specjalistyczny', date('Y-m-d', strtotime('next wednesday')), '18:00:00', '20:00:00', 'Boisko glowne']);

        $eventStmt = $db->prepare(
            "INSERT INTO events (club_id, title, event_date, location, description, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $eventStmt->execute([$clubId, 'Turniej demonstracyjny', date('Y-m-d', strtotime('next saturday')), 'Stadion Miejski', 'Przykladowe wydarzenie wygenerowane automatycznie dla demo.']);
    }

    /**
     * Enhanced seed with configurable sports, modules and data volume.
     *
     * @param array $config {
     *   sports: string[]  — sport keys to activate (empty = no sports)
     *   modules: string[] — module keys to enable (gallery/messages/bookings/analytics/shop/livestream)
     *   volume: string    — 'basic'(5)|'standard'(10)|'full'(25)
     * }
     */
    public static function seedEnhanced(int $clubId, array $config): void
    {
        $db      = Database::pdo();
        $sports  = array_values(array_filter((array)($config['sports']  ?? [])));
        $modules = array_values(array_filter((array)($config['modules'] ?? [])));
        $volume  = in_array($config['volume'] ?? '', ['basic', 'standard', 'full']) ? $config['volume'] : 'standard';

        $memberCount  = match ($volume) { 'basic' => 5, 'full' => 25, default => 10 };
        $payCount     = match ($volume) { 'basic' => 3, 'full' => 20, default => 8 };
        $trainCount   = match ($volume) { 'basic' => 2, 'full' => 10, default => 5 };
        $eventCount   = match ($volume) { 'basic' => 1, 'full' => 5,  default => 3 };

        // ── 1. Activate sports ────────────────────────────────────────────
        $sportIds = [];
        if (!empty($sports)) {
            $placeholders = implode(',', array_fill(0, count($sports), '?'));
            $stmt = $db->prepare("SELECT id, `key`, name FROM sports WHERE `key` IN ({$placeholders}) AND is_active = 1");
            $stmt->execute($sports);
            $sportRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $csStmt = $db->prepare(
                "INSERT IGNORE INTO club_sports (club_id, sport_id, name, is_active, created_at) VALUES (?,?,?,1,NOW())"
            );
            foreach ($sportRows as $sr) {
                $csStmt->execute([$clubId, $sr['id'], $sr['name']]);
                $sportIds[] = (int)$sr['id'];
            }
        }

        // ── 2. Module flags ───────────────────────────────────────────────
        $allModules = ['gallery', 'messages', 'bookings', 'analytics', 'shop', 'livestream'];
        $settingStmt = $db->prepare(
            "INSERT INTO club_settings (club_id, `key`, value, type) VALUES (?,?,?,'boolean')
             ON DUPLICATE KEY UPDATE value = VALUES(value)"
        );
        foreach ($allModules as $mod) {
            $settingStmt->execute([$clubId, 'module_' . $mod, in_array($mod, $modules) ? '1' : '0']);
        }

        // ── 3. Store demo config ──────────────────────────────────────────
        $db->prepare(
            "INSERT INTO club_settings (club_id, `key`, value, type) VALUES (?,?,?,'json')
             ON DUPLICATE KEY UPDATE value = VALUES(value)"
        )->execute([$clubId, 'demo_config', json_encode(['sports' => $sports, 'modules' => $modules, 'volume' => $volume], JSON_UNESCAPED_UNICODE)]);

        // ── 4. Members ────────────────────────────────────────────────────
        $memberPool = [
            ['Jan',        'Kowalski',       'M'], ['Anna',      'Nowak',          'K'],
            ['Piotr',      'Wisniewski',      'M'], ['Maria',     'Wojcik',         'K'],
            ['Tomasz',     'Kaminski',        'M'], ['Katarzyna', 'Lewandowska',    'K'],
            ['Michal',     'Zielinski',       'M'], ['Agnieszka', 'Szymanska',      'K'],
            ['Krzysztof',  'Wozniak',         'M'], ['Monika',    'Dabrowska',      'K'],
            ['Marek',      'Kozlowski',       'M'], ['Joanna',    'Jankowska',      'K'],
            ['Lukasz',     'Wojciechowski',   'M'], ['Magdalena', 'Kwiatkowska',    'K'],
            ['Adam',       'Kaczmarek',       'M'], ['Paulina',   'Piotrowska',     'K'],
            ['Robert',     'Grabowski',       'M'], ['Dominika',  'Pawlowska',      'K'],
            ['Marcin',     'Michalski',       'M'], ['Aleksandra','Adamska',         'K'],
            ['Pawel',      'Malinowski',      'M'], ['Natalia',   'Stepniak',       'K'],
            ['Kamil',      'Nowakowski',      'M'], ['Karolina',  'Wisniewska',     'K'],
            ['Rafal',      'Paczkowski',      'M'],
        ];

        $memberStmt = $db->prepare(
            "INSERT INTO members (club_id, member_number, first_name, last_name, email, birth_date, gender, status, join_date, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY), NOW())"
        );
        $memberIds = [];
        $statuses  = ['aktywny', 'aktywny', 'aktywny', 'zawieszony', 'wykreslony'];
        for ($i = 0; $i < min($memberCount, count($memberPool)); $i++) {
            [$fn, $ln, $g] = $memberPool[$i];
            $status    = $volume === 'full' ? $statuses[$i % count($statuses)] : 'aktywny';
            $birthYear = 1980 + ($i % 20);
            $joinedAgo = 30 + ($i * 15);
            $email     = strtolower($fn . '.' . $ln) . '@demo.test';
            $memberStmt->execute([$clubId, 'DEMO-' . $clubId . '-' . ($i + 1), $fn, $ln, $email, "{$birthYear}-0" . (($i % 9) + 1) . '-15', $g, $status, $joinedAgo]);
            $memberIds[] = (int)$db->lastInsertId();
        }

        // ── 5. Fee rates ──────────────────────────────────────────────────
        $feeStmt = $db->prepare(
            "INSERT INTO fee_rates (club_id, sport_id, name, amount, period, fee_type, is_active, created_at)
             VALUES (?, ?, ?, ?, 'monthly', 'skladka', 1, NOW())"
        );
        if (!empty($sportIds)) {
            foreach ($sportIds as $sid) {
                $feeStmt->execute([$clubId, $sid, 'Składka miesięczna', 120.00]);
            }
        } else {
            $feeStmt->execute([$clubId, null, 'Składka miesięczna', 120.00]);
        }

        // ── 6. Payments ───────────────────────────────────────────────────
        if (!empty($memberIds)) {
            $payStmt = $db->prepare(
                "INSERT INTO payments (club_id, member_id, amount, payment_date, method, status, description, created_at)
                 VALUES (?, ?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY), 'przelew', 'oplacone', ?, NOW())"
            );
            $months = ['styczen', 'luty', 'marzec', 'kwiecien', 'maj', 'czerwiec', 'lipiec', 'sierpien', 'wrzesien', 'pazdziernik'];
            for ($i = 0; $i < min($payCount, count($memberIds) * 2); $i++) {
                $mid = $memberIds[$i % count($memberIds)];
                $payStmt->execute([$clubId, $mid, 120.00, $i * 3, 'Składka demo - ' . $months[$i % count($months)]]);
            }
        }

        // ── 7. Trainings ──────────────────────────────────────────────────
        $trainStmt = $db->prepare(
            "INSERT INTO trainings (club_id, title, training_date, start_time, end_time, location, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $trainTitles   = ['Trening ogólnorozwojowy', 'Trening techniczny', 'Trening siłowy', 'Trening taktyczny', 'Trening kondycyjny', 'Trening regeneracyjny', 'Trening szybkościowy', 'Trening wytrzymałościowy', 'Trening grupowy', 'Trening indywidualny'];
        $trainHours    = ['07:00:00', '10:00:00', '15:00:00', '17:00:00', '18:00:00', '19:00:00'];
        $trainLocations = ['Hala sportowa', 'Boisko główne', 'Sala gimnastyczna', 'Stadion miejski', 'Centrum sportowe'];
        for ($i = 0; $i < $trainCount; $i++) {
            $daysOffset = ($i % 2 === 0) ? ($i + 1) : -($i);
            $trainDate  = date('Y-m-d', strtotime("+{$daysOffset} days"));
            $startH     = $trainHours[$i % count($trainHours)];
            [$hh, $mm]  = explode(':', $startH);
            $endH       = sprintf('%02d:%s:00', (int)$hh + 2, $mm);
            $trainStmt->execute([
                $clubId,
                $trainTitles[$i % count($trainTitles)],
                $trainDate,
                $startH,
                $endH,
                $trainLocations[$i % count($trainLocations)],
            ]);
        }

        // ── 8. Events ─────────────────────────────────────────────────────
        $eventStmt = $db->prepare(
            "INSERT INTO events (club_id, title, event_date, location, description, created_at)
             VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY), ?, ?, NOW())"
        );
        $eventTitles = ['Turniej demonstracyjny', 'Zawody regionalne', 'Dzień sportu', 'Mecz towarzyski', 'Gala sportowca'];
        $eventLocs   = ['Stadion Miejski', 'Hala sportowa', 'Centrum sportowe', 'Obiekt zewnętrzny', 'Boisko główne'];
        for ($i = 0; $i < $eventCount; $i++) {
            $eventStmt->execute([
                $clubId,
                $eventTitles[$i % count($eventTitles)],
                7 + ($i * 14),
                $eventLocs[$i % count($eventLocs)],
                'Przykładowe wydarzenie wygenerowane automatycznie dla prezentacji systemu ClubDesk.',
            ]);
        }

        // ── 9. Announcements (standard+) ─────────────────────────────────
        if (in_array($volume, ['standard', 'full'])) {
            $annStmt = $db->prepare(
                "INSERT INTO announcements (club_id, sport_id, title, content, priority, target, published, created_at)
                 VALUES (?, ?, ?, ?, ?, 'members', 1, NOW())"
            );
            if (!empty($sportIds)) {
                foreach ($sportIds as $idx => $sid) {
                    $annStmt->execute([
                        $clubId, $sid,
                        'Ważna informacja dla sekcji',
                        'To jest przykładowe ogłoszenie wygenerowane automatycznie w ramach demo systemu ClubDesk.',
                        $idx === 0 ? 'important' : 'normal',
                    ]);
                }
            } else {
                $annStmt->execute([$clubId, null, 'Ogłoszenie klubowe', 'Witamy w systemie ClubDesk! To jest przykładowe ogłoszenie.', 'normal']);
            }
        }

        // ── 10. Gallery albums (full + gallery module) ─────────────────────
        if ($volume === 'full' && in_array('gallery', $modules)) {
            $albumStmt = $db->prepare(
                "INSERT INTO gallery_albums (club_id, sport_id, title, description, is_public, created_at)
                 VALUES (?, ?, ?, ?, 1, NOW())"
            );
            if (!empty($sportIds)) {
                foreach ($sportIds as $sid) {
                    $albumStmt->execute([$clubId, $sid, 'Album sekcji — demo', 'Przykładowy album zdjęć wygenerowany dla prezentacji systemu.']);
                }
            } else {
                $albumStmt->execute([$clubId, null, 'Album klubowy — demo', 'Przykładowy album zdjęć wygenerowany dla prezentacji systemu.']);
            }
        }

        // ── 11. Medical exams (full) ───────────────────────────────────────
        if ($volume === 'full' && !empty($memberIds)) {
            $examStmt = $db->prepare(
                "INSERT INTO member_medical_exams (club_id, member_id, exam_type, exam_date, valid_until, doctor_name, created_at)
                 VALUES (?, ?, 'ogólne badanie sportowe', DATE_SUB(CURDATE(), INTERVAL ? DAY), DATE_ADD(CURDATE(), INTERVAL ? DAY), ?, NOW())"
            );
            $doctors = ['dr Jan Nowak', 'dr Anna Kowalska', 'dr Piotr Wiśniewski'];
            $count   = min(5, count($memberIds));
            for ($i = 0; $i < $count; $i++) {
                $examStmt->execute([$clubId, $memberIds[$i], 180 - ($i * 10), 185 + ($i * 30), $doctors[$i % count($doctors)]]);
            }
        }
    }
}
