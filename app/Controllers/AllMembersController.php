<?php

namespace App\Controllers;

use App\Helpers\Database;

/**
 * Faza P.4 — widok "Wszyscy zawodnicy w klubie" (cross-sport).
 *
 * Pokazuje listę wszystkich członków klubu z:
 *   - aktywnymi sekcjami sportowymi (badge'e)
 *   - subskrypcjami opłat (count + total monthly)
 *   - saldem (suma overdue + outstanding dues)
 *   - statusem (aktywny/zawieszony itd.)
 *
 * Pomaga klubowi widzieć całość zawodników zamiast filtrować per sport.
 *
 * Pełna izolacja per klub (manualne WHERE club_id w SQL z join'ami).
 */
class AllMembersController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
    }

    public function index(): void
    {
        $clubId = $this->currentClub();
        $db     = Database::pdo();

        $statusFilter = $_GET['status'] ?? null;
        $sportIdFilter = !empty($_GET['sport_id']) ? (int)$_GET['sport_id'] : null;

        // Główne query — agreguj po członku
        $sql = "SELECT m.id, m.first_name, m.last_name, m.member_number,
                       m.status, m.email, m.phone,
                       (SELECT COUNT(*) FROM member_sports ms
                        JOIN club_sports cs ON cs.id = ms.club_sport_id
                        WHERE ms.member_id = m.id) AS sports_count,
                       (SELECT GROUP_CONCAT(DISTINCT s.name SEPARATOR ', ')
                        FROM member_sports ms
                        JOIN club_sports cs ON cs.id = ms.club_sport_id
                        JOIN sports s        ON s.id = cs.sport_id
                        WHERE ms.member_id = m.id) AS sports_list,
                       (SELECT COUNT(*) FROM member_fee_assignments mfa
                        WHERE mfa.member_id = m.id AND mfa.club_id = m.club_id
                          AND mfa.status = 'active') AS active_subscriptions,
                       (SELECT COALESCE(SUM(pd.net_amount - pd.paid_amount), 0)
                        FROM payment_dues pd
                        WHERE pd.member_id = m.id AND pd.club_id = m.club_id
                          AND pd.status IN ('pending','partial','overdue')) AS outstanding_balance,
                       (SELECT COUNT(*) FROM payment_dues pd
                        WHERE pd.member_id = m.id AND pd.club_id = m.club_id
                          AND (pd.status = 'overdue'
                               OR (pd.status IN ('pending','partial') AND pd.due_date < CURDATE()))) AS overdue_count
                FROM members m
                WHERE m.club_id = ?";
        $params = [$clubId];

        if ($statusFilter) {
            $sql .= " AND m.status = ?";
            $params[] = $statusFilter;
        }
        if ($sportIdFilter) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM member_sports ms
                JOIN club_sports cs ON cs.id = ms.club_sport_id
                WHERE ms.member_id = m.id AND cs.sport_id = ?
            )";
            $params[] = $sportIdFilter;
        }

        $sql .= " ORDER BY m.last_name, m.first_name";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $members = $stmt->fetchAll();

        // Lista sportów klubu do filtra
        $sportsStmt = $db->prepare(
            "SELECT s.id, s.name FROM club_sports cs
             JOIN sports s ON s.id = cs.sport_id
             WHERE cs.club_id = ? AND cs.is_active = 1
             ORDER BY s.name"
        );
        $sportsStmt->execute([$clubId]);
        $clubSports = $sportsStmt->fetchAll();

        $statuses = [
            'aktywny'    => 'Aktywny',
            'zawieszony' => 'Zawieszony',
            'wykreslony' => 'Wykreślony',
            'urlop'      => 'Urlop',
        ];

        $this->render('members_all/index', [
            'title'        => 'Wszyscy zawodnicy',
            'members'      => $members,
            'clubSports'   => $clubSports,
            'statuses'     => $statuses,
            'statusFilter' => $statusFilter,
            'sportFilter'  => $sportIdFilter,
        ]);
    }
}
