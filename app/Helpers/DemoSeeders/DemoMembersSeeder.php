<?php

declare(strict_types=1);

namespace App\Helpers\DemoSeeders;

use App\Helpers\Database;
use App\Helpers\Encryption;
use PDO;

/**
 * Seeds members with realistic data: PESEL (correct checksum, fake-base year),
 * encrypted email/phone (if Encryption is configured — otherwise plaintext),
 * member_sports assignments, federation licenses for a couple of members.
 *
 * Reads from $context:
 *   - club_id, club_sport_ids, sport_ids, target_members, admin_user_id
 *
 * Writes to $context:
 *   - member_ids        : int[]
 *   - members_per_sport : array<string, int[]>  sport_key => member_ids that play it
 */
final class DemoMembersSeeder
{
    public static function seed(array &$context): array
    {
        $db = Database::pdo();
        $clubId = (int)$context['club_id'];
        $target = (int)$context['target_members'];
        $sportKeys = array_keys($context['club_sport_ids']);
        $createdBy = (int)($context['admin_user_id'] ?? 0) ?: null;

        if (empty($sportKeys)) {
            $context['member_ids'] = [];
            $context['members_per_sport'] = [];
            return ['members' => 0, 'sport_assignments' => 0, 'licenses' => 0];
        }

        // Age distribution: 30% kids (8-14), 40% youth (15-22), 30% adults (23-60).
        $today = new \DateTimeImmutable('today');
        $bands = [
            ['weight' => 30, 'min' => 8,  'max' => 14],
            ['weight' => 40, 'min' => 15, 'max' => 22],
            ['weight' => 30, 'min' => 23, 'max' => 60],
        ];

        // Pre-existing member counter so multiple runs do not collide on member_number.
        $existing = (int)$db->query(
            "SELECT COUNT(*) FROM members WHERE club_id={$clubId}"
        )->fetchColumn();

        $insertMember = $db->prepare(
            "INSERT INTO members (
                club_id, member_number, first_name, last_name, pesel,
                birth_date, gender, email, phone,
                address_street, address_city, address_postal,
                join_date, status, notes, created_by, created_at
             ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,DATE_SUB(CURDATE(), INTERVAL ? DAY),?,?,?,NOW())"
        );

        $insertMemberSport = $db->prepare(
            "INSERT IGNORE INTO member_sports (member_id, club_sport_id, joined_at, is_active)
             VALUES (?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY), 1)"
        );

        $memberIds = [];
        $membersPerSport = array_fill_keys($sportKeys, []);
        $sportAssignmentCount = 0;

        for ($i = 0; $i < $target; $i++) {
            // 1) Demographics
            $gender = ($i % 2 === 0) ? 'M' : 'K';
            $first  = $gender === 'M'
                ? DemoNames::pick(DemoNames::MALE_FIRST,   $i, 13)
                : DemoNames::pick(DemoNames::FEMALE_FIRST, $i, 7);
            $last = DemoNames::pick(DemoNames::LAST, $i, 23);

            $band = self::pickBand($bands, $i);
            $age  = $band['min'] + ($i % max(1, $band['max'] - $band['min']));
            // Base year 1990 + age-offset trick to avoid matching real PESELs.
            $birth = $today->modify("-{$age} years")->modify('+' . (($i * 7) % 360) . ' days');

            $pesel = DemoNames::generatePesel($birth, $gender, 100 + $i);
            $email = DemoNames::emailSlug($first, $last, $clubId * 1000 + $existing + $i);
            $phone = sprintf('+48 %d', 500000000 + (($clubId * 9973 + $i * 31) % 499999999));
            $street = sprintf('%s %d/%d',
                DemoNames::pick(DemoNames::STREETS, $i, 5),
                1 + ($i % 199),
                1 + ($i % 47)
            );
            $city = DemoNames::pick(DemoNames::CITIES, $i, 0);
            $postal = sprintf('%02d-%03d', ($i % 99), ($i * 7) % 999);

            // Encrypt email/phone if encryption is configured — otherwise plain.
            $emailStored = Encryption::isConfigured() ? (Encryption::encrypt($email) ?? $email) : $email;
            $phoneStored = Encryption::isConfigured() ? (Encryption::encrypt($phone) ?? $phone) : $phone;

            // Status: mostly active, ~8% suspended, ~2% urlop
            $status = match (true) {
                ($i % 25) === 0 => 'urlop',
                ($i % 12) === 0 => 'zawieszony',
                default => 'aktywny',
            };

            $joinedDaysAgo = 30 + (($i * 11) % 1200);
            $memberNumber = sprintf('DEMO-%d-%05d', $clubId, $existing + $i + 1);
            $notes = '[DEMO] Wygenerowany przez DemoMembersSeeder — usun przez --clean.';

            $insertMember->execute([
                $clubId, $memberNumber, $first, $last, $pesel,
                $birth->format('Y-m-d'), $gender, $emailStored, $phoneStored,
                $street, $city, $postal,
                $joinedDaysAgo, $status, $notes, $createdBy,
            ]);
            $mid = (int)$db->lastInsertId();
            $memberIds[] = $mid;

            // 2) Assign 1-3 sports per member (cross-sport showcase = some in 2-3)
            $sportPicks = self::pickSports($sportKeys, $i, $age);
            foreach ($sportPicks as $sk) {
                $csId = $context['club_sport_ids'][$sk];
                $insertMemberSport->execute([$mid, $csId, $joinedDaysAgo - 5]);
                $membersPerSport[$sk][] = $mid;
                $sportAssignmentCount++;
            }
        }

        $context['member_ids'] = $memberIds;
        $context['members_per_sport'] = $membersPerSport;

        // 3) Federation licenses for ~2 members per sport
        $licenseCount = self::seedLicenses($db, $clubId, $context, $createdBy);

        return [
            'members'           => count($memberIds),
            'sport_assignments' => $sportAssignmentCount,
            'licenses'          => $licenseCount,
        ];
    }

    private static function pickBand(array $bands, int $i): array
    {
        // Cycle 0..99 deterministically by index, then pick band by cumulative weight.
        $roll = ($i * 17 + 3) % 100;
        $cum = 0;
        foreach ($bands as $b) {
            $cum += $b['weight'];
            if ($roll < $cum) return $b;
        }
        return $bands[array_key_last($bands)];
    }

    /**
     * Pick 1-3 sports for a member. ~25% get 2 sports, ~10% get 3 (cross-sport showcase).
     */
    private static function pickSports(array $sportKeys, int $i, int $age): array
    {
        $rnd = ($i * 7 + 11) % 100;
        $count = match (true) {
            $rnd < 10 => 3,
            $rnd < 35 => 2,
            default   => 1,
        };
        $count = min($count, count($sportKeys));

        // Deterministic offset so members spread across sports.
        $start = $i % count($sportKeys);
        $out = [];
        for ($j = 0; $j < $count; $j++) {
            $out[] = $sportKeys[($start + $j) % count($sportKeys)];
        }
        return array_values(array_unique($out));
    }

    private static function seedLicenses(PDO $db, int $clubId, array &$context, ?int $createdBy): int
    {
        // Map sport_key => federation code (for licensure)
        $sportToFed = [
            'football'   => 'PZPN',
            'basketball' => 'PZKosz',
            'volleyball' => 'PZPS',
            'swimming'   => 'PZP',
            'tennis'     => 'PZT',
            'handball'   => 'PZPR',
            'athletics'  => 'PZLA',
            'judo'       => 'PZJ',
            'karate'     => 'PZKarate',
        ];

        $fedIds = [];
        foreach ($db->query("SELECT id, code FROM federations")->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $fedIds[$r['code']] = (int)$r['id'];
        }

        $ins = $db->prepare(
            "INSERT INTO member_licenses
                (club_id, member_id, sport_id, federation_id, license_type, license_number, issue_date, valid_until, status, created_by, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,NOW())"
        );

        $count = 0;
        foreach ($context['members_per_sport'] as $sportKey => $members) {
            if (!isset($context['sport_ids'][$sportKey])) continue;
            $sportId = $context['sport_ids'][$sportKey];
            $fedCode = $sportToFed[$sportKey] ?? null;
            $fedId   = $fedCode !== null ? ($fedIds[$fedCode] ?? null) : null;

            // Take first 2 members of this sport
            $take = array_slice($members, 0, 2);
            foreach ($take as $idx => $mid) {
                $issue = (new \DateTimeImmutable('today'))->modify('-' . (60 + $idx * 30) . ' days');
                $validUntil = $issue->modify('+1 year');
                $licNo = strtoupper($fedCode ?? 'KLUB') . '-' . $clubId . '-' . $mid;
                $ins->execute([
                    $clubId, $mid, $sportId, $fedId, 'zawodnicza',
                    $licNo, $issue->format('Y-m-d'), $validUntil->format('Y-m-d'),
                    'aktywna', $createdBy,
                ]);
                $count++;
            }
        }
        return $count;
    }
}
