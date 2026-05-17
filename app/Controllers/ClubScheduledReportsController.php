<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Reports\ClubDashboardPdf;
use App\Helpers\Reports\ScheduledReportRunner;
use App\Helpers\Session;
use PDO;

/**
 * ClubScheduledReportsController — CRUD + preview + history dla scheduled
 * PDF dashboards (zarzad/admin).
 *
 * BEZPIECZENSTWO:
 *   - requireLogin + requireClubContext na kazdym route
 *   - requireRole(['zarzad','admin']) — tylko top-level moga zarzadzac
 *   - Wszystkie zapytania filtrowane przez club_id
 *   - CSRF na POST
 *   - Preview/Download PDF — strumieniowo z storage (poza /public)
 */
class ClubScheduledReportsController extends BaseController
{
    private const ROLES_ALLOWED = ['zarzad', 'admin'];
    private const SCHEDULES = ['weekly_mon', 'weekly_fri', 'monthly_1st', 'quarterly'];
    private const TEMPLATES = ['club_summary', 'financial', 'attendance', 'full_dashboard'];

    /** Lista zaplanowanych raportow klubu. */
    public function index(): void
    {
        $this->guard();
        $clubId = $this->currentClub();
        $pdo    = Database::pdo();
        $stmt = $pdo->prepare(
            "SELECT * FROM scheduled_reports WHERE club_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$clubId]);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($reports as &$r) {
            $r['recipients'] = ScheduledReportRunner::decodeRecipients((string)$r['recipient_emails']);
        }
        unset($r);

        $this->render('club/scheduled_reports/index', [
            'title'   => 'Raporty zaplanowane',
            'reports' => $reports,
            'csrf'    => Csrf::token(),
        ]);
    }

    /** Form: nowy raport. */
    public function create(): void
    {
        $this->guard();
        $this->render('club/scheduled_reports/form', [
            'title'     => 'Nowy zaplanowany raport',
            'report'    => null,
            'schedules' => self::SCHEDULES,
            'templates' => self::TEMPLATES,
            'csrf'      => Csrf::token(),
        ]);
    }

    /** Edit form. */
    public function edit(string $id): void
    {
        $this->guard();
        $report = $this->loadReport((int)$id);
        if ($report === null) {
            Session::flash('error', 'Raport nie istnieje lub brak dostepu.');
            $this->redirect('club/scheduled-reports');
        }
        $report['recipients_text'] = implode("\n", ScheduledReportRunner::decodeRecipients((string)$report['recipient_emails']));
        $report['config'] = is_string($report['config_json']) ? (json_decode($report['config_json'], true) ?: []) : [];
        $this->render('club/scheduled_reports/form', [
            'title'     => 'Edycja: ' . $report['name'],
            'report'    => $report,
            'schedules' => self::SCHEDULES,
            'templates' => self::TEMPLATES,
            'csrf'      => Csrf::token(),
        ]);
    }

    /** POST: zapisanie nowego lub aktualizacja. */
    public function store(): void
    {
        $this->guard();
        Csrf::verify();
        $clubId = $this->currentClub();

        $name      = trim((string)($_POST['name'] ?? ''));
        $schedule  = (string)($_POST['cron_schedule'] ?? '');
        $template  = (string)($_POST['template'] ?? 'full_dashboard');
        $active    = !empty($_POST['active']) ? 1 : 0;
        $recipRaw  = (string)($_POST['recipients'] ?? '');
        $editId    = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        $errors = $this->validateInput($name, $schedule, $template, $recipRaw);
        if ($errors !== []) {
            Session::flash('error', implode(' • ', $errors));
            $this->redirect($editId > 0
                ? 'club/scheduled-reports/' . $editId . '/edit'
                : 'club/scheduled-reports/create');
        }

        $recipients = ScheduledReportRunner::encodeRecipients(preg_split('/[\s,;]+/', $recipRaw) ?: []);

        // opt-in sekcje (checkbox values)
        $config = [
            'include_kpi'        => !empty($_POST['include_kpi']),
            'include_events'     => !empty($_POST['include_events']),
            'include_trainers'   => !empty($_POST['include_trainers']),
            'include_overdue'    => !empty($_POST['include_overdue']),
        ];
        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE);

        $pdo = Database::pdo();
        $nextSend = ScheduledReportRunner::calculateNext($schedule);

        if ($editId > 0) {
            $existing = $this->loadReport($editId);
            if ($existing === null) {
                Session::flash('error', 'Raport nie istnieje lub brak dostepu.');
                $this->redirect('club/scheduled-reports');
            }
            $stmt = $pdo->prepare(
                "UPDATE scheduled_reports
                 SET name=?, recipient_emails=?, cron_schedule=?, template=?, config_json=?, active=?, next_send_at=?
                 WHERE id=? AND club_id=?"
            );
            $stmt->execute([$name, $recipients, $schedule, $template, $configJson, $active, $nextSend, $editId, $clubId]);
            Session::flash('success', 'Raport zaktualizowany.');
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO scheduled_reports
                    (club_id, name, recipient_emails, cron_schedule, template, config_json, active, next_send_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$clubId, $name, $recipients, $schedule, $template, $configJson, $active, $nextSend]);
            Session::flash('success', 'Raport utworzony — pierwszy wysylka: ' . $nextSend);
        }
        $this->redirect('club/scheduled-reports');
    }

    /** POST: usuwanie. */
    public function delete(string $id): void
    {
        $this->guard();
        Csrf::verify();
        $report = $this->loadReport((int)$id);
        if ($report === null) {
            Session::flash('error', 'Raport nie istnieje lub brak dostepu.');
            $this->redirect('club/scheduled-reports');
        }
        $stmt = Database::pdo()->prepare(
            "DELETE FROM scheduled_reports WHERE id = ? AND club_id = ?"
        );
        $stmt->execute([(int)$id, $this->currentClub()]);
        Session::flash('success', 'Raport usuniety.');
        $this->redirect('club/scheduled-reports');
    }

    /** Preview: generuj PDF natychmiast i pobierz (bez wysylki). */
    public function preview(string $id): void
    {
        $this->guard();
        $report = $this->loadReport((int)$id);
        if ($report === null) {
            Session::flash('error', 'Raport nie istnieje lub brak dostepu.');
            $this->redirect('club/scheduled-reports');
        }
        $config = is_string($report['config_json']) ? (json_decode($report['config_json'], true) ?: []) : [];
        $html   = ClubDashboardPdf::generateHtml($this->currentClub(), (string)$report['template'], $config);
        \App\Helpers\PdfHelper::renderToPdf($html, 'preview_' . (int)$id . '.pdf', 'P');
    }

    /** Historia ostatnich 10 runs. */
    public function runs(string $id): void
    {
        $this->guard();
        $report = $this->loadReport((int)$id);
        if ($report === null) {
            Session::flash('error', 'Raport nie istnieje lub brak dostepu.');
            $this->redirect('club/scheduled-reports');
        }
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM scheduled_report_runs WHERE report_id = ?
             ORDER BY generated_at DESC LIMIT 10"
        );
        $stmt->execute([(int)$id]);
        $runs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $this->render('club/scheduled_reports/runs', [
            'title'  => 'Historia: ' . $report['name'],
            'report' => $report,
            'runs'   => $runs,
            'csrf'   => Csrf::token(),
        ]);
    }

    /** Pobierz PDF z zapisanego run (z storage). */
    public function downloadRun(string $runId): void
    {
        $this->guard();
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "SELECT srr.*, sr.club_id
             FROM scheduled_report_runs srr
             JOIN scheduled_reports sr ON sr.id = srr.report_id
             WHERE srr.id = ? LIMIT 1"
        );
        $stmt->execute([(int)$runId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int)$row['club_id'] !== $this->currentClub()) {
            http_response_code(404);
            echo 'Run nie istnieje.';
            return;
        }
        $relPath = (string)($row['pdf_path'] ?? '');
        if ($relPath === '') {
            http_response_code(404);
            echo 'PDF dla tego uruchomienia nie jest dostepny.';
            return;
        }
        // Defense: ban path traversal — relPath musi zaczynac sie storage/reports/{clubId}/
        $expectedPrefix = 'storage/reports/' . $this->currentClub() . '/';
        if (!str_starts_with($relPath, $expectedPrefix) || str_contains($relPath, '..')) {
            http_response_code(403);
            echo 'Niedozwolona sciezka.';
            return;
        }
        $abs = ROOT_PATH . '/' . $relPath;
        if (!is_file($abs)) {
            http_response_code(404);
            echo 'Plik nie istnieje na dysku.';
            return;
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($abs) . '"');
        header('Content-Length: ' . filesize($abs));
        readfile($abs);
        exit;
    }

    // ── helpers ───────────────────────────────────────────────────────

    private function guard(): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        if (!Auth::isSuperAdmin()) {
            $this->requireRole(self::ROLES_ALLOWED);
        }
    }

    private function loadReport(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM scheduled_reports WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([$id, $this->currentClub()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** @return list<string> */
    private function validateInput(string $name, string $schedule, string $template, string $recipRaw): array
    {
        $errors = [];
        if ($name === '' || mb_strlen($name) > 200) {
            $errors[] = 'Nieprawidlowa nazwa raportu (1-200 znakow).';
        }
        if (!in_array($schedule, self::SCHEDULES, true)) {
            $errors[] = 'Nieprawidlowy harmonogram.';
        }
        if (!in_array($template, self::TEMPLATES, true)) {
            $errors[] = 'Nieprawidlowy szablon.';
        }
        $emails = preg_split('/[\s,;]+/', $recipRaw) ?: [];
        $valid = 0;
        foreach ($emails as $e) {
            if ($e !== '' && filter_var(trim($e), FILTER_VALIDATE_EMAIL)) {
                $valid++;
            }
        }
        if ($valid < 1) {
            $errors[] = 'Wymagany co najmniej jeden prawidlowy adres email.';
        }
        return $errors;
    }
}
