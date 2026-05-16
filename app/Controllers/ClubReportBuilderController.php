<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\CsvExporter;
use App\Helpers\Database;
use App\Helpers\PdfHelper;
use App\Helpers\Reports\DataSourceRegistry;
use App\Helpers\Reports\InvalidConfigException;
use App\Helpers\Reports\ReportBuilder;
use App\Helpers\Session;
use App\Helpers\View;
use PDO;

/**
 * ClubReportBuilderController — custom drag-drop report builder.
 *
 * BEZPIECZEŃSTWO:
 *   - Wszystkie route'y wymagają requireLogin + requireClubContext.
 *   - POST endpoints sprawdzają CSRF.
 *   - Multi-tenant: ReportBuilder auto-applies WHERE club_id = :club_id.
 *   - Sprawdzamy ownership saved_reports.club_id przed każdą operacją.
 *   - Config zapisany w DB jest zawalidowany przez ReportBuilder::validateConfig.
 */
class ClubReportBuilderController extends BaseController
{
    /** Lista zapisanych raportów + button "Nowy raport". */
    public function index(): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $clubId = $this->currentClub();
        $pdo    = Database::pdo();

        // Raporty klubu (własne usera + shared kolegów + globalne szablony)
        $userId = (int)Auth::id();
        $stmt = $pdo->prepare(
            "SELECT sr.*, u.username AS author_name
             FROM saved_reports sr
             LEFT JOIN users u ON u.id = sr.created_by_user_id
             WHERE (sr.club_id = :club_id AND (sr.created_by_user_id = :uid OR sr.is_shared = 1))
                OR sr.is_template = 1
             ORDER BY sr.is_template ASC, sr.updated_at DESC"
        );
        $stmt->execute([':club_id' => $clubId, ':uid' => $userId]);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->render('club/reports_builder/index', [
            'title'        => 'Kreator raportów',
            'reports'      => $reports,
            'dataSources'  => DataSourceRegistry::all(),
            'csrf'         => Csrf::token(),
        ]);
    }

    /** Kreator nowego raportu (drag-drop UI). */
    public function create(): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $defaultSource = $_GET['source'] ?? 'members';
        if (!DataSourceRegistry::get((string)$defaultSource)) {
            $defaultSource = 'members';
        }

        $this->render('club/reports_builder/builder', [
            'title'        => 'Nowy raport',
            'report'       => null,
            'dataSources'  => DataSourceRegistry::all(),
            'defaultSource'=> $defaultSource,
            'csrf'         => Csrf::token(),
        ]);
    }

    /** Edycja istniejącego raportu. */
    public function edit(string $id): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $report = $this->loadOwnedReport((int)$id, true);
        if ($report === null) {
            Session::flash('error', 'Raport nie istnieje lub brak dostępu.');
            $this->redirect('club/reports-builder');
        }

        $this->render('club/reports_builder/builder', [
            'title'        => 'Edycja raportu: ' . $report['name'],
            'report'       => $report,
            'dataSources'  => DataSourceRegistry::all(),
            'defaultSource'=> $report['data_source'],
            'csrf'         => Csrf::token(),
        ]);
    }

    /** POST: zapis nowego raportu. */
    public function store(): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        Csrf::verify();

        $clubId = $this->currentClub();
        $name   = trim((string)($_POST['name'] ?? ''));
        $desc   = trim((string)($_POST['description'] ?? ''));
        $source = (string)($_POST['data_source'] ?? '');
        $cfgRaw = (string)($_POST['config_json'] ?? '');
        $shared = !empty($_POST['is_shared']);

        $errors = $this->validateInput($name, $source, $cfgRaw);
        if ($errors !== []) {
            Session::flash('error', implode(' • ', $errors));
            $this->redirect('club/reports-builder/new?source=' . urlencode($source));
        }

        $cfg = json_decode($cfgRaw, true);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "INSERT INTO saved_reports (club_id, created_by_user_id, name, description, data_source, config_json, is_shared)
             VALUES (:club_id, :uid, :name, :desc, :ds, :cfg, :shared)"
        );
        $stmt->execute([
            ':club_id' => $clubId,
            ':uid'     => Auth::id(),
            ':name'    => $name,
            ':desc'    => $desc !== '' ? $desc : null,
            ':ds'      => $source,
            ':cfg'     => json_encode($cfg, JSON_UNESCAPED_UNICODE),
            ':shared'  => $shared ? 1 : 0,
        ]);

        Session::flash('success', 'Raport zapisany.');
        $this->redirect('club/reports-builder/' . (int)$pdo->lastInsertId() . '/run');
    }

    /** POST: aktualizacja istniejącego raportu. */
    public function update(string $id): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        Csrf::verify();

        $report = $this->loadOwnedReport((int)$id, false);
        if ($report === null) {
            Session::flash('error', 'Brak dostępu do edycji.');
            $this->redirect('club/reports-builder');
        }

        $name   = trim((string)($_POST['name'] ?? ''));
        $desc   = trim((string)($_POST['description'] ?? ''));
        $source = (string)($_POST['data_source'] ?? '');
        $cfgRaw = (string)($_POST['config_json'] ?? '');
        $shared = !empty($_POST['is_shared']);

        $errors = $this->validateInput($name, $source, $cfgRaw);
        if ($errors !== []) {
            Session::flash('error', implode(' • ', $errors));
            $this->redirect('club/reports-builder/' . (int)$id . '/edit');
        }

        $cfg = json_decode($cfgRaw, true);
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "UPDATE saved_reports
             SET name=:name, description=:desc, data_source=:ds, config_json=:cfg, is_shared=:shared
             WHERE id=:id AND club_id=:club_id"
        );
        $stmt->execute([
            ':name' => $name, ':desc' => $desc !== '' ? $desc : null,
            ':ds' => $source, ':cfg' => json_encode($cfg, JSON_UNESCAPED_UNICODE),
            ':shared' => $shared ? 1 : 0,
            ':id' => (int)$id, ':club_id' => $this->currentClub(),
        ]);

        Session::flash('success', 'Raport zaktualizowany.');
        $this->redirect('club/reports-builder/' . (int)$id . '/run');
    }

    /** POST: usuń raport. */
    public function delete(string $id): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        Csrf::verify();

        $report = $this->loadOwnedReport((int)$id, false);
        if ($report === null) {
            Session::flash('error', 'Brak dostępu.');
            $this->redirect('club/reports-builder');
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('DELETE FROM saved_reports WHERE id=:id AND club_id=:club_id');
        $stmt->execute([':id' => (int)$id, ':club_id' => $this->currentClub()]);
        Session::flash('success', 'Raport usunięty.');
        $this->redirect('club/reports-builder');
    }

    /** Wykonanie raportu + render wyniku. */
    public function run(string $id): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $report = $this->loadOwnedReport((int)$id, true);
        if ($report === null) {
            Session::flash('error', 'Raport nie istnieje lub brak dostępu.');
            $this->redirect('club/reports-builder');
        }

        $config = json_decode($report['config_json'], true) ?? [];
        try {
            $result = (new ReportBuilder())->execute(
                $this->currentClub(),
                (string)$report['data_source'],
                is_array($config) ? $config : []
            );
        } catch (InvalidConfigException $e) {
            Session::flash('error', 'Nieprawidłowa konfiguracja raportu: ' . $e->getMessage());
            $this->redirect('club/reports-builder/' . (int)$id . '/edit');
        } catch (\Throwable $e) {
            Session::flash('error', 'Błąd wykonania raportu: ' . $e->getMessage());
            $this->redirect('club/reports-builder');
        }

        // Audyt + statystyki (tylko jeśli raport jest klubowy, nie globalny szablon)
        if (!empty($report['club_id'])) {
            $this->logRun((int)$id, $result['total'], $result['duration_ms']);
        }

        $this->render('club/reports_builder/result', [
            'title'  => $report['name'],
            'report' => $report,
            'result' => $result,
            'csrf'   => Csrf::token(),
        ]);
    }

    /** POST: live preview (AJAX) — zwraca JSON z top 10 wierszy. */
    public function preview(): void
    {
        $this->requireLogin();
        $this->requireClubContext();
        Csrf::verify();

        $source = (string)($_POST['data_source'] ?? '');
        $cfgRaw = (string)($_POST['config_json'] ?? '{}');
        $cfg = json_decode($cfgRaw, true);
        if (!is_array($cfg)) {
            $cfg = [];
        }
        $cfg['limit'] = 10;

        try {
            $result = (new ReportBuilder())->execute($this->currentClub(), $source, $cfg);
            $this->json(['ok' => true, 'result' => $result]);
        } catch (InvalidConfigException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => 'Błąd: ' . $e->getMessage()], 500);
        }
    }

    /** Export CSV. */
    public function exportCsv(string $id): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $report = $this->loadOwnedReport((int)$id, true);
        if ($report === null) {
            $this->redirect('club/reports-builder');
        }

        $config = json_decode($report['config_json'], true) ?? [];
        $config = is_array($config) ? $config : [];
        // CSV nie powinno mieć ograniczenia podglądu — użyj limit z config (max 10k)
        try {
            $result = (new ReportBuilder())->execute(
                $this->currentClub(), (string)$report['data_source'], $config
            );
        } catch (\Throwable $e) {
            Session::flash('error', 'Eksport nieudany: ' . $e->getMessage());
            $this->redirect('club/reports-builder');
        }

        $headers = array_map(fn($c) => (string)$c['label'], $result['columns']);
        $keys    = array_map(fn($c) => (string)$c['key'], $result['columns']);

        $rows = [];
        foreach ($result['rows'] as $r) {
            $row = [];
            foreach ($keys as $k) {
                $row[] = (string)($r[$k] ?? '');
            }
            $rows[] = $row;
        }

        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string)$report['name']) ?: 'raport';
        CsvExporter::download($safe . '_' . date('Y-m-d') . '.csv', $headers, $rows);
    }

    /** Export PDF (najprostszy — tabela HTML → PDF). */
    public function exportPdf(string $id): void
    {
        $this->requireLogin();
        $this->requireClubContext();

        $report = $this->loadOwnedReport((int)$id, true);
        if ($report === null) {
            $this->redirect('club/reports-builder');
        }
        $config = json_decode($report['config_json'], true) ?? [];
        $config = is_array($config) ? $config : [];
        try {
            $result = (new ReportBuilder())->execute(
                $this->currentClub(), (string)$report['data_source'], $config
            );
        } catch (\Throwable $e) {
            Session::flash('error', 'Eksport nieudany: ' . $e->getMessage());
            $this->redirect('club/reports-builder');
        }

        $clubId = $this->currentClub();
        $header = PdfHelper::getClubHeader($clubId);
        $footer = PdfHelper::getSystemFooter();

        $html = View::partial('pdf/report_builder', [
            'clubHeader'   => $header,
            'systemFooter' => $footer,
            'report'       => $report,
            'result'       => $result,
            'generated'    => date('d.m.Y H:i'),
        ]);

        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string)$report['name']) ?: 'raport';
        PdfHelper::renderToPdf($html, $safe . '_' . date('Y-m-d') . '.pdf', 'L');
    }

    // ── Private helpers ──────────────────────────────────────

    /**
     * Ładuje raport jeśli należy do current club LUB jest globalnym szablonem.
     * @param bool $allowTemplate dopuść globalne szablony do podglądu
     * @return array<string, mixed>|null
     */
    private function loadOwnedReport(int $id, bool $allowTemplate): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM saved_reports WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            return null;
        }
        if ($allowTemplate && (int)$r['is_template'] === 1) {
            return $r;
        }
        if ((int)($r['club_id'] ?? 0) === $this->currentClub()) {
            return $r;
        }
        return null;
    }

    /**
     * Waliduje input z formularza save/update. Zwraca [] jeśli OK.
     * @return string[]
     */
    private function validateInput(string $name, string $source, string $configJson): array
    {
        $errors = [];
        if ($name === '' || mb_strlen($name) > 200) {
            $errors[] = 'Nazwa raportu jest wymagana (max 200 znaków).';
        }
        if (DataSourceRegistry::get($source) === null) {
            $errors[] = 'Niepoprawne źródło danych.';
            return $errors; // dalsza walidacja bez sensu
        }
        $cfg = json_decode($configJson, true);
        if (!is_array($cfg)) {
            $errors[] = 'Niepoprawna konfiguracja JSON.';
            return $errors;
        }
        $validationErrors = (new ReportBuilder())->validateConfig($source, $cfg);
        foreach ($validationErrors as $e) {
            $errors[] = $e;
        }
        return $errors;
    }

    private function logRun(int $reportId, int $rows, int $duration): void
    {
        try {
            $pdo = Database::pdo();
            $pdo->prepare(
                'INSERT INTO report_runs (report_id, user_id, rows_returned, duration_ms) VALUES (?,?,?,?)'
            )->execute([$reportId, Auth::id(), $rows, $duration]);
            $pdo->prepare(
                'UPDATE saved_reports SET last_run_at = NOW(), run_count = run_count + 1 WHERE id = ?'
            )->execute([$reportId]);
        } catch (\Throwable) {
            // brak audit log nie powinien rozwalić raportu
        }
    }
}
