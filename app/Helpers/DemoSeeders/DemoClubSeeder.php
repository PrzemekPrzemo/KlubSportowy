<?php

declare(strict_types=1);

namespace App\Helpers\DemoSeeders;

use App\Helpers\Database;
use PDO;

/**
 * Seeds the demo club, branding, admin users, and active sport sections.
 *
 * Context in:
 *   - club_id   : optional int, reuses existing club instead of creating
 *   - scale     : 'small'|'medium'|'large'
 *   - sports    : optional explicit list of sport `key` values
 *
 * Context out (mutates input):
 *   - club_id        : int  — club we operate on
 *   - club_sport_ids : array<string,int> sport_key => club_sports.id
 *   - sport_ids      : array<string,int> sport_key => sports.id
 *   - user_ids       : array<string,int> role-label => users.id
 *   - admin_user_id  : int — main "zarzad" user (for created_by columns)
 *
 * Returns summary numbers.
 */
final class DemoClubSeeder
{
    /** @var array<string, array{sports: array<int,string>, members: int}> */
    private const SCALES = [
        'small'  => ['sports' => ['football'],                                                    'members' => 20],
        'medium' => ['sports' => ['football','basketball','volleyball'],                          'members' => 80],
        'large'  => ['sports' => ['football','basketball','volleyball','swimming','tennis'],      'members' => 250],
    ];

    public static function seed(array &$context): array
    {
        $db = Database::pdo();
        $scale = $context['scale'] ?? 'medium';
        $scaleCfg = self::SCALES[$scale] ?? self::SCALES['medium'];

        $sportKeys = $context['sports'] ?? $scaleCfg['sports'];
        $context['target_members'] = $context['target_members'] ?? $scaleCfg['members'];

        // ── 1. Club (create or reuse) ─────────────────────────────────────
        $clubId = (int)($context['club_id'] ?? 0);
        if ($clubId === 0) {
            $stmt = $db->prepare(
                "INSERT INTO clubs (name, short_name, city, email, phone, address, website, founded_year, is_active, created_at)
                 VALUES (?, ?, 'Warszawa', ?, '+48 22 555 0100', 'ul. Olimpijska 1, 00-001 Warszawa',
                         'https://azs-demo.example', 1985, 1, NOW())"
            );
            $suffix = date('Ymd-His');
            $stmt->execute([
                'AZS Warszawa Demo ' . $suffix,
                'AZS-' . $suffix,
                'kontakt+' . $suffix . '@azs-demo.example',
            ]);
            $clubId = (int)$db->lastInsertId();
        }
        $context['club_id'] = $clubId;

        // ── 2. Branding (idempotent upsert) ───────────────────────────────
        $subdomain = 'azs-demo-' . $clubId;
        $db->prepare(
            "INSERT INTO club_customization (club_id, primary_color, navbar_bg, accent_color, subdomain, motto)
             VALUES (?, '#1e40af', '#0f172a', '#f59e0b', ?, 'Sport. Pasja. Wspólnota.')
             ON DUPLICATE KEY UPDATE
                primary_color = VALUES(primary_color),
                navbar_bg     = VALUES(navbar_bg),
                accent_color  = VALUES(accent_color),
                motto         = VALUES(motto)"
        )->execute([$clubId, $subdomain]);

        // Subscription — give them Standard with 1y validity for a nice demo
        $planRow = $db->query("SELECT id FROM subscription_plans WHERE code='standard' LIMIT 1")
            ->fetch(PDO::FETCH_ASSOC);
        if ($planRow) {
            $db->prepare(
                "INSERT INTO club_subscriptions (club_id, plan_id, valid_until, status, billing_cycle)
                 VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 'active', 'yearly')
                 ON DUPLICATE KEY UPDATE plan_id=VALUES(plan_id), valid_until=VALUES(valid_until), status='active'"
            )->execute([$clubId, (int)$planRow['id']]);
        }

        // ── 3. Sports activation ──────────────────────────────────────────
        $placeholders = implode(',', array_fill(0, count($sportKeys), '?'));
        $sportRows = $db->prepare("SELECT id, `key`, name FROM sports WHERE `key` IN ({$placeholders}) AND is_active = 1");
        $sportRows->execute($sportKeys);
        $sportRows = $sportRows->fetchAll(PDO::FETCH_ASSOC);

        $context['sport_ids'] = [];
        $context['club_sport_ids'] = [];

        foreach ($sportRows as $sr) {
            $sid = (int)$sr['id'];
            $context['sport_ids'][$sr['key']] = $sid;

            $db->prepare(
                "INSERT INTO club_sports (club_id, sport_id, name, is_active, started_at, created_at)
                 VALUES (?, ?, ?, 1, DATE_SUB(CURDATE(), INTERVAL 3 YEAR), NOW())
                 ON DUPLICATE KEY UPDATE name=VALUES(name), is_active=1"
            )->execute([$clubId, $sid, 'Sekcja ' . $sr['name']]);

            $csId = (int)$db->query(
                "SELECT id FROM club_sports WHERE club_id={$clubId} AND sport_id={$sid} LIMIT 1"
            )->fetchColumn();
            $context['club_sport_ids'][$sr['key']] = $csId;
        }

        // ── 4. Admin users ────────────────────────────────────────────────
        // bcrypt hash of 'demo1234' (cost 10) — fixed so we don't burn time hashing.
        $pwd = password_hash('demo1234', PASSWORD_BCRYPT);
        $userBlueprints = [
            ['prezes',   'zarzad',     'Adam Prezesowski',   '+48 600 100 001'],
            ['trener1',  'trener',     'Tomasz Trenerski',   '+48 600 100 002'],
            ['trener2',  'trener',     'Anna Trenerska',     '+48 600 100 003'],
            ['instruktor', 'instruktor', 'Piotr Instruktor', '+48 600 100 004'],
            ['ksiegowy', 'ksiegowy',   'Maria Ksiegowa',     '+48 600 100 005'],
            ['sedzia',   'sedzia',     'Jan Sedzia',         '+48 600 100 006'],
        ];

        $context['user_ids'] = [];
        $context['demo_user_logins'] = [];
        foreach ($userBlueprints as $i => [$baseUsername, $role, $fullName, $phone]) {
            $username = $baseUsername . '_c' . $clubId;
            $email    = $username . '@demo.test';

            // Try insert; if username collides reuse it
            $ins = $db->prepare(
                "INSERT INTO users (username, email, password, full_name, phone, is_super_admin, is_active, created_at)
                 VALUES (?, ?, ?, ?, ?, 0, 1, NOW())
                 ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), is_active=1"
            );
            $ins->execute([$username, $email, $pwd, $fullName, $phone]);

            $uid = (int)$db->query("SELECT id FROM users WHERE username=" . $db->quote($username))->fetchColumn();

            $db->prepare(
                "INSERT IGNORE INTO user_clubs (user_id, club_id, role, is_active) VALUES (?,?,?,1)"
            )->execute([$uid, $clubId, $role]);

            $context['user_ids'][$baseUsername] = $uid;
            $context['demo_user_logins'][] = ['username' => $username, 'role' => $role, 'name' => $fullName];
        }
        $context['admin_user_id'] = $context['user_ids']['prezes'];

        // Mark demo origin in settings (so admin UI can flag this club)
        $db->prepare(
            "INSERT INTO club_settings (club_id, `key`, value, label, type)
             VALUES (?, 'demo_seeded', ?, 'Klub wygenerowany przez seeder demo', 'boolean')
             ON DUPLICATE KEY UPDATE value=VALUES(value)"
        )->execute([$clubId, '1']);

        return [
            'club_id'    => $clubId,
            'sports'     => count($context['sport_ids']),
            'admin_users' => count($context['user_ids']),
            'subdomain'  => $subdomain,
        ];
    }
}
