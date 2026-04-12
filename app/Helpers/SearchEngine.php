<?php

namespace App\Helpers;

/**
 * SearchEngine — Elasticsearch integration with SQL LIKE fallback.
 */
class SearchEngine
{
    /**
     * Index a document in Elasticsearch.
     */
    public static function index(string $type, int $id, array $data): void
    {
        $url = self::esUrl();
        if ($url === null) {
            return; // ES not configured, skip silently
        }

        $endpoint = $url . '/' . $type . '/_doc/' . $id;
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => json_encode($data, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Search across indexed documents.
     */
    public static function search(string $query, ?string $type = null, ?int $clubId = null, int $limit = 20): array
    {
        $url = self::esUrl();
        if ($url === null) {
            return self::fallbackSearch($query, $type, $clubId, $limit);
        }

        $index = $type ?? '_all';
        $endpoint = $url . '/' . $index . '/_search';

        $must = [
            [
                'multi_match' => [
                    'query'  => $query,
                    'fields' => ['name^3', 'first_name^2', 'last_name^2', 'description', 'location', 'email'],
                    'type'   => 'best_fields',
                    'fuzziness' => 'AUTO',
                ],
            ],
        ];

        if ($clubId !== null) {
            $must[] = ['term' => ['club_id' => $clubId]];
        }

        $body = [
            'query' => ['bool' => ['must' => $must]],
            'size'  => $limit,
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return self::fallbackSearch($query, $type, $clubId, $limit);
        }

        $result = json_decode($response, true);
        if (empty($result['hits']['hits'])) {
            return [];
        }

        $results = [];
        foreach ($result['hits']['hits'] as $hit) {
            $source = $hit['_source'] ?? [];
            $source['_id']    = $hit['_id'];
            $source['_type']  = $hit['_index'];
            $source['_score'] = $hit['_score'];
            $results[] = $source;
        }
        return $results;
    }

    /**
     * Delete a document from Elasticsearch.
     */
    public static function delete(string $type, int $id): void
    {
        $url = self::esUrl();
        if ($url === null) {
            return;
        }

        $endpoint = $url . '/' . $type . '/_doc/' . $id;
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_CONNECTTIMEOUT => 2,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Get Elasticsearch URL from settings. Returns null if not configured.
     */
    private static function esUrl(): ?string
    {
        // Check environment variable first
        $url = getenv('ELASTICSEARCH_URL') ?: null;
        if ($url) {
            return rtrim($url, '/');
        }

        // Check settings table
        try {
            $db = Database::pdo();
            $stmt = $db->prepare("SELECT `value` FROM `settings` WHERE `key` = 'elasticsearch_url' LIMIT 1");
            $stmt->execute();
            $val = $stmt->fetchColumn();
            if ($val !== false && $val !== '' && $val !== null) {
                return rtrim((string)$val, '/');
            }
        } catch (\Throwable) {}

        return null;
    }

    /**
     * Fallback search using SQL LIKE when Elasticsearch is unavailable.
     */
    private static function fallbackSearch(string $query, ?string $type, ?int $clubId, int $limit): array
    {
        $db   = Database::pdo();
        $like = '%' . $query . '%';
        $results = [];

        $tables = [];
        if ($type === null || $type === 'members') {
            $tables[] = 'members';
        }
        if ($type === null || $type === 'events') {
            $tables[] = 'events';
        }
        if ($type === null || $type === 'trainings') {
            $tables[] = 'trainings';
        }

        foreach ($tables as $table) {
            $params = [];
            switch ($table) {
                case 'members':
                    $sql = "SELECT id, first_name, last_name, email, club_id, 'members' AS _type
                            FROM members
                            WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
                    $params = [$like, $like, $like];
                    break;
                case 'events':
                    $sql = "SELECT id, name, event_date, location, club_id, 'events' AS _type
                            FROM events
                            WHERE (name LIKE ? OR location LIKE ?)";
                    $params = [$like, $like];
                    break;
                case 'trainings':
                    $sql = "SELECT id, name, start_time, club_id, 'trainings' AS _type
                            FROM trainings
                            WHERE name LIKE ?";
                    $params = [$like];
                    break;
            }

            if ($clubId !== null) {
                $sql .= " AND club_id = ?";
                $params[] = $clubId;
            }
            $sql .= " LIMIT " . (int)$limit;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            foreach ($stmt->fetchAll() as $row) {
                $results[] = $row;
            }
        }

        return $results;
    }
}
