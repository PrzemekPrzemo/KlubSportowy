<?php
// ============================================================
// cli/seed_demo.php — comprehensive demo data seeder
//
// Generates a full demo club ready for a sales demo:
//   - club + branding + admin users
//   - 20/80/250 members with PESEL + addresses
//   - fee rates + 6 months of payment history
//   - trainings (past + future) + historic tournaments with results
//   - federation licenses, notification rules, in-app notifications
//   - recalculated sport rankings
//
// Usage:
//   php cli/seed_demo.php [--club-id=N] [--scale=small|medium|large]
//                         [--clean] [--dry-run]
//
// All demo members are tagged with notes starting with "[DEMO]" so they
// can be wiped with --clean.
// ============================================================
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap/php_version_check.php';

define('ROOT_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = ROOT_PATH . '/app/' . $relative . '.php';
    if (file_exists($file)) require $file;
});

use App\Helpers\Database;
use App\Helpers\DemoSeeders\DemoClubSeeder;
use App\Helpers\DemoSeeders\DemoEventsSeeder;
use App\Helpers\DemoSeeders\DemoFeesSeeder;
use App\Helpers\DemoSeeders\DemoMembersSeeder;
use App\Helpers\DemoSeeders\DemoNotificationsSeeder;

// ── ANSI helpers ──────────────────────────────────────────────────────
$useColor = (PHP_SAPI === 'cli' && (getenv('NO_COLOR') === false || getenv('NO_COLOR') === ''));
$color = function (string $code, string $text) use ($useColor): string {
    return $useColor ? "\033[{$code}m{$text}\033[0m" : $text;
};
$bold   = fn(string $t) => $color('1', $t);
$green  = fn(string $t) => $color('32', $t);
$yellow = fn(string $t) => $color('33', $t);
$red    = fn(string $t) => $color('31', $t);
$blue   = fn(string $t) => $color('36', $t);
$gray   = fn(string $t) => $color('90', $t);

// ── Parse args ────────────────────────────────────────────────────────
$opts = [
    'club-id' => null,
    'scale'   => 'medium',
    'clean'   => false,
    'dry-run' => false,
    'help'    => false,
];

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--help' || $arg === '-h') { $opts['help'] = true; continue; }
    if ($arg === '--clean')   { $opts['clean'] = true;   continue; }
    if ($arg === '--dry-run') { $opts['dry-run'] = true; continue; }
    if (str_starts_with($arg, '--club-id=')) { $opts['club-id'] = (int)substr($arg, 10); continue; }
    if (str_starts_with($arg, '--scale='))   { $opts['scale']   = substr($arg, 8);       continue; }
    fwrite(STDERR, "Unknown argument: {$arg}\n");
    exit(2);
}

if ($opts['help']) {
    echo "Usage: php cli/seed_demo.php [--club-id=N] [--scale=small|medium|large] [--clean] [--dry-run]\n";
    echo "\n";
    echo "  --club-id=N    Operate on existing club (default: create new 'AZS Warszawa Demo')\n";
    echo "  --scale=...    small=20 members/1 sport, medium=80/3, large=250/5 (default: medium)\n";
    echo "  --clean        Remove existing demo members ([DEMO] notes) before seeding\n";
    echo "  --dry-run      Print plan only, no DB writes\n";
    exit(0);
}

if (!in_array($opts['scale'], ['small','medium','large'], true)) {
    fwrite(STDERR, "Invalid --scale (must be small|medium|large)\n");
    exit(2);
}

echo $bold("\n=== ClubDesk Demo Seeder ===\n");
echo $gray("scale: {$opts['scale']}, club-id: " . ($opts['club-id'] ?? 'new') . ", clean: " . ($opts['clean'] ? 'yes' : 'no') . ", dry-run: " . ($opts['dry-run'] ? 'yes' : 'no') . "\n\n");

// ── Dry run short-circuit ─────────────────────────────────────────────
if ($opts['dry-run']) {
    echo $yellow("DRY RUN — no DB writes will happen.\n");
    echo "Plan:\n";
    echo "  - " . ($opts['club-id'] ? "use club #{$opts['club-id']}" : "create new club 'AZS Warszawa Demo'") . "\n";
    echo "  - scale '{$opts['scale']}'\n";
    if ($opts['clean']) echo "  - delete previous [DEMO] members first\n";
    echo "  - seed: club+branding, users, members, fees+payments, events+results, notifications, rankings\n";
    $expected = match ($opts['scale']) {
        'small'  => ['members' => 20,  'sports' => 1, 'payments' => '~100',  'events' => 5],
        'large'  => ['members' => 250, 'sports' => 5, 'payments' => '~1250', 'events' => 25],
        default  => ['members' => 80,  'sports' => 3, 'payments' => '~400',  'events' => 15],
    };
    echo "  - expected: {$expected['members']} members, {$expected['sports']} sports, {$expected['payments']} payments, {$expected['events']} events\n";
    exit(0);
}

// ── Connect ───────────────────────────────────────────────────────────
try {
    $db = Database::pdo();
} catch (Throwable $e) {
    echo $red("FATAL: cannot connect to DB: " . $e->getMessage() . "\n");
    exit(1);
}

// ── Wrap in transaction ───────────────────────────────────────────────
$db->beginTransaction();

try {
    // ── Optional clean ────────────────────────────────────────────────
    if ($opts['clean']) {
        $where = $opts['club-id'] ? "club_id = " . (int)$opts['club-id'] . " AND " : '';
        $deleted = $db->exec("DELETE FROM members WHERE {$where} notes LIKE '[DEMO]%'");
        echo $yellow("✓ Deleted {$deleted} previous demo members\n");

        if ($opts['club-id']) {
            $cid = (int)$opts['club-id'];
            $db->exec("DELETE FROM fee_rates  WHERE club_id={$cid} AND description LIKE '[DEMO]%'");
            $db->exec("DELETE FROM events     WHERE club_id={$cid} AND description LIKE '[DEMO]%'");
            $db->exec("DELETE FROM trainings  WHERE club_id={$cid} AND description LIKE '[DEMO]%'");
        }
    }

    // ── Context shared across seeders ─────────────────────────────────
    $context = [
        'club_id' => $opts['club-id'],
        'scale'   => $opts['scale'],
    ];

    // ── 1. Club + branding + users + sports ───────────────────────────
    echo $blue("→ Step 1/5: Club, branding, admin users, sport sections...\n");
    $clubStats = DemoClubSeeder::seed($context);
    echo $green("  ✓ Club #{$clubStats['club_id']} ready. Sports: {$clubStats['sports']}, admin users: {$clubStats['admin_users']}\n");

    // ── 2. Members ────────────────────────────────────────────────────
    echo $blue("→ Step 2/5: Members + PESEL + addresses + federation licenses...\n");
    $memberStats = DemoMembersSeeder::seed($context);
    echo $green("  ✓ Members: {$memberStats['members']}, sport assignments: {$memberStats['sport_assignments']}, licenses: {$memberStats['licenses']}\n");

    // ── 3. Fees + payment history ─────────────────────────────────────
    echo $blue("→ Step 3/5: Fee rates + 6mo payment history...\n");
    $feeStats = DemoFeesSeeder::seed($context);
    echo $green("  ✓ Fee rates: {$feeStats['fee_rates']}, payments: {$feeStats['payments']} (with arrears+advance simulated)\n");

    // ── 4. Events + tournaments + rankings ────────────────────────────
    echo $blue("→ Step 4/5: Trainings, events, tournaments, rankings...\n");
    $eventStats = DemoEventsSeeder::seed($context);
    echo $green("  ✓ Trainings: {$eventStats['trainings']}, events: {$eventStats['events']}, tournaments: {$eventStats['tournaments']}, ranking sports recalc: {$eventStats['ranking_runs']}\n");

    // ── 5. Notifications ──────────────────────────────────────────────
    echo $blue("→ Step 5/5: Notification rules + in-app notifications...\n");
    $notifStats = DemoNotificationsSeeder::seed($context);
    echo $green("  ✓ Rules: {$notifStats['rules']}, in-app notifications: {$notifStats['notifications']}\n");

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    echo $red("\n✗ FATAL: " . $e->getMessage() . "\n");
    echo $gray($e->getTraceAsString() . "\n");
    exit(1);
}

// ── Summary ───────────────────────────────────────────────────────────
echo "\n" . $bold("=== Demo seed complete ===\n");
echo $green(sprintf(
    "  Club: #%d  |  Members: %d  |  Payments: %d  |  Events: %d  |  Tournaments: %d\n",
    $clubStats['club_id'],
    $memberStats['members'],
    $feeStats['payments'],
    $eventStats['events'],
    $eventStats['tournaments']
));
echo $green("  Subdomain: {$clubStats['subdomain']}\n");

echo "\n" . $yellow("⚠ Demo credentials (password: demo1234 for all):\n");
foreach (($context['demo_user_logins'] ?? []) as $u) {
    printf("    %s  (role: %s, name: %s)\n", $u['username'], $u['role'], $u['name']);
}

echo "\n" . $gray("Tip: re-run with --clean to wipe previous demo members; use --dry-run to preview.\n");
echo $gray("NOT FOR PRODUCTION — these are public demo credentials.\n\n");
