<?php
// ============================================================
// cli/live_publish_demo.php
//
// Demo: tworzy kanal live (jeśli nie istnieje), startuje go, symuluje
// 5 goli w ciągu ~30s i konczy. Pozwala zweryfikowac SSE end-to-end.
//
// Usage:
//   php cli/live_publish_demo.php [club_id] [channel]
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

$vendor = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($vendor)) require $vendor;

require ROOT_PATH . '/app/Helpers/Helpers.php';

$localApp = ROOT_PATH . '/config/app.local.php';
$cfg = file_exists($localApp) ? require $localApp : require ROOT_PATH . '/config/app.php';
date_default_timezone_set($cfg['timezone'] ?? 'Europe/Warsaw');

use App\Helpers\ClubContext;
use App\Helpers\Database;
use App\Helpers\LiveStream;
use App\Models\LiveChannelModel;
use App\Models\LiveEventUpdateModel;

$clubId  = isset($argv[1]) ? (int)$argv[1] : 0;
$channel = $argv[2] ?? 'demo:match:1';

if ($clubId <= 0) {
    // Auto-pick pierwszy aktywny klub
    try {
        $pdo = Database::pdo();
        $row = $pdo->query("SELECT id FROM clubs WHERE is_active = 1 ORDER BY id ASC LIMIT 1")->fetch();
        if ($row) {
            $clubId = (int)$row['id'];
            echo "[info] Auto-picked club_id={$clubId}\n";
        }
    } catch (\Throwable $e) {
        fwrite(STDERR, "ERROR: nie udalo sie polaczyc z DB: {$e->getMessage()}\n");
        exit(1);
    }
}
if ($clubId <= 0) {
    fwrite(STDERR, "ERROR: brak aktywnego klubu w DB. Podaj club_id jako argument: php cli/live_publish_demo.php 1\n");
    exit(1);
}

// Symuluj ClubContext dla helpera (LiveStream::publish wymaga ClubContext::current)
$_SESSION = [];
ClubContext::set($clubId);

$model = new LiveChannelModel();
$existing = $model->findByChannel($channel);

if ($existing === null) {
    $id = $model->insert([
        'channel'   => $channel,
        'title'     => 'Demo mecz: Drużyna A vs Drużyna B',
        'sport_key' => 'football',
        'is_public' => 1,
        'status'    => 'scheduled',
    ]);
    echo "[info] Utworzono kanal id={$id} channel={$channel}\n";
    $existing = $model->findById($id);
}
$channelId = (int)$existing['id'];

$model->startChannel($channelId);
echo "[info] Kanal wystartowany. Otwórz w przeglądarce:\n";
echo "       " . url('live/stream/' . $channel) . "\n";
echo "       (lub widget na /live)\n\n";

// Start event
LiveStream::publish($channel, 'start', [
    'message' => 'Mecz rozpoczęty',
    'teams'   => ['A' => 'Drużyna A', 'B' => 'Drużyna B'],
]);
echo "[event] start\n";

// 5 goli w ~30s
$scoreA = 0;
$scoreB = 0;
$totalGoals = 5;

for ($i = 1; $i <= $totalGoals; $i++) {
    // Sleep losowo 4-8s, sumarycznie ~30s
    $delay = rand(4, 8);
    sleep($delay);

    $team = (rand(0, 1) === 0) ? 'A' : 'B';
    if ($team === 'A') { $scoreA++; } else { $scoreB++; }

    LiveStream::publish($channel, 'goal', [
        'team'    => $team,
        'minute'  => $i * 15,
        'scorer'  => 'Player ' . rand(1, 11),
        'score'   => ['A' => $scoreA, 'B' => $scoreB],
    ]);
    echo "[event] goal #{$i} team={$team} score={$scoreA}:{$scoreB} (delay {$delay}s)\n";
}

sleep(2);
LiveStream::publish($channel, 'end', [
    'final_score' => ['A' => $scoreA, 'B' => $scoreB],
    'message'     => 'Mecz zakończony',
]);
echo "[event] end final={$scoreA}:{$scoreB}\n";

$model->endChannel($channelId);
echo "[info] Kanal zakonczony. Sprawdz lacznie eventow:\n";
$last = (new LiveEventUpdateModel())->lastIdForChannel($channel);
echo "       last_event_id={$last}\n";
