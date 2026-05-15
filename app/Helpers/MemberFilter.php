<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\MemberModel;

/**
 * Współdzielony resolver filtrów członków — używany przez:
 *   - bulk export (XLSX/CSV)
 *   - bulk fee assignments
 *   - bulk invoices
 *   - bulk email/SMS campaigns
 *
 * Buduje WHERE-clause + paramsy z $_GET/$_POST kryteriów.
 * Wszystkie zwracane wiersze są ograniczone do bieżącego klubu
 * (przyjmowanego jako argument — kontroler bierze go z ClubContext).
 */
class MemberFilter
{
    /**
     * Z surowych $_POST/$_GET zbuduj znormalizowane kryteria.
     *
     * @return array{
     *   sport_id:?int, status:?string, gender:?string,
     *   age_min:?int, age_max:?int,
     *   fees:?string   // 'paid' | 'overdue' | null
     * }
     */
    public static function fromRequest(array $req): array
    {
        $intOrNull = fn($v) => (isset($v) && $v !== '' && $v !== null) ? (int)$v : null;
        $strOrNull = fn($v) => (isset($v) && is_string($v) && trim($v) !== '') ? trim($v) : null;

        return [
            'sport_id' => $intOrNull($req['sport_id'] ?? null),
            'status'   => $strOrNull($req['status']   ?? null),
            'gender'   => $strOrNull($req['gender']   ?? null),
            'age_min'  => $intOrNull($req['age_min']  ?? null),
            'age_max'  => $intOrNull($req['age_max']  ?? null),
            'fees'     => $strOrNull($req['fees']     ?? null), // 'paid'|'overdue'
        ];
    }

    /**
     * Zwróć listę zawodników (z deszyfrowanymi polami) pasujących do filtra.
     *
     * Bezpieczeństwo: wymusza scope club_id niezależnie od kontekstu —
     * caller wskazuje $clubId, a logika SQL filtruje WHERE m.club_id = ?.
     *
     * @return list<array<string,mixed>>
     */
    public static function query(int $clubId, array $filter, int $maxRows = 5000): array
    {
        $sql    = "SELECT DISTINCT m.* FROM members m";
        $params = [];

        if (!empty($filter['sport_id'])) {
            $sql .= " JOIN member_sports ms ON ms.member_id = m.id AND ms.club_sport_id = ?";
            $params[] = (int)$filter['sport_id'];
        }

        if (($filter['fees'] ?? null) === 'overdue') {
            $sql .= " JOIN payment_dues pd ON pd.member_id = m.id
                      AND pd.status IN ('pending','overdue','partial')
                      AND pd.due_date < CURDATE()";
        } elseif (($filter['fees'] ?? null) === 'paid') {
            // Członkowie którzy mają jakąkolwiek opłaconą należność w ostatnich 90 dniach
            $sql .= " JOIN payment_dues pd ON pd.member_id = m.id
                      AND pd.status = 'paid'
                      AND pd.updated_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
        }

        $sql     .= " WHERE m.club_id = ?";
        $params[] = $clubId;

        if (!empty($filter['status'])) {
            $sql     .= " AND m.status = ?";
            $params[] = $filter['status'];
        }
        if (!empty($filter['gender'])) {
            $sql     .= " AND m.gender = ?";
            $params[] = $filter['gender'];
        }
        if (!empty($filter['age_min'])) {
            $sql     .= " AND m.birth_date IS NOT NULL
                          AND TIMESTAMPDIFF(YEAR, m.birth_date, CURDATE()) >= ?";
            $params[] = (int)$filter['age_min'];
        }
        if (!empty($filter['age_max'])) {
            $sql     .= " AND m.birth_date IS NOT NULL
                          AND TIMESTAMPDIFF(YEAR, m.birth_date, CURDATE()) <= ?";
            $params[] = (int)$filter['age_max'];
        }

        $sql .= " ORDER BY m.last_name, m.first_name LIMIT " . max(1, $maxRows);

        $db   = Database::pdo();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Decrypt sensitive fields (email, phone, pesel) — przelot przez MemberModel::findById
        // byłby ~N+1, więc dekryptujemy in-place.
        if (Encryption::isConfigured()) {
            foreach ($rows as &$r) {
                foreach (['email', 'phone', 'pesel'] as $field) {
                    if (!empty($r[$field])) {
                        $dec = Encryption::decrypt($r[$field]);
                        $r[$field] = $dec ?? $r[$field];
                    }
                }
            }
            unset($r);
        }

        return $rows;
    }

    /**
     * Krótki opis filtra dla flash/log — bez wartości PII.
     */
    public static function describe(array $filter): string
    {
        $bits = [];
        if (!empty($filter['sport_id'])) $bits[] = 'sport=' . (int)$filter['sport_id'];
        if (!empty($filter['status']))   $bits[] = 'status=' . preg_replace('/[^a-z]/i', '', (string)$filter['status']);
        if (!empty($filter['gender']))   $bits[] = 'gender=' . preg_replace('/[^a-z]/i', '', (string)$filter['gender']);
        if (!empty($filter['age_min']))  $bits[] = 'age_min=' . (int)$filter['age_min'];
        if (!empty($filter['age_max']))  $bits[] = 'age_max=' . (int)$filter['age_max'];
        if (!empty($filter['fees']))     $bits[] = 'fees=' . preg_replace('/[^a-z]/i', '', (string)$filter['fees']);
        return $bits ? implode(',', $bits) : 'all';
    }
}
