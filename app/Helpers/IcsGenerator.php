<?php

namespace App\Helpers;

class IcsGenerator
{
    /**
     * Generate a VCALENDAR string with a single VEVENT.
     *
     * Expected $event keys:
     *   - uid        (string)  unique identifier
     *   - summary    (string)  event title
     *   - dtstart    (string)  start datetime (Y-m-d H:i:s or Y-m-d)
     *   - dtend      (string|null) end datetime
     *   - location   (string|null)
     *   - description(string|null)
     */
    public static function generate(array $event): string
    {
        $uid         = self::escape($event['uid'] ?? uniqid('ks-', true));
        $summary     = self::escape($event['summary'] ?? 'Wydarzenie');
        $dtstart     = self::formatDate($event['dtstart'] ?? date('Y-m-d H:i:s'));
        $dtend       = !empty($event['dtend']) ? self::formatDate($event['dtend']) : $dtstart;
        $location    = self::escape($event['location'] ?? '');
        $description = self::escape($event['description'] ?? '');
        $now         = gmdate('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//KlubSportowy//PL',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $now,
            'DTSTART:' . $dtstart,
            'DTEND:' . $dtend,
            'SUMMARY:' . $summary,
        ];

        if ($location !== '') {
            $lines[] = 'LOCATION:' . $location;
        }
        if ($description !== '') {
            $lines[] = 'DESCRIPTION:' . $description;
        }

        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Send iCal file to browser as a download.
     */
    public static function download(array $event, string $filename = 'event.ics'): void
    {
        $content = self::generate($event);

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: no-cache, no-store, must-revalidate');

        echo $content;
        exit;
    }

    /**
     * Format a datetime string to iCal UTC format.
     */
    private static function formatDate(string $datetime): string
    {
        $ts = strtotime($datetime);
        if ($ts === false) {
            $ts = time();
        }
        return gmdate('Ymd\THis\Z', $ts);
    }

    /**
     * Escape special characters for iCal values.
     */
    private static function escape(string $value): string
    {
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace(';', '\\;', $value);
        $value = str_replace(',', '\\,', $value);
        $value = str_replace("\n", '\\n', $value);
        $value = str_replace("\r", '', $value);
        return $value;
    }
}
