<?php

namespace App\Models;

class ClubModel extends BaseModel
{
    protected string $table = 'clubs';

    public function listActive(): array
    {
        $stmt = $this->db->query("SELECT * FROM `clubs` WHERE is_active = 1 ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function search(string $q = '', int $page = 1, int $perPage = 20): array
    {
        $sql    = "SELECT * FROM `clubs`";
        $params = [];
        if ($q !== '') {
            $sql     .= " WHERE name LIKE ? OR city LIKE ? OR short_name LIKE ?";
            $like     = '%' . $q . '%';
            $params   = [$like, $like, $like];
        }
        $sql .= " ORDER BY name ASC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function needsOnboarding(int $clubId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM club_sports WHERE club_id = ?");
        $stmt->execute([$clubId]);
        $sportsCount = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM members WHERE club_id = ?");
        $stmt->execute([$clubId]);
        $membersCount = (int)$stmt->fetchColumn();

        return $sportsCount === 0 || $membersCount === 0;
    }

    public function stats(int $clubId): array
    {
        $out = ['members' => 0, 'sports' => 0, 'events_upcoming' => 0, 'payments_total' => 0.0];

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM members WHERE club_id = ? AND status='aktywny'");
        $stmt->execute([$clubId]);
        $out['members'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM club_sports WHERE club_id = ? AND is_active = 1");
        $stmt->execute([$clubId]);
        $out['sports'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM events WHERE club_id = ? AND event_date >= NOW()");
        $stmt->execute([$clubId]);
        $out['events_upcoming'] = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE club_id = ? AND YEAR(payment_date) = YEAR(CURDATE())");
        $stmt->execute([$clubId]);
        $out['payments_total'] = (float)$stmt->fetchColumn();

        return $out;
    }

    // ────────────────────────────────────────────────────────────
    // Public discovery (migration 095) — katalog klubow /discover
    // Wszystkie metody dotykajace publicznych endpointow MUSZA filtrowac
    // po public_discovery_enabled = 1 (defense in depth, oprocz check'u w controllerze).
    // ────────────────────────────────────────────────────────────

    /**
     * Slugify text — polskie znaki -> ASCII, lowercase, [a-z0-9-].
     * (Wzorzec ten sam co TournamentModel::slugify — czysta funkcja.)
     */
    public static function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $map  = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
        ];
        $text = strtr($text, $map);
        $text = preg_replace('/[^\x20-\x7E]/u', '', $text) ?? $text;
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-');
    }

    /**
     * Generuj globalnie unikalny public_slug dla klubu.
     * Format: <slugify(name)>-<6 hex>. Cap 80 znakow (limit kolumny).
     */
    public function generatePublicSlug(int $clubId): string
    {
        $stmt = $this->db->prepare("SELECT name FROM clubs WHERE id = ? LIMIT 1");
        $stmt->execute([$clubId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException("Club $clubId not found");
        }
        $base = self::slugify((string)$row['name']);
        if ($base === '') {
            $base = 'klub';
        }
        if (strlen($base) > 73) {
            $base = rtrim(substr($base, 0, 73), '-');
        }
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $suffix    = strtolower(bin2hex(random_bytes(3)));
            $candidate = $base . '-' . $suffix;
            $check = $this->db->prepare(
                "SELECT 1 FROM clubs WHERE public_slug = ? AND id <> ? LIMIT 1"
            );
            $check->execute([$candidate, $clubId]);
            if (!$check->fetch()) {
                return $candidate;
            }
        }
        throw new \RuntimeException('Could not generate unique public_slug');
    }

    /**
     * Lookup klubu po publicznym slug. Zwraca null gdy brak/disabled.
     */
    public function findByPublicSlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM clubs
             WHERE public_slug = ? AND public_discovery_enabled = 1 AND is_active = 1
             LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Wszystkie publicznie widoczne kluby. Opcjonalnie filtrowane po
     * sport_key (zlacza club_sports + sports) lub city (LIKE).
     *
     * @return array<int,array<string,mixed>>
     */
    public function listForDiscovery(?string $sportKey = null, ?string $city = null, int $limit = 500): array
    {
        $sql = "SELECT DISTINCT c.id, c.name, c.short_name, c.city, c.address,
                       c.latitude, c.longitude, c.public_slug, c.sports_offered_json,
                       c.description_short, c.contact_phone, c.website_url, c.website, c.email,
                       c.founded_year
                FROM clubs c";
        $params = [];

        if ($sportKey !== null && $sportKey !== '') {
            $sql .= " INNER JOIN club_sports cs ON cs.club_id = c.id AND cs.is_active = 1
                      INNER JOIN sports s ON s.id = cs.sport_id AND s.`key` = ?";
            $params[] = $sportKey;
        }

        $sql .= " WHERE c.public_discovery_enabled = 1 AND c.is_active = 1";

        if ($city !== null && $city !== '') {
            $sql     .= " AND c.city LIKE ?";
            $params[] = '%' . $city . '%';
        }

        $sql .= " ORDER BY c.name ASC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Lista sportow oferowanych przez klub (z aktywnych club_sports).
     * Uzywane przy renderowaniu landing page i przy odswiezeniu sports_offered_json.
     *
     * @return array<int,array{key:string,name:string,color:string,icon:?string}>
     */
    public function sportsForClub(int $clubId): array
    {
        $sql = "SELECT s.`key`, s.name, s.color, s.icon
                FROM club_sports cs
                JOIN sports s ON s.id = cs.sport_id
                WHERE cs.club_id = ? AND cs.is_active = 1
                ORDER BY s.sort_order, s.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clubId]);
        return $stmt->fetchAll();
    }

    /**
     * Agreguj wszystkie sporty oferowane przez kluby z opt-in (dla chip selectora).
     *
     * @return array<int,array{key:string,name:string,color:string,clubs_count:int}>
     */
    public function sportsDistribution(): array
    {
        $sql = "SELECT s.`key`, s.name, s.color, COUNT(DISTINCT c.id) AS clubs_count
                FROM clubs c
                JOIN club_sports cs ON cs.club_id = c.id AND cs.is_active = 1
                JOIN sports s ON s.id = cs.sport_id
                WHERE c.public_discovery_enabled = 1 AND c.is_active = 1
                GROUP BY s.`key`, s.name, s.color
                ORDER BY clubs_count DESC, s.name";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Przybliz liczbe czlonkow do "100+", "50+", "10+", "<10".
     */
    public static function approxMembers(int $count): string
    {
        if ($count >= 500) return '500+';
        if ($count >= 100) return '100+';
        if ($count >= 50)  return '50+';
        if ($count >= 25)  return '25+';
        if ($count >= 10)  return '10+';
        return $count > 0 ? '<10' : '—';
    }

    /**
     * Odswiez sports_offered_json cache (wywolac po add/remove club_sports lub
     * w panelu admina przy save).
     */
    public function refreshSportsOfferedJson(int $clubId): void
    {
        $sports = $this->sportsForClub($clubId);
        $stmt = $this->db->prepare("UPDATE clubs SET sports_offered_json = ? WHERE id = ?");
        $stmt->execute([json_encode($sports, JSON_UNESCAPED_UNICODE), $clubId]);
    }
}
