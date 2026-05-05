<?php

namespace App\Helpers\DemoSeeders;

use App\Helpers\Database;
use App\Sports\Equestrian\EquestrianArchetype;
use App\Sports\Support\BaseSportArchetype;

/**
 * Realistic demo seed dla equestrian. Tworzy:
 *   - 4 wlascicieli koni (2 czlonkowie klubu + 2 zewnetrzni)
 *   - 5 koni z paszportami PZJ + microchip + dyscyplinami
 *   - 4 zawodnikow z licencjami PZJ B/S1/S2/S3
 *   - 2 zawody (CSI Demo + CDN Demo)
 *   - 6 klas (3 per competition: skoki + ujezdzenie + WKKW)
 *   - 12 startow (4 per klasa avg)
 *   - 12 wynikow (1:1 z startami)
 *   - 5 szczepien (jedno per kon, valid 1 rok)
 *   - 8 sesji treningowych (2 per kon avg)
 *
 * Po seedzie demo klub jest gotowy do prezentacji potencjalnemu klientowi —
 * widzi pelna funkcjonalnosc panelu klubu jezdzieckiego.
 */
class EquestrianSeeder implements ArchetypeSeederInterface
{
    public function archetypeClass(): string
    {
        return EquestrianArchetype::class;
    }

    public function seed(int $clubId, BaseSportArchetype $archetype, array $counts = []): array
    {
        $db = Database::pdo();
        $created = [
            'horse' => 0, 'owner' => 0, 'rider' => 0, 'member' => 0,
            'competition' => 0, 'class' => 0, 'start' => 0, 'result' => 0,
            'health' => 0, 'training' => 0,
        ];

        // 1. Czlonkowie klubu (4 osoby — beda riderzy)
        $memberIds = $this->seedMembers($db, $clubId);
        $created['member'] = count($memberIds);

        // 2. Wlasciciele (2 zwiazani z czlonkami + 2 zewnetrzni)
        $ownerIds = $this->seedOwners($db, $clubId, $memberIds);
        $created['owner'] = count($ownerIds);

        // 3. Konie (5)
        $horseIds = $this->seedHorses($db, $clubId, $ownerIds);
        $created['horse'] = count($horseIds);

        // 4. Riderzy (4 zawodnikow z licencjami PZJ)
        $riderIds = $this->seedRiders($db, $clubId, $memberIds);
        $created['rider'] = count($riderIds);

        // 5. Zawody (2)
        $competitionIds = $this->seedCompetitions($db, $clubId);
        $created['competition'] = count($competitionIds);

        // 6. Klasy (3 per competition)
        $classIds = $this->seedClasses($db, $competitionIds);
        $created['class'] = count($classIds);

        // 7. Starty (4 per klasa avg)
        $startIds = $this->seedStarts($db, $clubId, $classIds, $riderIds, $horseIds);
        $created['start'] = count($startIds);

        // 8. Wyniki (1:1 z startami)
        $created['result'] = $this->seedResults($db, $clubId, $startIds, $riderIds, $horseIds, $competitionIds, $classIds);

        // 9. Status zdrowotny (1 szczepienie per kon)
        $created['health'] = $this->seedHealth($db, $clubId, $horseIds);

        // 10. Treningi (2 sesje per kon avg)
        $created['training'] = $this->seedTraining($db, $clubId, $horseIds, $memberIds);

        return ['created' => $created];
    }

    private function seedMembers(\PDO $db, int $clubId): array
    {
        $names = [
            ['Anna',     'Kowalska'],
            ['Piotr',    'Nowak'],
            ['Maria',    'Wiśniewska'],
            ['Tomasz',   'Dąbrowski'],
        ];
        $ids = [];
        foreach ($names as $i => [$first, $last]) {
            $stmt = $db->prepare(
                "INSERT INTO members (club_id, first_name, last_name, status, member_number, join_date)
                 VALUES (?, ?, ?, 'aktywny', ?, CURDATE())"
            );
            try {
                $stmt->execute([$clubId, $first, $last, 'EQ-' . sprintf('%03d', $i + 1)]);
                $ids[] = (int)$db->lastInsertId();
            } catch (\Throwable) { /* skip */ }
        }
        return $ids;
    }

    private function seedOwners(\PDO $db, int $clubId, array $memberIds): array
    {
        $owners = [
            ['Anna Kowalska',     'member', $memberIds[0] ?? null, '+48 600 100 001'],
            ['Piotr Nowak',       'member', $memberIds[1] ?? null, '+48 600 100 002'],
            ['Stadnina Pegaz',    'extern', null,                  '+48 22 555 0001'],
            ['Hodowla Galop sp.', 'extern', null,                  '+48 22 555 0002'],
        ];
        $ids = [];
        foreach ($owners as [$name, $type, $memberId, $phone]) {
            $stmt = $db->prepare(
                "INSERT INTO equestrian_horse_owners (club_id, full_name, member_id, phone, city)
                 VALUES (?, ?, ?, ?, ?)"
            );
            try {
                $stmt->execute([$clubId, $name, $memberId, $phone, 'Warszawa']);
                $ids[] = (int)$db->lastInsertId();
            } catch (\Throwable) { /* skip */ }
        }
        return $ids;
    }

    private function seedHorses(\PDO $db, int $clubId, array $ownerIds): array
    {
        $horses = [
            ['Pegaz',  'KSP', 2018, 'gniada',     'gelding',  '900141001234567', 168, 'sportowa_srednia',  'jumping,eventing'],
            ['Burza',  'Hanowerski', 2016, 'siwa', 'mare',    '900141001234568', 172, 'sportowa_wysoka',   'dressage'],
            ['Iskra',  'Oldenburski', 2020, 'kara', 'mare',   '900141001234569', 165, 'sportowa_niska',    'jumping'],
            ['Wicher', 'Holsztynski', 2017, 'kasztanowy', 'gelding', '900141001234570', 170, 'sportowa_srednia', 'eventing,dressage'],
            ['Kometa', 'KWPN',  2019, 'siwa',    'mare',     '900141001234571', 167, 'sportowa_srednia',  'jumping,dressage'],
        ];
        $ids = [];
        foreach ($horses as $i => [$name, $breed, $birth, $color, $sex, $chip, $height, $sportClass, $disciplines]) {
            $ownerId = $ownerIds[$i % count($ownerIds)] ?? null;
            $stmt = $db->prepare(
                "INSERT INTO equestrian_horses
                 (club_id, name, breed, birth_year, color, sex, microchip, pzj_passport_no, height_cm, sport_class, discipline_focus, status, owner_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)"
            );
            try {
                $stmt->execute([
                    $clubId, $name, $breed, $birth, $color, $sex, $chip,
                    'PZJ-' . (1000 + $i), $height, $sportClass, $disciplines, $ownerId,
                ]);
                $ids[] = (int)$db->lastInsertId();
            } catch (\Throwable) { /* skip */ }
        }
        return $ids;
    }

    private function seedRiders(\PDO $db, int $clubId, array $memberIds): array
    {
        $classes = ['B', 'S1', 'S2', 'S3'];
        $disciplines = ['jumping', 'dressage', 'eventing', 'jumping'];
        $ids = [];
        foreach ($memberIds as $i => $mid) {
            $stmt = $db->prepare(
                "INSERT INTO equestrian_riders
                 (club_id, member_id, license_no, license_class, license_valid_until, discipline_main, status)
                 VALUES (?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL ? MONTH), ?, 'aktywny')"
            );
            try {
                $stmt->execute([
                    $clubId, $mid,
                    'PZJ-LIC-' . sprintf('%05d', $i + 1),
                    $classes[$i % 4],
                    rand(2, 14),  // license valid 2-14 months from now (some near expiry)
                    $disciplines[$i % 4],
                ]);
                $ids[] = (int)$db->lastInsertId();
            } catch (\Throwable) { /* skip */ }
        }
        return $ids;
    }

    private function seedCompetitions(\PDO $db, int $clubId): array
    {
        $comps = [
            ['Demo CSI Klub Olsztyn', 'CSI',    '+5 days',  '+6 days',  'Olsztyn',    'zaplanowane'],
            ['Demo CDN Mistrzostwa',  'CDN',    '-30 days', '-29 days', 'Sopot',      'zakonczone'],
        ];
        $ids = [];
        foreach ($comps as [$name, $level, $from, $to, $location, $status]) {
            $stmt = $db->prepare(
                "INSERT INTO equestrian_competitions
                 (club_id, name, date_from, date_to, location, level, status)
                 VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY), DATE_ADD(CURDATE(), INTERVAL ? DAY), ?, ?, ?)"
            );
            $fromDays = (int)$from; // string '+5 days' / '-30 days' won't work, parse manually
            $fromDays = (int)preg_replace('/[^-0-9]/', '', $from);
            $toDays   = (int)preg_replace('/[^-0-9]/', '', $to);
            try {
                $stmt->execute([$clubId, $name, $fromDays, $toDays, $location, $level, $status]);
                $ids[] = (int)$db->lastInsertId();
            } catch (\Throwable) { /* skip */ }
        }
        return $ids;
    }

    private function seedClasses(\PDO $db, array $competitionIds): array
    {
        $classes = [
            ['Klasa LL — 110cm',    'jumping',  'LL',         110,  90, 30],
            ['Klasa L — 120cm',     'jumping',  'L',          120,  85, 25],
            ['Ujeżdżenie N',         'dressage', 'N1',         null, null, 20],
            ['WKKW — Cross',         'eventing', 'L',          null, null, 15],
            ['Klasa P — 130cm',     'jumping',  'P',          130,  80, 20],
            ['Ujeżdżenie Grand Prix','dressage', 'Grand Prix', null, null, 12],
        ];
        $ids = [];
        foreach ($classes as $i => [$name, $disc, $level, $height, $time, $maxStart]) {
            $compId = $competitionIds[$i % count($competitionIds)];
            $stmt = $db->prepare(
                "INSERT INTO equestrian_competition_classes
                 (competition_id, class_no, name, discipline, class_level, fence_height_cm, time_allowed_s, max_starters, prize_pool)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            try {
                $stmt->execute([$compId, $i + 1, $name, $disc, $level, $height, $time, $maxStart, 1500.00]);
                $ids[] = (int)$db->lastInsertId();
            } catch (\Throwable) { /* skip */ }
        }
        return $ids;
    }

    private function seedStarts(\PDO $db, int $clubId, array $classIds, array $riderIds, array $horseIds): array
    {
        $ids = [];
        $startNo = 1;
        foreach ($classIds as $ci => $classId) {
            // 4 starty per klasa (lub mniej gdy malo riderow)
            for ($j = 0; $j < 4 && $j < count($riderIds); $j++) {
                $riderId = $riderIds[$j];
                $horseId = $horseIds[$j % count($horseIds)];
                $stmt = $db->prepare(
                    "INSERT INTO equestrian_starts
                     (club_id, competition_class_id, rider_id, horse_id, start_no, status)
                     VALUES (?, ?, ?, ?, ?, 'startuje')"
                );
                try {
                    $stmt->execute([$clubId, $classId, $riderId, $horseId, $startNo++]);
                    $ids[] = (int)$db->lastInsertId();
                } catch (\Throwable) { /* skip */ }
            }
        }
        return $ids;
    }

    private function seedResults(\PDO $db, int $clubId, array $startIds, array $riderIds, array $horseIds, array $competitionIds, array $classIds): int
    {
        $count = 0;
        foreach ($startIds as $i => $startId) {
            // For each start — wynik z miejscem 1-N
            $rank = ($i % 4) + 1;
            $score = 80.0 - ($rank - 1) * 5.0; // 80 / 75 / 70 / 65
            $faults = $rank === 1 ? 0 : ($rank * 4);
            $time = 65.0 + ($rank * 1.5);

            // Find competition + class for this start
            $stmt = $db->prepare(
                "SELECT s.competition_class_id, cc.competition_id, s.rider_id, s.horse_id, s.member_id
                 FROM equestrian_starts s
                 JOIN equestrian_competition_classes cc ON cc.id = s.competition_class_id
                 WHERE s.id = ?"
            );
            $stmt->execute([$startId]);
            $row = $stmt->fetch();
            if (!$row) continue;

            $stmt2 = $db->prepare(
                "INSERT INTO equestrian_results
                 (club_id, member_id, competition_id, competition_class_id, start_id, rider_id, horse_id,
                  competition_name, competition_date, discipline, class_level, placement, score, faults, time_seconds)
                 SELECT ?, COALESCE(s.member_id,
                            (SELECT member_id FROM equestrian_riders WHERE id = s.rider_id)),
                        cc.competition_id, s.competition_class_id, s.id, s.rider_id, s.horse_id,
                        c.name, c.date_from, cc.discipline, cc.class_level, ?, ?, ?, ?
                 FROM equestrian_starts s
                 JOIN equestrian_competition_classes cc ON cc.id = s.competition_class_id
                 JOIN equestrian_competitions c ON c.id = cc.competition_id
                 WHERE s.id = ?"
            );
            try {
                $stmt2->execute([$clubId, $rank, $score, $faults, $time, $startId]);
                $count++;
            } catch (\Throwable) { /* skip */ }
        }
        return $count;
    }

    private function seedHealth(\PDO $db, int $clubId, array $horseIds): int
    {
        $count = 0;
        foreach ($horseIds as $i => $horseId) {
            $stmt = $db->prepare(
                "INSERT INTO equestrian_horse_health
                 (club_id, horse_id, event_date, event_type, description, vet_name, vet_license, valid_until, cost)
                 VALUES (?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY), 'szczepienie_grypa',
                         'Coroczna szczepionka grypa konska', 'lek. wet. Jan Kowalski', 'PWZW-' || ?,
                         DATE_ADD(CURDATE(), INTERVAL 12 MONTH), 250.00)"
            );
            try {
                $stmt->execute([$clubId, $horseId, $i * 30, sprintf('%05d', 1000 + $i)]);
                $count++;
            } catch (\Throwable) {
                // Fallback bez SQL || (MariaDB-specific) — uzyj CONCAT
                $stmt2 = $db->prepare(
                    "INSERT INTO equestrian_horse_health
                     (club_id, horse_id, event_date, event_type, description, vet_name, vet_license, valid_until, cost)
                     VALUES (?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY), 'szczepienie_grypa',
                             'Coroczna szczepionka grypa konska', 'lek. wet. Jan Kowalski', ?,
                             DATE_ADD(CURDATE(), INTERVAL 12 MONTH), 250.00)"
                );
                try {
                    $stmt2->execute([$clubId, $horseId, $i * 30, 'PWZW-' . sprintf('%05d', 1000 + $i)]);
                    $count++;
                } catch (\Throwable) { /* skip */ }
            }
        }
        return $count;
    }

    private function seedTraining(\PDO $db, int $clubId, array $horseIds, array $memberIds): int
    {
        $types = ['ujezdzenie_w_jezdzcu', 'lonza', 'skok', 'spacer'];
        $intensities = ['lekka', 'umiarkowana', 'intensywna'];
        $count = 0;
        foreach ($horseIds as $i => $horseId) {
            // 2 sesje per kon (rozne typy, rozne dni)
            for ($j = 0; $j < 2; $j++) {
                $memberId = $memberIds[($i + $j) % max(1, count($memberIds))] ?? null;
                $type = $types[($i + $j) % 4];
                $intensity = $intensities[($i + $j) % 3];
                $stmt = $db->prepare(
                    "INSERT INTO equestrian_horse_training
                     (club_id, horse_id, member_id, training_date, duration_min, training_type, intensity, arena, behavior)
                     VALUES (?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY), ?, ?, ?, ?, 'spokojny')"
                );
                try {
                    $stmt->execute([
                        $clubId, $horseId, $memberId,
                        ($i * 7 + $j * 3),  // dni temu
                        45 + ($j * 15),      // 45-60 min
                        $type, $intensity,
                        'kryta',
                    ]);
                    $count++;
                } catch (\Throwable) { /* skip */ }
            }
        }
        return $count;
    }
}
