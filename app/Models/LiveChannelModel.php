<?php

namespace App\Models;

/**
 * Live channel — meta kanalu live updates (mecz/turniej/event).
 * Strumieniowane updaty w live_event_updates (LiveEventUpdateModel).
 */
class LiveChannelModel extends ClubScopedModel
{
    protected string $table = 'live_channels';

    /**
     * Zwroc kanaly aktywne (live) dla biezacego klubu.
     */
    public function findActiveForClub(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT * FROM `{$this->table}` WHERE status = 'live'";
        $params = [];
        if ($clubId !== null) { $sql .= " AND club_id = ?"; $params[] = $clubId; }
        $sql .= " ORDER BY started_at DESC, id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Publiczne kanaly live dla danego klubu (is_public=1, status=live).
     */
    public function publicLiveForClub(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}`
             WHERE club_id = ? AND is_public = 1 AND status = 'live'
             ORDER BY started_at DESC, id DESC"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    public function findByChannel(string $channel): ?array
    {
        $clubId = $this->clubId();
        $sql = "SELECT * FROM `{$this->table}` WHERE channel = ?";
        $params = [$channel];
        if ($clubId !== null) { $sql .= " AND club_id = ?"; $params[] = $clubId; }
        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Public lookup po kluczu kanalu (bez club_id scope) — dla
     * publicznych viewerow widget SSE.
     */
    public function findByChannelPublic(string $channel): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE channel = ? LIMIT 1"
        );
        $stmt->execute([$channel]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Start: status -> live, started_at = now.
     */
    public function startChannel(int $id): bool
    {
        return $this->update($id, [
            'status'     => 'live',
            'started_at' => date('Y-m-d H:i:s'),
            'ended_at'   => null,
        ]);
    }

    /**
     * End: status -> finished, ended_at = now.
     */
    public function endChannel(int $id): bool
    {
        return $this->update($id, [
            'status'   => 'finished',
            'ended_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Touch last_update_at — wolano po INSERT do live_event_updates.
     */
    public function touchUpdate(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET last_update_at = CURRENT_TIMESTAMP(3) WHERE id = ?"
        );
        $stmt->execute([$id]);
    }
}
