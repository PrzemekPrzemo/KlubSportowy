<?php

namespace App\Models;

use App\Helpers\Encryption;

class MemberModel extends ClubScopedModel
{
    protected string $table = 'members';

    /** Pola szyfrowane — auto encrypt przy insert/update, auto decrypt przy findById. */
    private const ENCRYPTED_FIELDS = ['pesel', 'email', 'phone'];
    private const HASH_FIELDS = ['pesel' => 'pesel_hash', 'email' => 'email_hash', 'phone' => 'phone_hash'];

    public function insert(array $data): int
    {
        if (Encryption::isConfigured()) {
            foreach (self::ENCRYPTED_FIELDS as $field) {
                if (!empty($data[$field])) {
                    $hashField = self::HASH_FIELDS[$field] ?? null;
                    if ($hashField) {
                        $data[$hashField] = Encryption::hash($data[$field]);
                    }
                    $data[$field] = Encryption::encrypt($data[$field]);
                }
            }
        }
        return parent::insert($data);
    }

    public function update(int $id, array $data): bool
    {
        if (Encryption::isConfigured()) {
            foreach (self::ENCRYPTED_FIELDS as $field) {
                if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                    $hashField = self::HASH_FIELDS[$field] ?? null;
                    if ($hashField) {
                        $data[$hashField] = Encryption::hash($data[$field]);
                    }
                    $data[$field] = Encryption::encrypt($data[$field]);
                }
            }
        }
        return parent::update($id, $data);
    }

    public function findById(int $id): ?array
    {
        $row = parent::findById($id);
        return $row ? $this->decryptRow($row) : null;
    }

    private function decryptRow(array $row): array
    {
        if (!Encryption::isConfigured()) return $row;
        foreach (self::ENCRYPTED_FIELDS as $field) {
            if (!empty($row[$field])) {
                $decrypted = Encryption::decrypt($row[$field]);
                $row[$field] = $decrypted ?? $row[$field]; // fallback to raw if decrypt fails
            }
        }
        return $row;
    }

    public function search(string $q = '', ?string $status = null, ?int $clubSportId = null, int $page = 1, int $perPage = 20): array
    {
        $clubId = $this->clubId();
        $sql    = "SELECT m.* FROM members m";
        $params = [];

        if ($clubSportId !== null) {
            $sql .= " JOIN member_sports ms ON ms.member_id = m.id AND ms.club_sport_id = ?";
            $params[] = $clubSportId;
        }

        $sql .= " WHERE 1=1";

        if ($clubId !== null) {
            $sql     .= " AND m.club_id = ?";
            $params[] = $clubId;
        }

        if ($q !== '') {
            // Szukanie po first_name/last_name/member_number (nieszyfrowane)
            // + po email_hash (zaszyfrowane — hash query i porównaj)
            $like = '%' . $q . '%';
            $emailHash = Encryption::isConfigured() ? Encryption::hash($q) : '';
            $sql .= " AND (m.first_name LIKE ? OR m.last_name LIKE ?
                           OR m.member_number LIKE ?
                           OR m.email_hash = ?)";
            $params = [...$params, $like, $like, $like, $emailHash];
        }

        if ($status !== null && $status !== '') {
            $sql     .= " AND m.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY m.last_name, m.first_name";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function withSports(int $memberId): array
    {
        $row = $this->findById($memberId);
        if ($row === null) return [];

        $sql = "SELECT ms.id AS ms_id, ms.position, ms.jersey_number, ms.is_active,
                       ms.joined_at, ms.class_id, ms.discipline_id,
                       cs.id AS club_sport_id, s.`key` AS sport_key, s.name AS sport_name,
                       s.icon, s.color, s.team_sport,
                       mc.name AS class_name, mc.short_code AS class_code,
                       d.name AS discipline_name, d.short_code AS discipline_code
                FROM member_sports ms
                JOIN club_sports cs    ON cs.id = ms.club_sport_id
                JOIN sports s          ON s.id = cs.sport_id
                LEFT JOIN member_classes mc ON mc.id = ms.class_id
                LEFT JOIN disciplines d     ON d.id = ms.discipline_id
                WHERE ms.member_id = ?
                ORDER BY s.sort_order";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$memberId]);
        $row['sports'] = $stmt->fetchAll();
        return $row;
    }

    /** Kolejny wolny numer członkowski w klubie (format: {rok}-{seq}). */
    public function nextMemberNumber(int $clubId): string
    {
        $year = (int)date('Y');
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM members WHERE club_id = ? AND YEAR(join_date) = ?"
        );
        $stmt->execute([$clubId, $year]);
        $count = (int)$stmt->fetchColumn() + 1;
        return $year . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Slugify text — polskie znaki -> ASCII, lowercase, [a-z0-9-].
     * Wykorzystywane do public_profile_slug.
     */
    public static function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $map  = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
        ];
        $text = strtr($text, $map);
        // Strip non-ASCII (zapas po mapie polskich znakow)
        $text = preg_replace('/[^\x20-\x7E]/u', '', $text) ?? $text;
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-');
    }

    /**
     * Wygeneruj unikalny public_profile_slug dla zawodnika.
     *
     * Algorytm:
     *   1) slugify(first_name . '-' . last_name)
     *   2) Append member_number (lub random hex) gdy kolizja
     *   3) UNIQUE w bazie — sprawdzamy az do uzyskania wolnego
     *
     * Bypassuje club_scope (slug jest globalnie unikalny).
     */
    public function generatePublicSlug(int $memberId): string
    {
        $db = $this->db;
        $stmt = $db->prepare("SELECT first_name, last_name, member_number FROM members WHERE id = ? LIMIT 1");
        $stmt->execute([$memberId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException("Member $memberId not found");
        }

        $base = self::slugify(($row['first_name'] ?? '') . '-' . ($row['last_name'] ?? ''));
        if ($base === '') {
            $base = 'zawodnik';
        }
        // Ogranicz dlugosc bazowa (zostaw miejsce na suffix)
        if (strlen($base) > 100) {
            $base = substr($base, 0, 100);
            $base = rtrim($base, '-');
        }

        // Najpierw probuj bare slug
        $candidate = $base;
        if (!$this->slugExists($candidate, $memberId)) {
            return $candidate;
        }

        // Sprobuj z member_number
        if (!empty($row['member_number'])) {
            $candidate = $base . '-' . self::slugify((string)$row['member_number']);
            if (!$this->slugExists($candidate, $memberId)) {
                return $candidate;
            }
        }

        // Fallback: random suffix do 10 prob
        for ($i = 0; $i < 10; $i++) {
            $suffix    = substr(bin2hex(random_bytes(3)), 0, 5);
            $candidate = $base . '-' . $suffix;
            if (!$this->slugExists($candidate, $memberId)) {
                return $candidate;
            }
        }

        // Ostateczny fallback — czas + id
        return $base . '-' . dechex(time()) . '-' . $memberId;
    }

    private function slugExists(string $slug, int $excludeMemberId = 0): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM members WHERE public_profile_slug = ? AND id <> ? LIMIT 1"
        );
        $stmt->execute([$slug, $excludeMemberId]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Znajdz czlonka po public_profile_slug (bypassuje club_scope — slug globalny).
     * Zwraca tylko gdy visibility IN (public, club_only). private => null.
     */
    public function findByPublicSlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM members
             WHERE public_profile_slug = ?
               AND public_profile_visibility IN ('public', 'club_only')
               AND (is_anonymized IS NULL OR is_anonymized = 0)
             LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return $this->decryptRow($row);
    }

    /**
     * Lista wszystkich profili z visibility=public (do sitemap.xml / discovery).
     */
    public function listPublicProfiles(int $limit = 1000): array
    {
        $limit = max(1, min(10000, $limit));
        $stmt = $this->db->prepare(
            "SELECT id, first_name, last_name, public_profile_slug, public_profile_view_count, photo_path
             FROM members
             WHERE public_profile_visibility = 'public'
               AND public_profile_slug IS NOT NULL
               AND (is_anonymized IS NULL OR is_anonymized = 0)
             ORDER BY public_profile_view_count DESC, last_name ASC
             LIMIT " . $limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
