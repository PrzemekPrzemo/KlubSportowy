<?php

namespace App\Helpers;

use App\Helpers\ClubBranding;
use App\Models\ClubSettingsModel;
use App\Models\EmailQueueModel;
use App\Models\EmailTemplateModel;
use App\Models\SettingModel;

/**
 * Wysyłka e-mail — obsługuje SMTP per-klub (z fallbackiem do globalnego)
 * i natywną funkcję mail() jako ostateczny fallback.
 *
 * Produkcyjnie: rekomendujemy PHPMailer (composer require phpmailer/phpmailer).
 * Tutaj używamy mail() aby nie wprowadzać zależności zewnętrznych w rdzeniu.
 */
class EmailService
{
    /**
     * Wysyła wiadomość NATYCHMIAST (nie używa kolejki).
     *
     * Y.3 — body z plain-text auto-opakowany w branded HTML (logo systemu +
     * klubu, primary color, footer). Multipart/alternative gdy SMTP aktywny.
     */
    public static function send(int $clubId, string $toEmail, string $subject, string $body, ?string $toName = null): bool
    {
        $config = self::resolveSmtpConfig($clubId);

        // Whitelabel: per-klub email_from_name nadpisuje SMTP from_name.
        $branding = ClubBranding::forClub($clubId);
        $brandFromName = $branding->emailFromNameOrDefault('');

        $from = $config['from_email'] ?: ('noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $fromName = $brandFromName !== '' ? $brandFromName : ($config['from_name'] ?: 'KlubSportowy');
        $config['from_name'] = $fromName; // przekaz do SMTP wrappera

        // Y.3 — Build HTML wrapper if body is plain (default) lub jest już HTML
        $isAlreadyHtml = str_starts_with(trim($body), '<') || str_contains($body, '<html');
        $htmlBody = $isAlreadyHtml ? $body : self::brandedHtml($clubId, $body);

        // Sponsor footer placeholder ({{sponsors_footer}}) — best-effort.
        // Działa zarówno w plain body (po wrapie do HTML) jak i już-HTML body.
        // Nieobecny placeholder => zero kosztu i zero side-effects.
        $htmlBody = self::injectSponsorsFooter($clubId, $htmlBody);

        // SMTP — multipart/alternative (HTML + plain text)
        if ($config['enabled'] && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return self::sendViaSmtp($config, $toEmail, $toName, $subject, $htmlBody, $body);
        }

        // Fallback: natywna mail() z text/html
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . self::encodeHeader($fromName) . " <{$from}>\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= "X-Mailer: ClubDesk\r\n";
        $subjectEncoded = self::encodeHeader($subject);

        return @mail($toEmail, $subjectEncoded, $htmlBody, $headers);
    }

    /**
     * Wysylka emaila z zalacznikiem (np. scheduled report PDF).
     *
     * @param array{content:string,filename:string,mime?:string} $attachment
     */
    public static function sendWithAttachment(int $clubId, string $toEmail, string $subject, string $body, array $attachment, ?string $toName = null): bool
    {
        if (!isset($attachment['content'], $attachment['filename'])) {
            return false;
        }
        $mime = $attachment['mime'] ?? 'application/octet-stream';

        $config = self::resolveSmtpConfig($clubId);
        $branding = ClubBranding::forClub($clubId);
        $brandFromName = $branding->emailFromNameOrDefault('');
        $from = $config['from_email'] ?: ('noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $fromName = $brandFromName !== '' ? $brandFromName : ($config['from_name'] ?: 'KlubSportowy');
        $config['from_name'] = $fromName;

        $isAlreadyHtml = str_starts_with(trim($body), '<') || str_contains($body, '<html');
        $htmlBody = $isAlreadyHtml ? $body : self::brandedHtml($clubId, $body);
        $htmlBody = self::injectSponsorsFooter($clubId, $htmlBody);

        // SMTP path (PHPMailer obsluguje attachments natywnie)
        if ($config['enabled'] && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            $mailerClass = 'PHPMailer\\PHPMailer\\PHPMailer';
            $mail = new $mailerClass(true);
            try {
                $mail->isSMTP();
                $mail->Host       = $config['host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['user'];
                $mail->Password   = $config['pass'];
                $mail->SMTPSecure = $config['secure'];
                $mail->Port       = $config['port'];
                $mail->CharSet    = 'UTF-8';
                $mail->setFrom($from, $fromName);
                $mail->addAddress($toEmail, $toName ?? '');
                $mail->Subject = $subject;
                $mail->isHTML(true);
                $mail->Body    = $htmlBody;
                $mail->AltBody = trim(strip_tags($body));
                $mail->addStringAttachment(
                    (string)$attachment['content'],
                    (string)$attachment['filename'],
                    'base64',
                    $mime
                );
                $mail->send();
                return true;
            } catch (\Throwable $e) {
                error_log('PHPMailer attachment error: ' . $e->getMessage());
                return false;
            }
        }

        // Fallback: rece-budowany multipart/mixed (mail())
        $boundary = '=_clubdesk_' . bin2hex(random_bytes(8));
        $subjectEncoded = self::encodeHeader($subject);

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        $headers .= "From: " . self::encodeHeader($fromName) . " <{$from}>\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= "X-Mailer: ClubDesk\r\n";

        $message  = "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $message .= $htmlBody . "\r\n";
        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: {$mime}; name=\"{$attachment['filename']}\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n";
        $message .= "Content-Disposition: attachment; filename=\"{$attachment['filename']}\"\r\n\r\n";
        $message .= chunk_split(base64_encode((string)$attachment['content'])) . "\r\n";
        $message .= "--{$boundary}--\r\n";

        return @mail($toEmail, $subjectEncoded, $message, $headers);
    }

    /**
     * Y.3 — Buduje branded HTML email shell z 3 warstwami logo
     * (system + klub) + primary color + nagłówek + footer.
     *
     * @param int    $clubId   ID klubu (do pobrania logo + nazwy + koloru)
     * @param string $bodyText Plain text body (zostanie przekonwertowane na HTML)
     */
    public static function brandedHtml(int $clubId, string $bodyText): string
    {
        // Pobierz dane klubu + branding
        $clubName     = '';
        $primaryColor = '#EE2C28';
        try {
            $club = (new \App\Models\ClubModel())->findById($clubId);
            $clubName = (string)($club['name'] ?? '');
            $cust = (new \App\Models\ClubCustomizationModel())->findForClub($clubId);
            $primaryColor = $cust['primary_color'] ?? '#EE2C28';
        } catch (\Throwable) {}

        // Logo URLs — używamy pełnych URL bo email klient nie ma kontekstu serwera
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'https://portal.clubdesk.pl';

        $sysLogoSrc = '';
        try {
            $sysLogoPath = (new \App\Models\SettingModel())->get('system_logo_color', '');
            if (is_string($sysLogoPath) && $sysLogoPath !== '') {
                $sysLogoSrc = $base . '/' . ltrim((string)$sysLogoPath, '/');
            } else {
                $sysLogoSrc = $base . '/images/logo-cd.svg';
            }
        } catch (\Throwable) {
            $sysLogoSrc = $base . '/images/logo-cd.svg';
        }

        $clubLogoSrc = '';
        try {
            $cust = $cust ?? (new \App\Models\ClubCustomizationModel())->findForClub($clubId);
            $clubLogoPath = $cust['logo_path'] ?? null;
            if ($clubLogoPath) {
                $clubLogoSrc = $base . '/' . ltrim((string)$clubLogoPath, '/');
            }
        } catch (\Throwable) {}

        // Konwertuj plain text na HTML — escape + nl2br + paragraph wrapping
        $bodyHtml = nl2br(htmlspecialchars($bodyText, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $clubNameEsc     = htmlspecialchars($clubName, ENT_QUOTES, 'UTF-8');
        $primaryColorEsc = htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8');
        $sysLogoSrcEsc   = htmlspecialchars($sysLogoSrc, ENT_QUOTES, 'UTF-8');
        $clubLogoBlock   = $clubLogoSrc
            ? '<img src="' . htmlspecialchars($clubLogoSrc, ENT_QUOTES, 'UTF-8')
                . '" alt="' . $clubNameEsc . '" style="max-height:50px; max-width:160px; vertical-align:middle;">'
            : '';

        // Whitelabel: per-klub email_header_html nadpisuje domyslny header.
        try {
            $branding = ClubBranding::forClub($clubId);
            $customHeader = $branding->__get('email_header_html');
        } catch (\Throwable) {
            $customHeader = null;
        }
        if (is_string($customHeader) && trim($customHeader) !== '') {
            // Wartosc juz sanitized w zapisie (WhitelabelSanitizer::sanitizeEmailHeaderHtml).
            $headerBlock = '<tr><td>' . $customHeader . '</td></tr>';
        } else {
            $headerBlock = <<<HTML_HEADER
<!-- Header z logo -->
<tr><td style="background:{$primaryColorEsc}; padding:20px 24px; color:#fff;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr>
<td style="vertical-align:middle;">
{$clubLogoBlock}
</td>
<td style="vertical-align:middle; text-align:right; color:#fff;">
<strong style="font-size:16px;">{$clubNameEsc}</strong>
</td>
</tr>
</table>
</td></tr>
HTML_HEADER;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$clubNameEsc}</title>
</head>
<body style="margin:0; padding:0; background:#f5f5f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; color:#333;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f7;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.08);">
{$headerBlock}
<!-- Body -->
<tr><td style="padding:32px 24px; line-height:1.6; font-size:15px; color:#222;">
{$bodyHtml}
</td></tr>
<!-- Footer z system logo -->
<tr><td style="border-top:1px solid #eee; padding:18px 24px; background:#fafafa; text-align:center; font-size:12px; color:#888;">
<img src="{$sysLogoSrcEsc}" alt="ClubDesk" style="max-height:24px; vertical-align:middle; margin-right:6px;">
<span style="vertical-align:middle;">Wiadomość wysłana przez system ClubDesk · <a href="{$base}" style="color:{$primaryColorEsc}; text-decoration:none;">clubdesk.pl</a></span>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    /** Dodaje wiadomość do kolejki — worker wysyła w tle. */
    public static function queue(int $clubId, string $toEmail, string $subject, string $body, ?string $toName = null, ?string $templateType = null): int
    {
        return (new EmailQueueModel())
            ->enqueue($clubId, $toEmail, $toName, $subject, $body, $templateType, Auth::id());
    }

    /**
     * Kolejkuje wiadomość z szablonu, renderując placeholdery.
     *
     * @param string|null $locale Wymus konkretny locale (pl/en). Jesli null:
     *                            cascade z $vars['recipient_member_id'] -> klub.default_locale -> 'pl'.
     *                            Brak EN translation -> fallback do PL (avoid send-fail).
     */
    public static function queueFromTemplate(
        int $clubId,
        string $templateType,
        string $toEmail,
        array $vars,
        ?string $toName = null,
        ?string $locale = null
    ): ?int {
        $tpl = (new EmailTemplateModel())->resolve($templateType, $clubId);
        if (!$tpl) return null;

        // Resolve effective locale.
        $effLocale = self::resolveLocaleForRecipient($locale, $vars, $clubId);

        // Try locale-specific translation z email_event_catalog_translations.
        $localized = self::fetchEventCatalogTranslation($templateType, $effLocale);
        if ($localized === null && $effLocale !== \App\Helpers\Translator::FALLBACK) {
            // Fallback do PL (locale='pl')
            $localized = self::fetchEventCatalogTranslation($templateType, \App\Helpers\Translator::FALLBACK);
        }

        if ($localized !== null) {
            $rendered = EmailTemplateModel::render(
                ['subject' => $localized['subject'], 'body' => $localized['body']],
                $vars
            );
        } else {
            // Brak translation -> uzyj klub override / default z resolve().
            $rendered = EmailTemplateModel::render($tpl, $vars);
        }
        return self::queue($clubId, $toEmail, $rendered['subject'], $rendered['body'], $toName, $templateType);
    }

    /**
     * Cascade resolve locale dla odbiorcy emaila:
     *   1) explicit $locale parametr (jesli supported)
     *   2) member.preferred_locale (jesli $vars['recipient_member_id'] set)
     *   3) club.default_locale
     *   4) hard fallback 'pl'
     */
    private static function resolveLocaleForRecipient(?string $locale, array $vars, int $clubId): string
    {
        $supported = \App\Helpers\Translator::SUPPORTED;

        if (is_string($locale) && in_array($locale, $supported, true)) {
            return $locale;
        }

        $memberId = $vars['recipient_member_id'] ?? null;
        if ($memberId !== null && (int)$memberId > 0) {
            try {
                $stmt = Database::pdo()->prepare('SELECT preferred_locale FROM members WHERE id = ? LIMIT 1');
                $stmt->execute([(int)$memberId]);
                $loc = $stmt->fetchColumn();
                if (is_string($loc) && in_array($loc, $supported, true)) {
                    return $loc;
                }
            } catch (\Throwable) { /* best-effort */ }
        }

        try {
            $stmt = Database::pdo()->prepare('SELECT default_locale FROM clubs WHERE id = ? LIMIT 1');
            $stmt->execute([$clubId]);
            $loc = $stmt->fetchColumn();
            if (is_string($loc) && in_array($loc, $supported, true)) {
                return $loc;
            }
        } catch (\Throwable) { /* best-effort */ }

        return \App\Helpers\Translator::FALLBACK;
    }

    /**
     * Pobiera lokalizowany subject+body z email_event_catalog_translations.
     * Klucz: catalog.code = $templateType + locale.
     */
    private static function fetchEventCatalogTranslation(string $templateType, string $locale): ?array
    {
        try {
            $stmt = Database::pdo()->prepare(
                'SELECT t.subject, t.body
                   FROM email_event_catalog c
                   JOIN email_event_catalog_translations t ON t.event_id = c.id
                  WHERE c.code = ? AND t.locale = ?
                  LIMIT 1'
            );
            $stmt->execute([$templateType, $locale]);
            $row = $stmt->fetch();
            if ($row && isset($row['subject'], $row['body'])) {
                return ['subject' => (string)$row['subject'], 'body' => (string)$row['body']];
            }
        } catch (\Throwable) { /* tabela moze nie istniec — best-effort */ }
        return null;
    }

    /**
     * Wysyła wiadomości z kolejki — wywoływane przez cli/email_worker.php.
     * Zwraca liczbę wysłanych.
     */
    public static function processQueue(int $batchSize = 20): int
    {
        $queue = new EmailQueueModel();
        $pending = $queue->pending($batchSize);
        $sent = 0;
        foreach ($pending as $row) {
            $queue->markSending((int)$row['id']);
            try {
                $ok = self::send(
                    (int)$row['club_id'],
                    $row['to_email'],
                    $row['subject'],
                    $row['body'],
                    $row['to_name']
                );
                if ($ok) {
                    $queue->markSent((int)$row['id']);
                    $sent++;
                } else {
                    $queue->markFailed((int)$row['id'], 'mail() returned false');
                }
            } catch (\Throwable $e) {
                $queue->markFailed((int)$row['id'], $e->getMessage());
            }
        }
        return $sent;
    }

    /**
     * Wstaw rendered HTML footer z 1-3 logo top sponsorów w miejsce
     * placeholdera `{{sponsors_footer}}` w body. Jeśli placeholder nie wystepuje
     * w body — funkcja zwraca body bez zmian (no-op, brak dodatkowych zapytan).
     *
     * Tabela sponsors moze nie istniec (np. migracja 083 nieprzejechana) —
     * defensive: catch any error i zwroc oryginalny body.
     */
    public static function injectSponsorsFooter(int $clubId, string $body): string
    {
        if (!str_contains($body, '{{sponsors_footer}}')) {
            return $body;
        }

        $html = '';
        try {
            $model = new \App\Models\SponsorModel();
            $list  = $model->activeForClub($clubId, 'email', 3);

            if (!empty($list)) {
                $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
                $items = [];
                foreach ($list as $sp) {
                    // Rejestruj ekspozycje email (best-effort, idempotent ze wzgledu na timing)
                    $model->recordExposure((int)$sp['id'], 'email_view', null);

                    $name = htmlspecialchars((string)$sp['name'], ENT_QUOTES, 'UTF-8');
                    if (!empty($sp['logo_path'])) {
                        $logoSrc = $base . '/' . ltrim((string)$sp['logo_path'], '/');
                        $logoSrcEsc = htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8');
                        $img = '<img src="' . $logoSrcEsc . '" alt="' . $name
                             . '" style="max-height:36px;max-width:100px;vertical-align:middle;margin:0 8px;">';
                    } else {
                        $img = '<span style="display:inline-block;padding:6px 10px;border:1px solid #ddd;border-radius:4px;margin:0 6px;font-size:12px;">'
                             . $name . '</span>';
                    }
                    if (!empty($sp['website'])) {
                        $url = htmlspecialchars((string)$sp['website'], ENT_QUOTES, 'UTF-8');
                        $img = '<a href="' . $url . '" target="_blank" rel="noopener sponsored" style="text-decoration:none;color:inherit;">' . $img . '</a>';
                    }
                    $items[] = $img;
                }

                $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;border-top:1px solid #eee;padding-top:12px;">'
                      . '<tr><td style="text-align:center;font-size:11px;color:#999;padding-bottom:6px;">Nasi sponsorzy</td></tr>'
                      . '<tr><td style="text-align:center;">' . implode('', $items) . '</td></tr>'
                      . '</table>';
            }
        } catch (\Throwable $e) {
            error_log('EmailService::injectSponsorsFooter failed: ' . $e->getMessage());
            $html = '';
        }

        return str_replace('{{sponsors_footer}}', $html, $body);
    }

    /** Rozwiązuje konfigurację SMTP: per-klub → globalna → null. */
    private static function resolveSmtpConfig(int $clubId): array
    {
        $cs = new ClubSettingsModel();
        $gs = new SettingModel();

        $enabled = (int)$cs->get($clubId, 'smtp_enabled', 0) === 1;

        return [
            'enabled'    => $enabled,
            'host'       => $cs->get($clubId, 'smtp_host', $gs->get('smtp_host', '')),
            'port'       => (int)$cs->get($clubId, 'smtp_port', $gs->get('smtp_port', 587)),
            'secure'     => $cs->get($clubId, 'smtp_secure', $gs->get('smtp_secure', 'tls')),
            'user'       => $cs->get($clubId, 'smtp_user', $gs->get('smtp_user', '')),
            'pass'       => $cs->get($clubId, 'smtp_pass_enc', $gs->get('smtp_pass_enc', '')),
            'from_email' => $cs->get($clubId, 'smtp_from_email', ''),
            'from_name'  => $cs->get($clubId, 'smtp_from_name', ''),
        ];
    }

    /**
     * Y.3 — Multipart/alternative: HTML body + plain-text fallback.
     * @param string|null $textBody  Wersja plain-text (gdy null, używa stripped HTML)
     */
    private static function sendViaSmtp(array $config, string $toEmail, ?string $toName, string $subject, string $body, ?string $textBody = null): bool
    {
        $mailerClass = 'PHPMailer\\PHPMailer\\PHPMailer';
        $mail = new $mailerClass(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['user'];
            $mail->Password   = $config['pass'];
            $mail->SMTPSecure = $config['secure'];
            $mail->Port       = $config['port'];
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($toEmail, $toName ?? '');
            $mail->Subject = $subject;

            // Y.3: Auto-detect HTML body i ustaw multipart/alternative
            $isHtml = str_contains($body, '<html') || str_contains($body, '<!DOCTYPE');
            if ($isHtml) {
                $mail->isHTML(true);
                $mail->Body    = $body;
                $mail->AltBody = $textBody ?? trim(strip_tags($body));
            } else {
                $mail->Body = $body;
            }
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            error_log('PHPMailer error: ' . $e->getMessage());
            return false;
        }
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
