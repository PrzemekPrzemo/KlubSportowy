<?php

namespace App\Helpers;

use App\Models\LiveChannelModel;
use App\Models\LiveEventUpdateModel;

/**
 * Server-Sent Events publish/subscribe helper.
 *
 * publish() — INSERT do live_event_updates + touch live_channels.last_update_at.
 * stream() — dlugotrwale HTTP, emituje SSE frames z polingu MySQL co 2s.
 *
 * Ograniczenia: PHP-FPM dlugo trzyma worker — limit pm.max_children jest
 * waskim gardlem przy wielu jednoczesnych viewerach. Plan B: wyniesc SSE
 * do dedicated Node/Go service.
 */
class LiveStream
{
    /** Domyslny timeout polaczenia SSE (sekundy). Klient i tak sie reconnectuje. */
    public const DEFAULT_TIMEOUT_SEC = 30;

    /** Interwal pollingu MySQL (sekundy). */
    public const POLL_INTERVAL_SEC = 2;

    /** Interwal heartbeat (sekundy) — komentarz SSE, zeby nie zerwalo. */
    public const HEARTBEAT_INTERVAL_SEC = 10;

    /**
     * Publish update do channel.
     *
     * @param string $channel    nazwa kanalu (np. "tournament:42")
     * @param string $eventType  typ zdarzenia (goal/point/...)
     * @param array  $payload    dowolne dane do serializacji JSON
     * @return int               id wstawionego rekordu live_event_updates
     */
    public static function publish(string $channel, string $eventType, array $payload): int
    {
        $clubId = ClubContext::current();
        if ($clubId === null) {
            throw new \RuntimeException('LiveStream::publish wymaga aktywnego ClubContext.');
        }

        $channelModel = new LiveChannelModel();
        $ch = $channelModel->findByChannel($channel);
        if ($ch === null) {
            throw new \RuntimeException("Live channel '{$channel}' nie istnieje dla biezacego klubu.");
        }

        $updates = new LiveEventUpdateModel();
        $insertedId = $updates->append((int)$clubId, $channel, $eventType, $payload);

        $channelModel->touchUpdate((int)$ch['id']);

        return $insertedId;
    }

    /**
     * SSE stream loop. Emituje frames i flushuje, dopoki polaczenie zywe.
     *
     * @param string $channel
     * @param int    $sinceId      ostatnie znane id (Last-Event-ID)
     * @param int    $timeoutSec   po ilu sekundach zakonczyc (klient reconnect)
     */
    public static function stream(string $channel, int $sinceId = 0, int $timeoutSec = self::DEFAULT_TIMEOUT_SEC): void
    {
        // Headers
        if (!headers_sent()) {
            header('Content-Type: text/event-stream; charset=utf-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('X-Accel-Buffering: no');  // disable nginx buffering
            header('Connection: keep-alive');
        }

        // PHP runtime
        @set_time_limit(0);
        @ini_set('zlib.output_compression', '0');
        ignore_user_abort(false);

        // Wylacz wszystkie poziomy output bufferingu
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        @ob_implicit_flush(true);

        $updates       = new LiveEventUpdateModel();
        $startedAt     = time();
        $lastHeartbeat = time();

        // Wyslij retry hint dla klienta (3000ms)
        echo "retry: 3000\n\n";
        @flush();

        // Wstepny heartbeat
        echo ": connected channel={$channel} since={$sinceId}\n\n";
        @flush();

        while (true) {
            if (connection_aborted()) {
                break;
            }
            if ((time() - $startedAt) >= $timeoutSec) {
                // Wyslij sygnal koncowy — klient ponowi polaczenie
                echo ": timeout\n\n";
                @flush();
                break;
            }

            try {
                $rows = $updates->getSince($channel, $sinceId);
            } catch (\Throwable $e) {
                echo ": db-error\n\n";
                @flush();
                break;
            }

            foreach ($rows as $row) {
                $id      = (int)$row['id'];
                $type    = (string)$row['event_type'];
                $payload = $row['payload'];
                // Pole payload w JSON; PDO zwroci string — przekaz tak jak jest
                if (is_array($payload)) {
                    $payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
                }
                // Strip newlines z payloadu (SSE rozdziela frames po \n)
                $payload = str_replace(["\r\n", "\r", "\n"], ' ', (string)$payload);
                $type    = preg_replace('/[^a-zA-Z0-9_\-]/', '', $type) ?: 'message';

                echo "id: {$id}\n";
                echo "event: {$type}\n";
                echo "data: {$payload}\n\n";
                @flush();

                $sinceId = $id;
            }

            // Heartbeat zeby proxy nie zamknelo
            if ((time() - $lastHeartbeat) >= self::HEARTBEAT_INTERVAL_SEC) {
                echo ": heartbeat\n\n";
                @flush();
                $lastHeartbeat = time();
            }

            sleep(self::POLL_INTERVAL_SEC);
        }
    }

    /**
     * Odczytaj Last-Event-ID z requestu (header lub ?since=).
     */
    public static function lastEventIdFromRequest(): int
    {
        $hdr = $_SERVER['HTTP_LAST_EVENT_ID'] ?? '';
        if ($hdr !== '' && ctype_digit((string)$hdr)) {
            return (int)$hdr;
        }
        $q = $_GET['since'] ?? '0';
        return ctype_digit((string)$q) ? (int)$q : 0;
    }
}
