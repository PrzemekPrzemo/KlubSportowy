<?php

declare(strict_types=1);

namespace App\Helpers\Reports;

use App\Helpers\Database;
use App\Helpers\EmailService;
use PDO;

/**
 * Worker do wysylki zaplanowanych raportow PDF.
 *
 * Flow `processOne`:
 *   1) Pobierz raport (z lockiem WHERE next_send_at <= NOW)
 *   2) Generuj PDF (ClubDashboardPdf::generate)
 *   3) Zapisz pod storage/reports/{club_id}/{report_id}_{timestamp}.pdf
 *   4) INSERT scheduled_report_runs status=generated
 *   5) Wyslij email (subject/body z placeholderami {{club_name}} itd.) z PDF jako attachment
 *   6) UPDATE run -> status=sent, sent_at
 *   7) UPDATE definicje -> last_sent_at = NOW, next_send_at = calculateNext
 *
 * Bledy -> status=failed + error_message; next_send_at i tak przesuniete
 * (zeby uniknac petli na uszkodzonym raporcie).
 */
class ScheduledReportRunner
{
    /** Maks. wielkosc attachmentu emailowego (5 MB). */
    public const MAX_PDF_BYTES = 5 * 1024 * 1024;

    /**
     * Przetwarza wszystkie wymagalne raporty (next_send_at <= NOW + active=1).
     * Zwraca liczbe przetworzonych (sent + failed).
     */
    public function processDue(int $limit = 50): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "SELECT id FROM scheduled_reports
             WHERE active = 1 AND next_send_at IS NOT NULL AND next_send_at <= NOW()
             ORDER BY next_send_at ASC LIMIT " . max(1, $limit)
        );
        $stmt->execute();
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $count = 0;
        foreach ($ids as $id) {
            try {
                $this->processOne((int)$id);
                $count++;
            } catch (\Throwable $e) {
                error_log('ScheduledReportRunner error report=' . $id . ': ' . $e->getMessage());
            }
        }
        return $count;
    }

    /**
     * Generuje + wysyla pojedynczy raport. Zawsze przesuwa next_send_at
     * (nawet przy bledzie) zeby nie zaspamowac retries.
     */
    public function processOne(int $reportId): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT * FROM scheduled_reports WHERE id = ? LIMIT 1");
        $stmt->execute([$reportId]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$report) {
            throw new \RuntimeException("scheduled_reports id={$reportId} not found");
        }

        $clubId   = (int)$report['club_id'];
        $template = (string)$report['template'];
        $config   = is_string($report['config_json']) ? (json_decode($report['config_json'], true) ?: []) : [];
        $recipients = self::decodeRecipients((string)$report['recipient_emails']);

        $rangeStart = date('Y-m-d', strtotime('-30 days'));
        $rangeEnd   = date('Y-m-d');

        $runId = $this->insertRun($reportId, 'generated');

        try {
            $pdfBinary = ClubDashboardPdf::generate($clubId, $template, $config, $rangeStart, $rangeEnd);
            if ($pdfBinary === null || $pdfBinary === '') {
                throw new \RuntimeException('PDF binary is empty (mpdf missing?)');
            }
            $size = strlen($pdfBinary);
            if ($size > self::MAX_PDF_BYTES) {
                throw new \RuntimeException("PDF size {$size}B exceeds max " . self::MAX_PDF_BYTES . 'B');
            }

            $pdfPath = $this->storePdf($clubId, $reportId, $pdfBinary);

            // Update run z pdf metadanymi
            $pdo->prepare("UPDATE scheduled_report_runs SET pdf_path=?, pdf_size_bytes=? WHERE id=?")
                ->execute([$pdfPath, $size, $runId]);

            $kpi = ClubDashboardPdf::collectKpi($clubId, $rangeStart, $rangeEnd);
            $club = $this->loadClub($clubId);
            $vars = [
                'club_name'      => (string)($club['name'] ?? ''),
                'date_range'     => date('d.m.Y', strtotime($rangeStart)) . ' — ' . date('d.m.Y', strtotime($rangeEnd)),
                'kpi_members'    => (string)$kpi['members'],
                'kpi_attendance' => (string)$kpi['attendance_pct'],
                'kpi_revenue'    => number_format((float)$kpi['revenue'], 2, ',', ' '),
                'kpi_overdue'    => number_format((float)$kpi['overdue'], 2, ',', ' '),
            ];

            [$subjectTpl, $bodyTpl] = $this->loadEmailTemplate();
            $subject = self::applyPlaceholders($subjectTpl, $vars);
            $body    = self::applyPlaceholders($bodyTpl, $vars);

            $sentCount = 0;
            foreach ($recipients as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $ok = EmailService::sendWithAttachment(
                    $clubId,
                    $email,
                    $subject,
                    $body,
                    [
                        'content'  => $pdfBinary,
                        'filename' => 'raport_' . $clubId . '_' . date('Ymd') . '.pdf',
                        'mime'     => 'application/pdf',
                    ]
                );
                if ($ok) {
                    $sentCount++;
                }
            }

            $pdo->prepare(
                "UPDATE scheduled_report_runs
                 SET status='sent', recipients_count=?, sent_at=NOW()
                 WHERE id=?"
            )->execute([$sentCount, $runId]);

            $pdo->prepare(
                "UPDATE scheduled_reports
                 SET last_sent_at=NOW(), next_send_at=?
                 WHERE id=?"
            )->execute([self::calculateNext((string)$report['cron_schedule']), $reportId]);
        } catch (\Throwable $e) {
            $pdo->prepare(
                "UPDATE scheduled_report_runs SET status='failed', error_message=? WHERE id=?"
            )->execute([substr($e->getMessage(), 0, 65000), $runId]);

            // Przesun next_send_at zeby uniknac petli (rerun w nastepnym cyklu).
            $pdo->prepare(
                "UPDATE scheduled_reports SET next_send_at=? WHERE id=?"
            )->execute([self::calculateNext((string)$report['cron_schedule']), $reportId]);

            throw $e;
        }
    }

    /**
     * Liczy nastepny next_send_at w oparciu o schedule:
     *  - weekly_mon  → nastepny poniedzialek 08:00
     *  - weekly_fri  → nastepny piatek 08:00
     *  - monthly_1st → 1. dzien nastepnego miesiaca 08:00
     *  - quarterly   → 1. dzien nastepnego kwartalu (Jan/Apr/Jul/Oct) 08:00
     *
     * Uzywa $now (DateTimeImmutable) — testowalne.
     */
    public static function calculateNext(string $schedule, ?\DateTimeImmutable $now = null): string
    {
        $now ??= new \DateTimeImmutable('now');
        switch ($schedule) {
            case 'weekly_mon':
                $next = $now->modify('next monday')->setTime(8, 0);
                break;
            case 'weekly_fri':
                $next = $now->modify('next friday')->setTime(8, 0);
                break;
            case 'monthly_1st':
                $next = $now->modify('first day of next month')->setTime(8, 0);
                break;
            case 'quarterly':
                $m = (int)$now->format('n');
                $y = (int)$now->format('Y');
                $nextQ = match (true) {
                    $m <= 3  => "{$y}-04-01 08:00:00",
                    $m <= 6  => "{$y}-07-01 08:00:00",
                    $m <= 9  => "{$y}-10-01 08:00:00",
                    default  => ($y + 1) . "-01-01 08:00:00",
                };
                $next = new \DateTimeImmutable($nextQ);
                break;
            default:
                $next = $now->modify('+1 week')->setTime(8, 0);
        }
        return $next->format('Y-m-d H:i:s');
    }

    /** @return list<string> */
    public static function decodeRecipients(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') return [];
        $arr = json_decode($raw, true);
        if (is_array($arr)) {
            return array_values(array_filter(array_map('trim', array_map('strval', $arr)), 'strlen'));
        }
        // fallback: rozdzielone przecinkami/srednikami
        $parts = preg_split('/[,;]+/', $raw) ?: [];
        return array_values(array_filter(array_map('trim', $parts), 'strlen'));
    }

    /** Encoduje listy do JSON do zapisu. */
    public static function encodeRecipients(array $emails): string
    {
        $clean = [];
        foreach ($emails as $e) {
            $e = trim((string)$e);
            if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                $clean[] = $e;
            }
        }
        return json_encode(array_values(array_unique($clean)), JSON_UNESCAPED_UNICODE) ?: '[]';
    }

    /** Zastapuje placeholdery {{name}} wartosciami. */
    public static function applyPlaceholders(string $tpl, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $tpl = str_replace('{{' . $k . '}}', (string)$v, $tpl);
        }
        return $tpl;
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function insertRun(int $reportId, string $status): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            "INSERT INTO scheduled_report_runs (report_id, status) VALUES (?, ?)"
        );
        $stmt->execute([$reportId, $status]);
        return (int)$pdo->lastInsertId();
    }

    private function storePdf(int $clubId, int $reportId, string $pdfBinary): string
    {
        $relDir = 'storage/reports/' . $clubId;
        $absDir = ROOT_PATH . '/' . $relDir;
        if (!is_dir($absDir)) {
            @mkdir($absDir, 0775, true);
        }
        if (!is_dir($absDir) || !is_writable($absDir)) {
            throw new \RuntimeException("PDF storage dir unwritable: {$absDir}");
        }
        $filename = $reportId . '_' . date('Ymd_His') . '.pdf';
        $abs = $absDir . '/' . $filename;
        if (file_put_contents($abs, $pdfBinary) === false) {
            throw new \RuntimeException("Cannot write PDF to {$abs}");
        }
        return $relDir . '/' . $filename;
    }

    private function loadClub(int $clubId): array
    {
        $stmt = Database::pdo()->prepare("SELECT id, name FROM clubs WHERE id = ?");
        $stmt->execute([$clubId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['id' => $clubId, 'name' => ''];
    }

    /** @return array{0:string,1:string} [subject, body] */
    private function loadEmailTemplate(): array
    {
        $defaultSubject = '[{{club_name}}] Raport {{date_range}}';
        $defaultBody    = "Czesc,\n\nW zalaczniku Twoj raport klubu {{club_name}} za okres {{date_range}}.\n\n"
            . "Kluczowe wskazniki:\n"
            . "- Aktywni czlonkowie: {{kpi_members}}\n"
            . "- Srednia frekwencja: {{kpi_attendance}}%\n"
            . "- Wplywy: {{kpi_revenue}} PLN\n\n"
            . "Pelne dane w zalaczonym PDF.\n\nPozdrawiamy,\nClubDesk";
        try {
            $stmt = Database::pdo()->prepare(
                "SELECT default_subject, default_body FROM email_event_catalog WHERE code = ? LIMIT 1"
            );
            $stmt->execute(['scheduled_report_ready']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return [
                    (string)($row['default_subject'] ?: $defaultSubject),
                    (string)($row['default_body']    ?: $defaultBody),
                ];
            }
        } catch (\Throwable) {}
        return [$defaultSubject, $defaultBody];
    }
}
