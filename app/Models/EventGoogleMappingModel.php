<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Mapowanie calendar_events.id ↔ Google Calendar event ID.
 *
 * Każdy lokalny event ma 0 lub 1 mapping (UNIQUE event_id). Etag pochodzi
 * z Google response — pozwala wykrywać out-of-date (jeśli etag w Google
 * ≠ nasz, wykonaj re-sync).
 */
class EventGoogleMappingModel extends BaseModel
{
    protected string $table = 'event_google_mapping';

    public function findByEventId(int $eventId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM event_google_mapping WHERE event_id = ? LIMIT 1"
        );
        $stmt->execute([$eventId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByGoogleId(int $clubId, string $googleEventId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM event_google_mapping WHERE club_id = ? AND google_event_id = ? LIMIT 1"
        );
        $stmt->execute([$clubId, $googleEventId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Upsert mappingu — po push'u lub pull'u eventu.
     */
    public function upsert(int $clubId, int $eventId, string $googleEventId, ?string $etag = null, string $status = 'synced'): int
    {
        $existing = $this->findByEventId($eventId);
        $data = [
            'club_id'         => $clubId,
            'event_id'        => $eventId,
            'google_event_id' => $googleEventId,
            'etag'            => $etag,
            'last_synced_at'  => date('Y-m-d H:i:s'),
            'sync_status'     => $status,
            'last_error'      => null,
        ];
        if ($existing) {
            $id = (int)$existing['id'];
            unset($data['club_id'], $data['event_id']);
            $this->update($id, $data);
            return $id;
        }
        return $this->insert($data);
    }

    /**
     * Zapis błędu sync — po nieudanym push/pull dla konkretnego event'a.
     */
    public function markError(int $eventId, string $error): void
    {
        $stmt = $this->db->prepare(
            "UPDATE event_google_mapping
                SET sync_status = 'error', last_error = ?, last_synced_at = NOW()
              WHERE event_id = ?"
        );
        $stmt->execute([mb_substr($error, 0, 500), $eventId]);
    }

    public function deleteByEventId(int $eventId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM event_google_mapping WHERE event_id = ?");
        return $stmt->execute([$eventId]);
    }

    /**
     * Lista wszystkich mappingów klubu.
     * @return array<int, array<string,mixed>>
     */
    public function listForClub(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM event_google_mapping WHERE club_id = ? ORDER BY id"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    /**
     * Mapa event_id => google_event_id (lookup do syncera).
     * @return array<int,string>
     */
    public function mapForClub(int $clubId): array
    {
        $out = [];
        foreach ($this->listForClub($clubId) as $row) {
            $out[(int)$row['event_id']] = (string)$row['google_event_id'];
        }
        return $out;
    }
}
