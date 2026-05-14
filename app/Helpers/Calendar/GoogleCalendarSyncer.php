<?php

declare(strict_types=1);

namespace App\Helpers\Calendar;

use App\Helpers\Database;
use App\Models\ClubGoogleCalendarModel;
use App\Models\ClubModel;
use App\Models\EventGoogleMappingModel;

/**
 * High-level syncer Google Calendar per klub.
 *
 * Algorytm `syncClub($clubId)`:
 *   1. Pobierz config klubu z ClubGoogleCalendarModel (decrypted).
 *   2. Sprawdź ważność access_token; jeśli wygasł (<60s do expires) →
 *      refresh przez refresh_token i zapisz nowy.
 *   3. Direction = push lub both:
 *        a) calendar_events bez mappingu → createEvent + insert mapping
 *        b) calendar_events z mappingiem, updated_at > last_synced_at
 *           → updateEvent + update mapping
 *      (Usuwanie skasowanych lokalnie eventów obsługuje FK ON DELETE
 *       CASCADE — kasujemy mapping w DB. Hard-delete z Google robimy
 *       gdy mapping istnieje ale rekord lokalny zniknął — w praktyce
 *       robimy soft-sync: sprawdzamy mappingi orphan przez LEFT JOIN.)
 *   4. Direction = pull lub both:
 *        a) listEvents() z calendar
 *        b) Event z extendedProperties.private.clubdesk_event_id = NULL
 *           ∧ status != 'cancelled' → INSERT do calendar_events +
 *           upsert mapping.
 *        c) Event status='cancelled' i jest w mappingu → DELETE local +
 *           DELETE mapping (CASCADE).
 *   5. Zapisz last_sync_at / last_sync_status w club_google_calendar.
 *
 * Return: ['pushed', 'pulled', 'updated', 'deleted', 'errors'].
 */
class GoogleCalendarSyncer
{
    /**
     * @return array{pushed:int, pulled:int, updated:int, deleted:int, errors:array<int,string>}
     */
    public static function syncClub(int $clubId): array
    {
        $result = [
            'pushed'  => 0,
            'pulled'  => 0,
            'updated' => 0,
            'deleted' => 0,
            'errors'  => [],
        ];

        $cfgModel = new ClubGoogleCalendarModel();
        $cfg = $cfgModel->decryptedConfig($clubId);
        if (!$cfg) {
            $result['errors'][] = 'Brak konfiguracji Google Calendar dla klubu.';
            return $result;
        }
        if (empty($cfg['client_id']) || empty($cfg['client_secret'])) {
            $result['errors'][] = 'Brak OAuth client_id/secret (per-klub ani globalny w config/google.php).';
            $cfgModel->markSync($clubId, 'error', 'missing_oauth_client');
            return $result;
        }
        if (empty($cfg['refresh_token']) && empty($cfg['access_token'])) {
            $result['errors'][] = 'Brak tokenów OAuth — wymagane ponowne połączenie konta Google.';
            $cfgModel->markSync($clubId, 'error', 'no_tokens');
            return $result;
        }

        $client = new GoogleCalendarClient($cfg);

        // 2. Refresh access_token jeśli wygasł.
        try {
            self::ensureFreshToken($client, $cfg, $cfgModel, $clubId);
        } catch (\Throwable $e) {
            $msg = 'Nie udało się odświeżyć access_token: ' . $e->getMessage();
            $result['errors'][] = $msg;
            $cfgModel->markSync($clubId, 'error', $msg);
            return $result;
        }

        $calendarId = (string)($cfg['calendar_id'] ?: 'primary');
        $direction  = (string)($cfg['sync_direction'] ?? 'push');

        $clubName = '';
        try {
            $club = (new ClubModel())->findById($clubId);
            $clubName = (string)($club['name'] ?? "Klub #{$clubId}");
        } catch (\Throwable) {
            $clubName = "Klub #{$clubId}";
        }

        // 3. Push
        if ($direction === 'push' || $direction === 'both') {
            self::pushEvents($client, $calendarId, $clubId, $clubName, $cfg['timezone'] ?? 'Europe/Warsaw', $result);
        }

        // 4. Pull
        if ($direction === 'pull' || $direction === 'both') {
            self::pullEvents($client, $calendarId, $clubId, $result);
        }

        // 5. Mark sync.
        $status = empty($result['errors']) ? 'ok' : 'partial';
        $msg = sprintf(
            'pushed=%d updated=%d pulled=%d deleted=%d errors=%d',
            $result['pushed'], $result['updated'], $result['pulled'], $result['deleted'], count($result['errors'])
        );
        $cfgModel->markSync($clubId, $status, $msg);

        return $result;
    }

    /**
     * @param array<string,mixed>  $cfg
     * @param array{pushed:int, pulled:int, updated:int, deleted:int, errors:array<int,string>} $result
     */
    private static function ensureFreshToken(
        GoogleCalendarClient $client,
        array &$cfg,
        ClubGoogleCalendarModel $cfgModel,
        int $clubId
    ): void {
        $expiresAt = $cfg['token_expires_at'] ?? null;
        $expiresTs = $expiresAt ? strtotime((string)$expiresAt) : 0;
        $needsRefresh = empty($cfg['access_token']) || $expiresTs === 0 || $expiresTs < time() + 60;

        if (!$needsRefresh) {
            return;
        }
        if (empty($cfg['refresh_token'])) {
            throw new \RuntimeException('access_token wygasł i brak refresh_token');
        }

        $fresh = $client->refreshAccessToken();
        $cfgModel->updateTokens(
            $clubId,
            $fresh['access_token'],
            null, // refresh_token nie zmienia się przy refresh
            $fresh['expires_in']
        );
        $cfg['access_token'] = $fresh['access_token'];
    }

    /**
     * @param array{pushed:int, pulled:int, updated:int, deleted:int, errors:array<int,string>} $result
     */
    private static function pushEvents(
        GoogleCalendarClient $client,
        string $calendarId,
        int $clubId,
        string $clubName,
        string $timezone,
        array &$result
    ): void {
        $db = Database::pdo();
        $mappingModel = new EventGoogleMappingModel();

        // Lokalne eventy klubu + mapping (LEFT JOIN).
        $stmt = $db->prepare(
            "SELECT ce.*, egm.google_event_id, egm.last_synced_at, egm.id AS mapping_id
               FROM calendar_events ce
               LEFT JOIN event_google_mapping egm ON egm.event_id = ce.id
              WHERE ce.club_id = ?
              ORDER BY ce.id"
        );
        $stmt->execute([$clubId]);
        $events = $stmt->fetchAll();

        foreach ($events as $ev) {
            $eventId = (int)$ev['id'];
            try {
                $googleEvent = self::toGoogleEvent($ev, $clubName, $timezone);

                if (empty($ev['google_event_id'])) {
                    // CREATE
                    $resp = $client->createEvent($calendarId, $googleEvent);
                    $gid  = (string)($resp['id'] ?? '');
                    $etag = (string)($resp['etag'] ?? '');
                    if ($gid !== '') {
                        $mappingModel->upsert($clubId, $eventId, $gid, $etag, 'synced');
                        $result['pushed']++;
                    }
                } else {
                    // UPDATE jeśli updated_at lokalny > last_synced_at (lub last_synced_at NULL)
                    $localUpd  = strtotime((string)($ev['updated_at']     ?? '1970-01-01'));
                    $lastSync  = $ev['last_synced_at'] ? strtotime((string)$ev['last_synced_at']) : 0;
                    if ($localUpd > $lastSync) {
                        $resp = $client->updateEvent($calendarId, (string)$ev['google_event_id'], $googleEvent);
                        $etag = (string)($resp['etag'] ?? '');
                        $mappingModel->upsert($clubId, $eventId, (string)$ev['google_event_id'], $etag, 'synced');
                        $result['updated']++;
                    }
                }
            } catch (\Throwable $e) {
                $result['errors'][] = "event#{$eventId}: " . $e->getMessage();
                if (!empty($ev['mapping_id'])) {
                    $mappingModel->markError($eventId, $e->getMessage());
                }
            }
        }
    }

    /**
     * @param array{pushed:int, pulled:int, updated:int, deleted:int, errors:array<int,string>} $result
     */
    private static function pullEvents(
        GoogleCalendarClient $client,
        string $calendarId,
        int $clubId,
        array &$result
    ): void {
        $db = Database::pdo();
        $mappingModel = new EventGoogleMappingModel();

        // Delta sync: pull tylko zmiany od ostatniego sync (last 30 dni jako bezpieczny fallback).
        $updatedMin = date('c', strtotime('-30 days'));

        try {
            $resp = $client->listEvents($calendarId, $updatedMin);
        } catch (\Throwable $e) {
            $result['errors'][] = 'listEvents: ' . $e->getMessage();
            return;
        }

        $items = (array)($resp['items'] ?? []);
        foreach ($items as $g) {
            $gid = (string)($g['id'] ?? '');
            if ($gid === '') {
                continue;
            }
            $status = (string)($g['status'] ?? 'confirmed');
            $existingMapping = $mappingModel->findByGoogleId($clubId, $gid);

            // CASE: event w Google został odwołany.
            if ($status === 'cancelled') {
                if ($existingMapping) {
                    try {
                        $del = $db->prepare("DELETE FROM calendar_events WHERE id = ? AND club_id = ?");
                        $del->execute([(int)$existingMapping['event_id'], $clubId]);
                        // mapping kasuje CASCADE z calendar_events
                        $result['deleted']++;
                    } catch (\Throwable $e) {
                        $result['errors'][] = "del google#{$gid}: " . $e->getMessage();
                    }
                }
                continue;
            }

            // Pomijamy eventy które JEŚMY popchnęli (extendedProperties.private.clubdesk_event_id).
            $marker = $g['extendedProperties']['private']['clubdesk_event_id'] ?? null;
            if ($marker !== null && $existingMapping !== null) {
                // Już zmapowane, push side zarządza updateami.
                continue;
            }

            // Konwersja do calendar_events.
            try {
                $local = self::fromGoogleEvent($g, $clubId);
                if ($existingMapping) {
                    // Update lokalny — pobierz id, update fields
                    $localId = (int)$existingMapping['event_id'];
                    $upd = $db->prepare(
                        "UPDATE calendar_events
                            SET title=?, description=?, location=?, start_at=?, end_at=?
                          WHERE id=? AND club_id=?"
                    );
                    $upd->execute([
                        $local['title'], $local['description'], $local['location'],
                        $local['start_at'], $local['end_at'], $localId, $clubId,
                    ]);
                    $mappingModel->upsert($clubId, $localId, $gid, (string)($g['etag'] ?? ''), 'synced');
                    $result['updated']++;
                } else {
                    $cols   = array_keys($local);
                    $vals   = array_values($local);
                    $holds  = implode(', ', array_fill(0, count($cols), '?'));
                    $sql    = "INSERT INTO calendar_events (`" . implode('`,`', $cols) . "`) VALUES ({$holds})";
                    $ins    = $db->prepare($sql);
                    $ins->execute($vals);
                    $newId  = (int)$db->lastInsertId();
                    $mappingModel->upsert($clubId, $newId, $gid, (string)($g['etag'] ?? ''), 'synced');
                    $result['pulled']++;
                }
            } catch (\Throwable $e) {
                $result['errors'][] = "pull google#{$gid}: " . $e->getMessage();
            }
        }
    }

    /**
     * Konwertuje wiersz calendar_events na Google Event resource.
     *
     * @param array<string,mixed> $ev
     * @return array<string,mixed>
     */
    private static function toGoogleEvent(array $ev, string $clubName, string $timezone): array
    {
        $start = (string)$ev['start_at'];
        $end   = (string)($ev['end_at'] ?: $ev['start_at']);
        $isAllDay = !empty($ev['all_day']);

        $description = trim((string)($ev['description'] ?? ''));
        $description .= "\n\nKlub: {$clubName}\nID: " . (int)$ev['id'];

        $startBlock = $isAllDay
            ? ['date' => substr($start, 0, 10)]
            : ['dateTime' => self::toRfc3339($start, $timezone), 'timeZone' => $timezone];
        $endBlock = $isAllDay
            ? ['date' => substr($end, 0, 10)]
            : ['dateTime' => self::toRfc3339($end, $timezone), 'timeZone' => $timezone];

        return [
            'summary'     => (string)$ev['title'],
            'description' => $description,
            'location'    => (string)($ev['location'] ?? ''),
            'start'       => $startBlock,
            'end'         => $endBlock,
            'extendedProperties' => [
                'private' => [
                    'clubdesk_event_id' => (string)(int)$ev['id'],
                    'clubdesk_club_id'  => (string)(int)($ev['club_id'] ?? 0),
                ],
            ],
        ];
    }

    /**
     * Konwertuje Google Event → wiersz calendar_events (INSERT/UPDATE).
     *
     * @param array<string,mixed> $g
     * @return array<string,mixed>
     */
    private static function fromGoogleEvent(array $g, int $clubId): array
    {
        $start = $g['start']['dateTime'] ?? $g['start']['date'] ?? null;
        $end   = $g['end']['dateTime']   ?? $g['end']['date']   ?? null;

        $startAt = $start ? date('Y-m-d H:i:s', strtotime((string)$start)) : date('Y-m-d H:i:s');
        $endAt   = $end   ? date('Y-m-d H:i:s', strtotime((string)$end))   : $startAt;

        $allDay = isset($g['start']['date']) && !isset($g['start']['dateTime']);

        return [
            'club_id'     => $clubId,
            'title'       => mb_substr((string)($g['summary'] ?? '(bez tytułu)'), 0, 200),
            'description' => (string)($g['description'] ?? ''),
            'location'    => mb_substr((string)($g['location'] ?? ''), 0, 200),
            'start_at'    => $startAt,
            'end_at'      => $endAt,
            'all_day'     => $allDay ? 1 : 0,
            'visibility'  => 'club',
            'link_type'   => 'none',
        ];
    }

    private static function toRfc3339(string $datetime, string $timezone): string
    {
        try {
            $tz  = new \DateTimeZone($timezone);
            $dt  = new \DateTimeImmutable($datetime, $tz);
            return $dt->format(\DateTime::RFC3339);
        } catch (\Throwable) {
            return date(\DateTime::RFC3339, strtotime($datetime) ?: time());
        }
    }
}
