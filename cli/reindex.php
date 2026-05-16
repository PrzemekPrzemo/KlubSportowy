<?php
/**
 * Reindex all members, events, and trainings to Elasticsearch.
 *
 * Usage: php cli/reindex.php [--type=members|events|trainings]
 */

declare(strict_types=1);
require_once __DIR__ . '/../bootstrap/php_version_check.php';

define('ROOT_PATH', dirname(__DIR__));

// Composer autoloader (optional)
$vendorAutoload = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require $vendorAutoload;
}

// Simple PSR-4 autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = ROOT_PATH . '/app/' . $relative . '.php';
    if (file_exists($file)) require $file;
});

require ROOT_PATH . '/app/Helpers/Helpers.php';

use App\Helpers\Database;
use App\Helpers\SearchEngine;

echo "=== KlubSportowy — Elasticsearch Reindex ===\n\n";

// Parse optional --type argument
$typeFilter = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--type=')) {
        $typeFilter = substr($arg, 7);
    }
}

$db = Database::pdo();
$indexed = 0;

// --- Members ---
if ($typeFilter === null || $typeFilter === 'members') {
    echo "Indexing members...\n";
    $stmt = $db->query("SELECT id, club_id, first_name, last_name, email, member_number, phone, status FROM members");
    while ($row = $stmt->fetch()) {
        SearchEngine::index('members', (int)$row['id'], [
            'club_id'       => (int)$row['club_id'],
            'first_name'    => $row['first_name'],
            'last_name'     => $row['last_name'],
            'name'          => $row['last_name'] . ' ' . $row['first_name'],
            'email'         => $row['email'],
            'member_number' => $row['member_number'],
            'phone'         => $row['phone'],
            'status'        => $row['status'],
        ]);
        $indexed++;
    }
    echo "  Members indexed: {$indexed}\n";
}

// --- Events ---
$evtCount = 0;
if ($typeFilter === null || $typeFilter === 'events') {
    echo "Indexing events...\n";
    $stmt = $db->query("SELECT id, club_id, name, event_date, end_date, location, type, status, description FROM events");
    while ($row = $stmt->fetch()) {
        SearchEngine::index('events', (int)$row['id'], [
            'club_id'     => (int)$row['club_id'],
            'name'        => $row['name'],
            'event_date'  => $row['event_date'],
            'end_date'    => $row['end_date'],
            'location'    => $row['location'],
            'type'        => $row['type'],
            'status'      => $row['status'],
            'description' => $row['description'],
        ]);
        $evtCount++;
    }
    echo "  Events indexed: {$evtCount}\n";
}

// --- Trainings ---
$trnCount = 0;
if ($typeFilter === null || $typeFilter === 'trainings') {
    echo "Indexing trainings...\n";
    $stmt = $db->query("SELECT id, club_id, name, start_time, end_time, location, description FROM trainings");
    while ($row = $stmt->fetch()) {
        SearchEngine::index('trainings', (int)$row['id'], [
            'club_id'     => (int)$row['club_id'],
            'name'        => $row['name'],
            'start_time'  => $row['start_time'],
            'end_time'    => $row['end_time'],
            'location'    => $row['location'],
            'description' => $row['description'],
        ]);
        $trnCount++;
    }
    echo "  Trainings indexed: {$trnCount}\n";
}

$total = $indexed + $evtCount + $trnCount;
echo "\nDone. Total documents indexed: {$total}\n";
