<?php
// ============================================================
// bootstrap/php_version_check.php
//
// Wymusza PHP >= 8.1 dla ClubDesk (uzywa str_starts_with, named args,
// readonly props, enum). Plesk default to PHP 7.4 — trzeba jawnie
// wskazac binary 8.x.
//
// Includowane na samym poczatku KAZDEGO entrypointa:
//   - public/index.php
//   - wszystkie cli/*.php
// ============================================================

if (PHP_VERSION_ID < 80100) {
    $current = PHP_VERSION;
    $isCli = (PHP_SAPI === 'cli');

    $msg = "[ClubDesk] Wymagany PHP >= 8.1, wykryto {$current}.\n";

    if ($isCli) {
        $msg .= "\nPlesk standardowo wskazuje stary PHP w /usr/bin/php (7.4). Uzyj jawnej sciezki:\n";
        $msg .= "  /opt/plesk/php/8.3/bin/php " . ($_SERVER['argv'][0] ?? 'cli/script.php') . "\n";
        $msg .= "  /opt/plesk/php/8.2/bin/php " . ($_SERVER['argv'][0] ?? 'cli/script.php') . "\n";
        $msg .= "  /opt/plesk/php/8.1/bin/php " . ($_SERVER['argv'][0] ?? 'cli/script.php') . "\n";
        $msg .= "\nLub ustaw alias w ~/.bashrc:  alias php='/opt/plesk/php/8.3/bin/php'\n";
        fwrite(STDERR, $msg);
        exit(1);
    }

    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg;
    echo "\nSkonfiguruj wersje PHP dla domeny w panelu Plesk (Domains -> portal.clubdesk.pl -> PHP Settings -> Version >= 8.1).\n";
    exit(1);
}
