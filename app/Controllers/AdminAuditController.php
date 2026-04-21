<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Models\ActivityLogModel;
use PDO;

class AdminAuditController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    /**
     * Tables that MUST have valid club_id referencing clubs(id).
     */
    private const CLUB_SCOPED_TABLES = [
        'members', 'member_sports', 'member_medical_exams', 'member_licenses',
        'member_consents', 'fee_rates', 'payments', 'teams', 'events',
        'event_entries', 'event_results', 'club_subscriptions', 'billing_invoices',
        'club_sports', 'trainings', 'calendar_event_categories', 'calendar_events',
        'announcements', 'weapons', 'weapon_assignments', 'ammo_stock',
        'ammo_transactions', 'judge_licenses',
        // New sport modules (S1-S10)
        'bjj_belts', 'bjj_results',
        'gymnastics_results', 'gymnastics_minor_consents',
        'floorball_teams', 'floorball_matches',
        'padel_courts', 'padel_pairs',
        'sailing_boats', 'sailing_races',
        'triathlon_results',
        'crossfit_wods', 'crossfit_scores', 'crossfit_prs',
        'association_meetings',
        // Batches N1-N10 (10 polskich związków sportowych)
        'swimming_results',
        'tennis_matches', 'tennis_rankings', 'tennis_courts',
        'boxing_results', 'boxing_medicals',
        'handball_teams', 'handball_players', 'handball_matches', 'handball_events',
        'cycling_results', 'cycling_athletes', 'cycling_ftp_tests',
        'icehockey_teams', 'icehockey_players', 'icehockey_matches', 'icehockey_events',
        'fencing_results', 'fencing_fencers',
        'taekwondo_belts', 'taekwondo_results',
        'weightlifting_results', 'weightlifting_records',
        'climbing_results', 'climbing_routes', 'climbing_sends',
        // Profile extensions (M1-M6)
        'body_metrics', 'member_emergency_contacts', 'athlete_training_logs',
        'anti_doping_declarations', 'minor_consents',
    ];

    public function isolation(): void
    {
        $checks = $this->runChecks();

        $passed = 0;
        $failed = 0;
        $warned = 0;
        foreach ($checks as $c) {
            if ($c['status'] === 'pass') $passed++;
            elseif ($c['status'] === 'fail') $failed++;
            else $warned++;
        }

        $this->render('admin/audit/isolation', [
            'title'    => 'Audyt izolacji danych',
            'checks'   => $checks,
            'summary'  => ['pass' => $passed, 'fail' => $failed, 'warning' => $warned, 'total' => count($checks)],
            'ranAt'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function exportReport(): void
    {
        Csrf::verify();
        $checks = $this->runChecks();

        (new ActivityLogModel())->log('audit_isolation_export', 'audit', null);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit_isolation_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['status', 'check', 'affected', 'description']);
        foreach ($checks as $c) {
            fputcsv($out, [$c['status'], $c['name'], (int)($c['count'] ?? 0), $c['description'] ?? '']);
        }
        fclose($out);
    }

    private function runChecks(): array
    {
        $db = Database::pdo();
        $checks = [];

        // 1. Orphaned records — club_id nie istnieje w clubs
        foreach (self::CLUB_SCOPED_TABLES as $t) {
            try {
                $stmt = $db->prepare(
                    "SELECT COUNT(*) FROM `{$t}` t
                     LEFT JOIN clubs c ON c.id = t.club_id
                     WHERE c.id IS NULL"
                );
                $stmt->execute();
                $n = (int)$stmt->fetchColumn();
                $sample = [];
                if ($n > 0) {
                    $stmt = $db->prepare(
                        "SELECT t.id, t.club_id FROM `{$t}` t
                         LEFT JOIN clubs c ON c.id = t.club_id
                         WHERE c.id IS NULL LIMIT 5"
                    );
                    $stmt->execute();
                    $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                $checks[] = [
                    'name'        => "Osierocone rekordy w `{$t}`",
                    'description' => "Rekordy w {$t} z club_id nieistniejącym w clubs",
                    'status'      => $n === 0 ? 'pass' : 'fail',
                    'count'       => $n,
                    'sample'      => $sample,
                ];
            } catch (\Throwable $e) {
                $checks[] = [
                    'name'        => "Osierocone rekordy w `{$t}`",
                    'description' => 'Błąd zapytania: ' . $e->getMessage(),
                    'status'      => 'warning',
                    'count'       => 0,
                    'sample'      => [],
                ];
            }
        }

        // 2. NULL club_id w tabelach wymagających club_id
        foreach (self::CLUB_SCOPED_TABLES as $t) {
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM `{$t}` WHERE club_id IS NULL");
                $n = (int)$stmt->fetchColumn();
                if ($n > 0) {
                    $checks[] = [
                        'name'        => "Puste club_id w `{$t}`",
                        'description' => "Rekordy w {$t} z club_id NULL (naruszenie izolacji)",
                        'status'      => 'fail',
                        'count'       => $n,
                        'sample'      => [],
                    ];
                }
                // pass case omitted to reduce noise — only raise when problems exist
            } catch (\Throwable) {
                // column may not exist — silently skip
            }
        }

        // 3. Duplikaty user_clubs (ten sam user_id + club_id)
        try {
            $stmt = $db->query(
                "SELECT user_id, club_id, COUNT(*) c FROM user_clubs
                 GROUP BY user_id, club_id HAVING c > 1"
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $n = count($rows);
            $checks[] = [
                'name'        => 'Duplikaty user_clubs (user_id + club_id)',
                'description' => 'Przypisanie użytkownika do klubu powinno być unikalne',
                'status'      => $n === 0 ? 'pass' : 'fail',
                'count'       => $n,
                'sample'      => array_slice($rows, 0, 5),
            ];
        } catch (\Throwable $e) {
            $checks[] = [
                'name'        => 'Duplikaty user_clubs',
                'description' => 'Błąd: ' . $e->getMessage(),
                'status'      => 'warning',
                'count'       => 0,
                'sample'      => [],
            ];
        }

        // 4. Liczby członków vs limity subskrypcji (uwzględniając override)
        try {
            $stmt = $db->query(
                "SELECT c.id, c.name,
                        (SELECT COUNT(*) FROM members m WHERE m.club_id = c.id AND m.status = 'aktywny') AS member_count,
                        COALESCE(cs.max_members_override, sp.max_members) AS limit_members,
                        sp.name AS plan_name
                 FROM clubs c
                 LEFT JOIN club_subscriptions cs ON cs.club_id = c.id
                 LEFT JOIN subscription_plans sp ON sp.id = cs.plan_id
                 HAVING limit_members IS NOT NULL
                    AND limit_members > 0
                    AND member_count > limit_members"
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $n = count($rows);
            $checks[] = [
                'name'        => 'Przekroczenie limitu członków (uwzględniając override)',
                'description' => 'Kluby z liczbą aktywnych członków > limit planu',
                'status'      => $n === 0 ? 'pass' : 'warning',
                'count'       => $n,
                'sample'      => array_slice($rows, 0, 5),
            ];
        } catch (\Throwable $e) {
            $checks[] = [
                'name'        => 'Przekroczenie limitu członków',
                'description' => 'Błąd: ' . $e->getMessage(),
                'status'      => 'warning',
                'count'       => 0,
                'sample'      => [],
            ];
        }

        // 5. billing_invoices wskazuje na nieaktywny klub
        try {
            $stmt = $db->query(
                "SELECT i.id, i.number, i.club_id
                 FROM billing_invoices i
                 LEFT JOIN clubs c ON c.id = i.club_id
                 WHERE c.id IS NULL OR c.is_active = 0"
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $n = count($rows);
            $checks[] = [
                'name'        => 'Faktury dla nieaktywnego/nieistniejącego klubu',
                'description' => 'billing_invoices.club_id wskazuje na klub nieaktywny lub usunięty',
                'status'      => $n === 0 ? 'pass' : 'warning',
                'count'       => $n,
                'sample'      => array_slice($rows, 0, 5),
            ];
        } catch (\Throwable $e) {
            $checks[] = [
                'name'        => 'Faktury cross-klub',
                'description' => 'Błąd: ' . $e->getMessage(),
                'status'      => 'warning',
                'count'       => 0,
                'sample'      => [],
            ];
        }

        // 6. Członkowie przypisani do sport z innego klubu (club_sports)
        try {
            $stmt = $db->query(
                "SELECT ms.member_id, ms.club_sport_id, m.club_id AS member_club, cs.club_id AS cs_club
                 FROM member_sports ms
                 JOIN members m ON m.id = ms.member_id
                 JOIN club_sports cs ON cs.id = ms.club_sport_id
                 WHERE m.club_id <> cs.club_id LIMIT 20"
            );
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $n = count($rows);
            $checks[] = [
                'name'        => 'Cross-club: member_sports.club_sport należy do innego klubu',
                'description' => 'Członek przypisany do sportu z innego klubu — naruszenie izolacji',
                'status'      => $n === 0 ? 'pass' : 'fail',
                'count'       => $n,
                'sample'      => array_slice($rows, 0, 5),
            ];
        } catch (\Throwable) {
            // silently skip if schema differs
        }

        return $checks;
    }
}
