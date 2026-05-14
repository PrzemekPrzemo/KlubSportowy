<?php

namespace App\Models;

/**
 * Append-only log zdarzen live dla kanalow SSE.
 *
 * Tabela live_event_updates indeksowana po (channel, id). SSE subscriber
 * dlugotrwale polaczenie polluje przez getSince($channel, $lastId).
 */
class LiveEventUpdateModel extends ClubScopedModel
{
    protected string $table = 'live_event_updates';

    /**
     * Zwroc updaty dla kanalu z id > $sinceId.
     *
     * UWAGA: ta metoda nie filtruje po club_id (kanal sam w sobie
     * jest globalnie unique i mapuje sie na klub). Stream SSE czyta
     * po prostu po channel.
     */
    public function getSince(string $channel, int $sinceId = 0, int $limit = 200): array
    {
        $limit = max(1, min(1000, $limit));
        $stmt = $this->db->prepare(
            "SELECT id, channel, sequence_id, event_type, payload, created_at
             FROM `{$this->table}`
             WHERE channel = ? AND id > ?
             ORDER BY id ASC
             LIMIT {$limit}"
        );
        $stmt->execute([$channel, $sinceId]);
        return $stmt->fetchAll();
    }

    /**
     * Pobierz nastepny sequence_id dla kanalu (MAX+1).
     */
    public function nextSequenceId(string $channel): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(sequence_id), 0) + 1
             FROM `{$this->table}` WHERE channel = ?"
        );
        $stmt->execute([$channel]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Append nowy update. Zwraca insert id.
     */
    public function append(int $clubId, string $channel, string $eventType, array $payload): int
    {
        $seq = $this->nextSequenceId($channel);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{}';
        }
        $stmt = $this->db->prepare(
            "INSERT INTO `{$this->table}` (club_id, channel, sequence_id, event_type, payload)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$clubId, $channel, $seq, $eventType, $json]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Ostatnie id w kanale (dla resume).
     */
    public function lastIdForChannel(string $channel): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(MAX(id), 0) FROM `{$this->table}` WHERE channel = ?"
        );
        $stmt->execute([$channel]);
        return (int)$stmt->fetchColumn();
    }
}
