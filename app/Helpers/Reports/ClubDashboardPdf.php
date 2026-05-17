<?php

declare(strict_types=1);

namespace App\Helpers\Reports;

use App\Helpers\Database;
use App\Helpers\PdfHelper;
use PDO;

/**
 * Generator scheduled PDF dashboards z KPI klubu.
 *
 * Templates:
 *   - full_dashboard (default) — KPI cards + frekwencja last 4w + eventy + top 3 trenerow
 *   - financial — wplywy/wydatki + zaleglosci (zanonimizowane) + split metod platnosci
 *   - attendance — frekwencja per sekcja + trendy + members z najnizsza frekwencja
 *   - club_summary — top 3 KPI + nadchodzace eventy
 *
 * BEZPIECZENSTWO:
 *   - Wszystkie zapytania filtrowane WHERE club_id = ? (multi-tenant)
 *   - W sekcji `financial` lista zaleglosci ZANONIMIZOWANA (Jan K.) — defense-in-depth
 *     na wypadek "wycieku" PDF / przeslania na zly email.
 */
class ClubDashboardPdf
{
    /**
     * Generuje HTML dashboardu, ktory mozna zamienic na PDF przez PdfHelper.
     *
     * @param int    $clubId
     * @param string $template  jeden z: full_dashboard, financial, attendance, club_summary
     * @param array  $config    opt-in sekcje (np. ['include_overdue' => true])
     * @param string|null $rangeStart YYYY-MM-DD (domyslnie -30 dni)
     * @param string|null $rangeEnd   YYYY-MM-DD (domyslnie dzis)
     */
    public static function generateHtml(
        int $clubId,
        string $template = 'full_dashboard',
        array $config = [],
        ?string $rangeStart = null,
        ?string $rangeEnd = null
    ): string {
        $rangeEnd   ??= date('Y-m-d');
        $rangeStart ??= date('Y-m-d', strtotime('-30 days', strtotime($rangeEnd)));

        $kpi    = self::collectKpi($clubId, $rangeStart, $rangeEnd);
        $club   = self::loadClub($clubId);
        $header = PdfHelper::getClubHeader($clubId);
        $footer = PdfHelper::getSystemFooter();

        $title  = match ($template) {
            'financial'      => 'Raport finansowy',
            'attendance'     => 'Raport frekwencji',
            'club_summary'   => 'Podsumowanie klubu',
            default          => 'Dashboard klubu',
        };

        $body = '';
        switch ($template) {
            case 'financial':
                $body .= self::sectionFinancial($clubId, $rangeStart, $rangeEnd, $kpi);
                break;
            case 'attendance':
                $body .= self::sectionAttendance($clubId, $rangeStart, $rangeEnd, $kpi);
                break;
            case 'club_summary':
                $body .= self::sectionKpiCards($kpi, true);
                $body .= self::sectionUpcomingEvents($clubId, 5);
                break;
            case 'full_dashboard':
            default:
                $body .= self::sectionKpiCards($kpi, false);
                $body .= self::sectionAttendanceChart($clubId);
                $body .= self::sectionUpcomingEvents($clubId, 5);
                $body .= self::sectionTopTrainers($clubId, $rangeStart, $rangeEnd);
                break;
        }

        $rangeLabel = htmlspecialchars(date('d.m.Y', strtotime($rangeStart)) . ' — ' . date('d.m.Y', strtotime($rangeEnd)), ENT_QUOTES);
        $titleEsc   = htmlspecialchars($title, ENT_QUOTES);
        $clubName   = htmlspecialchars($club['name'] ?? '', ENT_QUOTES);
        $genStamp   = date('d.m.Y H:i');

        return <<<HTML
<!DOCTYPE html>
<html lang="pl"><head><meta charset="UTF-8"><style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #222; }
  h1 { font-size: 22px; margin: 8px 0 4px 0; color: #0d6efd; }
  h2 { font-size: 14px; margin: 18px 0 6px 0; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 3px; }
  .range { color: #666; font-size: 11px; margin-bottom: 12px; }
  table.kpi { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 10px 0 18px 0; }
  table.kpi td { background: #f8f9fa; border: 1px solid #e1e1e1; padding: 12px; text-align: center; vertical-align: top; }
  .kpi-num { font-size: 22px; font-weight: bold; color: #0d6efd; display: block; }
  .kpi-lbl { font-size: 10px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; }
  table.data { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 10px; }
  table.data th, table.data td { border: 1px solid #ccc; padding: 5px 7px; }
  table.data th { background: #f0f0f0; text-align: left; }
  .bar { background: #0d6efd; height: 14px; display: inline-block; vertical-align: middle; }
  .small { font-size: 9px; color: #888; }
  .warn { color: #b00020; }
  .ok { color: #1b7f3a; }
  .footer-stamp { margin-top: 24px; font-size: 9px; color: #888; text-align: center; }
</style></head><body>
{$header}
<h1>{$titleEsc}</h1>
<div class="range"><strong>{$clubName}</strong> · zakres: {$rangeLabel}</div>
{$body}
<div class="footer-stamp">Wygenerowano przez ClubDesk · {$genStamp}</div>
{$footer}
</body></html>
HTML;
    }

    /**
     * Generuje PDF binarny (string). Wymaga mpdf. Zwraca null gdy mpdf brak.
     */
    public static function generate(
        int $clubId,
        string $template = 'full_dashboard',
        array $config = [],
        ?string $rangeStart = null,
        ?string $rangeEnd = null
    ): ?string {
        $html = self::generateHtml($clubId, $template, $config, $rangeStart, $rangeEnd);
        return PdfHelper::renderToString($html, 'club-dashboard-' . $clubId, 'P');
    }

    /**
     * Zbiera kluczowe wskazniki — uzywane zarowno do PDF jak i do emaila
     * (placeholdery {{kpi_members}} itd.).
     *
     * @return array{members:int, attendance_pct:float, revenue:float, overdue:float, overdue_count:int}
     */
    public static function collectKpi(int $clubId, string $rangeStart, string $rangeEnd): array
    {
        $pdo = Database::pdo();

        // Aktywni czlonkowie
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE club_id=? AND status='aktywny'");
        $stmt->execute([$clubId]);
        $members = (int)$stmt->fetchColumn();

        // Srednia frekwencja w zakresie — % obecnych ze wszystkich zapisow
        $stmt = $pdo->prepare(
            "SELECT
                SUM(CASE WHEN ta.status='obecny' THEN 1 ELSE 0 END) AS present,
                COUNT(*) AS total
             FROM training_attendees ta
             JOIN trainings t ON t.id = ta.training_id
             WHERE t.club_id = :club_id
               AND t.start_time BETWEEN :start AND :end_plus"
        );
        $stmt->execute([
            ':club_id'  => $clubId,
            ':start'    => $rangeStart . ' 00:00:00',
            ':end_plus' => $rangeEnd . ' 23:59:59',
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['present' => 0, 'total' => 0];
        $attendancePct = ((int)$row['total'] > 0)
            ? round(((int)$row['present'] / (int)$row['total']) * 100, 1)
            : 0.0;

        // Wplywy w zakresie
        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM payments
             WHERE club_id = ? AND payment_date BETWEEN ? AND ?"
        );
        $stmt->execute([$clubId, $rangeStart, $rangeEnd]);
        $revenue = (float)$stmt->fetchColumn();

        // Oczekujace / zalegle platnosci (laczna suma)
        $overdue = 0.0; $overdueCount = 0;
        try {
            $stmt = $pdo->prepare(
                "SELECT COALESCE(SUM(net_amount - paid_amount), 0) AS s, COUNT(*) AS c
                 FROM payment_dues
                 WHERE club_id = ? AND status IN ('pending','partial','overdue')"
            );
            $stmt->execute([$clubId]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $overdue = (float)($r['s'] ?? 0);
            $overdueCount = (int)($r['c'] ?? 0);
        } catch (\Throwable) {
            // Tabela moze nie istniec w starszych instalacjach — ignoruj.
        }

        return [
            'members'        => $members,
            'attendance_pct' => (float)$attendancePct,
            'revenue'        => $revenue,
            'overdue'        => $overdue,
            'overdue_count'  => $overdueCount,
        ];
    }

    // ── Sekcje renderujace ────────────────────────────────────────────

    private static function sectionKpiCards(array $kpi, bool $mini): string
    {
        $m = (int)$kpi['members'];
        $a = number_format((float)$kpi['attendance_pct'], 1, ',', ' ');
        $r = number_format((float)$kpi['revenue'], 2, ',', ' ');
        $o = number_format((float)$kpi['overdue'], 2, ',', ' ');

        if ($mini) {
            return <<<HTML
<table class="kpi"><tr>
  <td><span class="kpi-num">{$m}</span><span class="kpi-lbl">Aktywni czlonkowie</span></td>
  <td><span class="kpi-num">{$a}%</span><span class="kpi-lbl">Srednia frekwencja</span></td>
  <td><span class="kpi-num">{$r}</span><span class="kpi-lbl">Wplywy (PLN)</span></td>
</tr></table>
HTML;
        }
        return <<<HTML
<table class="kpi"><tr>
  <td><span class="kpi-num">{$m}</span><span class="kpi-lbl">Aktywni czlonkowie</span></td>
  <td><span class="kpi-num">{$a}%</span><span class="kpi-lbl">Srednia frekwencja</span></td>
  <td><span class="kpi-num">{$r}</span><span class="kpi-lbl">Wplywy (PLN)</span></td>
  <td><span class="kpi-num">{$o}</span><span class="kpi-lbl">Oczekujace platnosci (PLN)</span></td>
</tr></table>
HTML;
    }

    /** Bar chart frekwencji w 4 ostatnich tygodniach (HTML-only, dziala w mpdf). */
    private static function sectionAttendanceChart(int $clubId): string
    {
        $pdo = Database::pdo();
        $weeks = [];
        for ($i = 3; $i >= 0; $i--) {
            $start = date('Y-m-d', strtotime("monday -{$i} week"));
            $end   = date('Y-m-d', strtotime($start . ' +6 days'));
            $stmt = $pdo->prepare(
                "SELECT
                    SUM(CASE WHEN ta.status='obecny' THEN 1 ELSE 0 END) AS present,
                    COUNT(*) AS total
                 FROM training_attendees ta
                 JOIN trainings t ON t.id = ta.training_id
                 WHERE t.club_id = :club_id
                   AND t.start_time BETWEEN :s AND :e"
            );
            $stmt->execute([':club_id' => $clubId, ':s' => $start . ' 00:00:00', ':e' => $end . ' 23:59:59']);
            $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['present' => 0, 'total' => 0];
            $pct = ((int)$r['total'] > 0) ? (int)round((int)$r['present'] / (int)$r['total'] * 100) : 0;
            $weeks[] = ['label' => date('d.m', strtotime($start)), 'pct' => $pct];
        }

        $rows = '';
        foreach ($weeks as $w) {
            $width = max(1, (int)$w['pct']) * 3;
            $lbl   = htmlspecialchars($w['label'], ENT_QUOTES);
            $rows .= '<tr><td style="width:60px;">' . $lbl . '</td>'
                . '<td><span class="bar" style="width:' . $width . 'px;"></span>'
                . ' <span class="small">' . $w['pct'] . '%</span></td></tr>';
        }
        return '<h2>Frekwencja — ostatnie 4 tygodnie</h2><table class="data">' . $rows . '</table>';
    }

    private static function sectionUpcomingEvents(int $clubId, int $limit = 5): string
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "SELECT name, event_date, location, type
             FROM events
             WHERE club_id = ? AND event_date >= NOW() AND status NOT IN ('odwolane','zakonczone')
             ORDER BY event_date ASC LIMIT " . max(1, $limit)
        );
        $stmt->execute([$clubId]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!$events) {
            return '<h2>Nadchodzace wydarzenia</h2><p class="small">Brak zaplanowanych wydarzen.</p>';
        }
        $rows = '';
        foreach ($events as $e) {
            $name = htmlspecialchars((string)$e['name'], ENT_QUOTES);
            $date = htmlspecialchars(date('d.m.Y H:i', strtotime((string)$e['event_date'])), ENT_QUOTES);
            $loc  = htmlspecialchars((string)($e['location'] ?? '—'), ENT_QUOTES);
            $type = htmlspecialchars((string)($e['type'] ?? ''), ENT_QUOTES);
            $rows .= "<tr><td>{$date}</td><td>{$name}</td><td>{$type}</td><td>{$loc}</td></tr>";
        }
        return '<h2>Nadchodzace wydarzenia</h2>'
             . '<table class="data"><thead><tr><th>Termin</th><th>Nazwa</th><th>Typ</th><th>Lokalizacja</th></tr></thead>'
             . '<tbody>' . $rows . '</tbody></table>';
    }

    private static function sectionTopTrainers(int $clubId, string $rangeStart, string $rangeEnd): string
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "SELECT u.username AS name, COUNT(t.id) AS sessions,
                    SUM(CASE WHEN ta.status='obecny' THEN 1 ELSE 0 END) AS present,
                    COUNT(ta.id) AS total_att
             FROM trainings t
             LEFT JOIN training_attendees ta ON ta.training_id = t.id
             LEFT JOIN users u ON u.id = t.instructor_id
             WHERE t.club_id = :club_id
               AND t.start_time BETWEEN :s AND :e
               AND t.instructor_id IS NOT NULL
             GROUP BY t.instructor_id
             ORDER BY present DESC, sessions DESC
             LIMIT 3"
        );
        $stmt->execute([':club_id' => $clubId, ':s' => $rangeStart . ' 00:00:00', ':e' => $rangeEnd . ' 23:59:59']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!$rows) {
            return '<h2>Top 3 trenerow</h2><p class="small">Brak danych o trenerach w tym okresie.</p>';
        }
        $tr = '';
        foreach ($rows as $r) {
            $name = htmlspecialchars((string)($r['name'] ?? '—'), ENT_QUOTES);
            $s    = (int)$r['sessions'];
            $p    = (int)$r['present'];
            $t    = (int)$r['total_att'];
            $pct  = $t > 0 ? round($p / $t * 100, 1) : 0.0;
            $tr  .= "<tr><td>{$name}</td><td>{$s}</td><td>{$p}/{$t}</td><td>{$pct}%</td></tr>";
        }
        return '<h2>Top 3 trenerow (wg frekwencji)</h2>'
             . '<table class="data"><thead><tr><th>Trener</th><th>Treningi</th><th>Obecnych/Zapisow</th><th>Frekwencja</th></tr></thead>'
             . '<tbody>' . $tr . '</tbody></table>';
    }

    private static function sectionFinancial(int $clubId, string $rangeStart, string $rangeEnd, array $kpi): string
    {
        $pdo = Database::pdo();

        $rev = number_format((float)$kpi['revenue'], 2, ',', ' ');
        $owe = number_format((float)$kpi['overdue'], 2, ',', ' ');
        $oweCnt = (int)$kpi['overdue_count'];

        // Split metod platnosci
        $stmt = $pdo->prepare(
            "SELECT method, COALESCE(SUM(amount), 0) AS total, COUNT(*) AS cnt
             FROM payments
             WHERE club_id = ? AND payment_date BETWEEN ? AND ?
             GROUP BY method ORDER BY total DESC"
        );
        $stmt->execute([$clubId, $rangeStart, $rangeEnd]);
        $methods = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $methodRows = '';
        foreach ($methods as $m) {
            $methodRows .= '<tr><td>' . htmlspecialchars((string)$m['method'], ENT_QUOTES) . '</td>'
                . '<td>' . (int)$m['cnt'] . '</td>'
                . '<td>' . number_format((float)$m['total'], 2, ',', ' ') . ' PLN</td></tr>';
        }
        if ($methodRows === '') {
            $methodRows = '<tr><td colspan="3" class="small">Brak platnosci w tym okresie.</td></tr>';
        }

        // Zaleglosci — anonimizowane (Jan K.)
        $overdueRows = '';
        try {
            $stmt = $pdo->prepare(
                "SELECT m.first_name, m.last_name, pd.net_amount - pd.paid_amount AS owed, pd.due_date
                 FROM payment_dues pd
                 JOIN members m ON m.id = pd.member_id
                 WHERE pd.club_id = ? AND pd.status IN ('pending','partial','overdue')
                 ORDER BY pd.due_date ASC
                 LIMIT 20"
            );
            $stmt->execute([$clubId]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $first = (string)($r['first_name'] ?? '');
                $last  = (string)($r['last_name'] ?? '');
                $anon  = htmlspecialchars($first . ' ' . (mb_substr($last, 0, 1) !== '' ? mb_substr($last, 0, 1) . '.' : ''), ENT_QUOTES);
                $amt   = number_format((float)$r['owed'], 2, ',', ' ');
                $due   = htmlspecialchars((string)$r['due_date'], ENT_QUOTES);
                $overdueRows .= "<tr><td>{$anon}</td><td>{$amt} PLN</td><td>{$due}</td></tr>";
            }
        } catch (\Throwable) {}
        if ($overdueRows === '') {
            $overdueRows = '<tr><td colspan="3" class="small ok">Brak zaleglosci — gratulacje!</td></tr>';
        }

        return <<<HTML
<table class="kpi"><tr>
  <td><span class="kpi-num">{$rev}</span><span class="kpi-lbl">Wplywy (PLN)</span></td>
  <td><span class="kpi-num">{$owe}</span><span class="kpi-lbl">Zaleglosci (PLN)</span></td>
  <td><span class="kpi-num">{$oweCnt}</span><span class="kpi-lbl">Aktywne zaleglosci</span></td>
</tr></table>

<h2>Wplywy wedlug metody platnosci</h2>
<table class="data">
  <thead><tr><th>Metoda</th><th>Ilosc</th><th>Suma</th></tr></thead>
  <tbody>{$methodRows}</tbody>
</table>

<h2>Zaleglosci (TOP 20 — zanonimizowane)</h2>
<table class="data">
  <thead><tr><th>Czlonek</th><th>Do zaplaty</th><th>Termin</th></tr></thead>
  <tbody>{$overdueRows}</tbody>
</table>
<p class="small">Lista zanonimizowana (pelne dane w panelu admina).</p>
HTML;
    }

    private static function sectionAttendance(int $clubId, string $rangeStart, string $rangeEnd, array $kpi): string
    {
        $pdo = Database::pdo();
        $attPct = number_format((float)$kpi['attendance_pct'], 1, ',', ' ');

        // Frekwencja per sport (sekcja)
        $stmt = $pdo->prepare(
            "SELECT s.name AS sport_name,
                    SUM(CASE WHEN ta.status='obecny' THEN 1 ELSE 0 END) AS present,
                    COUNT(ta.id) AS total
             FROM trainings t
             LEFT JOIN training_attendees ta ON ta.training_id = t.id
             LEFT JOIN sports s ON s.id = t.sport_id
             WHERE t.club_id = :club_id
               AND t.start_time BETWEEN :s AND :e
             GROUP BY t.sport_id
             ORDER BY total DESC"
        );
        $stmt->execute([':club_id' => $clubId, ':s' => $rangeStart . ' 00:00:00', ':e' => $rangeEnd . ' 23:59:59']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $sectionRows = '';
        foreach ($rows as $r) {
            $name = htmlspecialchars((string)($r['sport_name'] ?? 'Ogolne'), ENT_QUOTES);
            $p    = (int)$r['present'];
            $t    = (int)$r['total'];
            $pct  = $t > 0 ? round($p / $t * 100, 1) : 0.0;
            $sectionRows .= "<tr><td>{$name}</td><td>{$p}/{$t}</td><td>{$pct}%</td></tr>";
        }
        if ($sectionRows === '') {
            $sectionRows = '<tr><td colspan="3" class="small">Brak trenowanych sesji w tym okresie.</td></tr>';
        }

        // Members z najnizsza frekwencja (warning)
        $stmt = $pdo->prepare(
            "SELECT m.first_name, m.last_name,
                    SUM(CASE WHEN ta.status='obecny' THEN 1 ELSE 0 END) AS present,
                    COUNT(ta.id) AS total
             FROM members m
             JOIN training_attendees ta ON ta.member_id = m.id
             JOIN trainings t ON t.id = ta.training_id
             WHERE m.club_id = :club_id AND t.club_id = :club_id
               AND t.start_time BETWEEN :s AND :e
             GROUP BY m.id
             HAVING total >= 3
             ORDER BY (present * 1.0 / total) ASC
             LIMIT 10"
        );
        $stmt->execute([':club_id' => $clubId, ':s' => $rangeStart . ' 00:00:00', ':e' => $rangeEnd . ' 23:59:59']);
        $lowRows = '';
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $first = (string)($r['first_name'] ?? '');
            $last  = (string)($r['last_name'] ?? '');
            $anon  = htmlspecialchars($first . ' ' . (mb_substr($last, 0, 1) !== '' ? mb_substr($last, 0, 1) . '.' : ''), ENT_QUOTES);
            $p     = (int)$r['present'];
            $t     = (int)$r['total'];
            $pct   = $t > 0 ? round($p / $t * 100, 1) : 0.0;
            $cls   = $pct < 50 ? 'warn' : '';
            $lowRows .= "<tr class=\"{$cls}\"><td>{$anon}</td><td>{$p}/{$t}</td><td>{$pct}%</td></tr>";
        }
        if ($lowRows === '') {
            $lowRows = '<tr><td colspan="3" class="small">Brak czlonkow z wystarczajaca historia frekwencji.</td></tr>';
        }

        return <<<HTML
<table class="kpi"><tr>
  <td><span class="kpi-num">{$attPct}%</span><span class="kpi-lbl">Srednia frekwencja</span></td>
</tr></table>

<h2>Frekwencja per sekcja</h2>
<table class="data">
  <thead><tr><th>Sekcja</th><th>Obecnych/Zapisow</th><th>%</th></tr></thead>
  <tbody>{$sectionRows}</tbody>
</table>

<h2>Czlonkowie z najnizsza frekwencja (alert)</h2>
<table class="data">
  <thead><tr><th>Czlonek</th><th>Obecnych/Zapisow</th><th>%</th></tr></thead>
  <tbody>{$lowRows}</tbody>
</table>
<p class="small">Czerwone wiersze: ponizej 50% frekwencji. Lista zanonimizowana.</p>
HTML;
    }

    private static function loadClub(int $clubId): array
    {
        try {
            $stmt = Database::pdo()->prepare("SELECT id, name FROM clubs WHERE id = ? LIMIT 1");
            $stmt->execute([$clubId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['id' => $clubId, 'name' => ''];
        } catch (\Throwable) {
            return ['id' => $clubId, 'name' => ''];
        }
    }
}
