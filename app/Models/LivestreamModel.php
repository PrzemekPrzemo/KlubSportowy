<?php

namespace App\Models;

class LivestreamModel extends ClubScopedModel
{
    protected string $table = 'livestreams';

    public function listForClub(?string $status = null, int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT ls.*, e.name AS event_name, u.full_name AS creator_name
                FROM livestreams ls
                LEFT JOIN events e ON e.id = ls.event_id
                LEFT JOIN users u  ON u.id = ls.created_by
                WHERE 1=1";
        $params = [];
        if ($clubId !== null) { $sql .= " AND ls.club_id = ?"; $params[] = $clubId; }
        if ($status) { $sql .= " AND ls.status = ?"; $params[] = $status; }
        $sql .= " ORDER BY ls.scheduled_at DESC, ls.created_at DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function liveNow(): array
    {
        $clubId = $this->clubId();
        $sql = "SELECT * FROM livestreams WHERE status = 'na_zywo'";
        $params = [];
        if ($clubId !== null) { $sql .= " AND club_id = ?"; $params[] = $clubId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function publicLive(int $clubId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM livestreams WHERE club_id = ? AND status = 'na_zywo' AND is_public = 1"
        );
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    /** Auto-generuje embed code z URL. */
    public static function generateEmbed(string $url, string $platform): string
    {
        return match ($platform) {
            'youtube' => self::youtubeEmbed($url),
            'twitch'  => self::twitchEmbed($url),
            'facebook'=> '<iframe src="' . htmlspecialchars($url) . '" width="100%" height="400" allowfullscreen></iframe>',
            default   => '<a href="' . htmlspecialchars($url) . '" target="_blank">Otwórz transmisję</a>',
        };
    }

    private static function youtubeEmbed(string $url): string
    {
        $videoId = '';
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/live\/)([a-zA-Z0-9_-]+)/', $url, $m)) {
            $videoId = $m[1];
        }
        if ($videoId === '') return '<a href="' . htmlspecialchars($url) . '" target="_blank">YouTube</a>';
        return '<iframe width="100%" height="400" src="https://www.youtube.com/embed/' . htmlspecialchars($videoId)
            . '?autoplay=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
    }

    private static function twitchEmbed(string $url): string
    {
        $channel = '';
        if (preg_match('/twitch\.tv\/([a-zA-Z0-9_]+)/', $url, $m)) {
            $channel = $m[1];
        }
        if ($channel === '') return '<a href="' . htmlspecialchars($url) . '" target="_blank">Twitch</a>';
        return '<iframe src="https://player.twitch.tv/?channel=' . htmlspecialchars($channel)
            . '&parent=localhost" width="100%" height="400" allowfullscreen></iframe>';
    }
}
