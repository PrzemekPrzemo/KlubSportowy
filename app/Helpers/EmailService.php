<?php

namespace App\Helpers;

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
    /** Wysyła wiadomość NATYCHMIAST (nie używa kolejki). */
    public static function send(int $clubId, string $toEmail, string $subject, string $body, ?string $toName = null): bool
    {
        $config = self::resolveSmtpConfig($clubId);

        $from = $config['from_email'] ?: ('noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $fromName = $config['from_name'] ?: 'KlubSportowy';

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "From: " . self::encodeHeader($fromName) . " <{$from}>\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= "X-Mailer: KlubSportowy\r\n";

        $subjectEncoded = self::encodeHeader($subject);

        // Jeśli skonfigurowano SMTP — próbujemy wysłać przez PHPMailer (jeśli dostępny)
        if ($config['enabled'] && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return self::sendViaSmtp($config, $toEmail, $toName, $subject, $body);
        }

        // Fallback: natywna mail()
        return @mail($toEmail, $subjectEncoded, $body, $headers);
    }

    /** Dodaje wiadomość do kolejki — worker wysyła w tle. */
    public static function queue(int $clubId, string $toEmail, string $subject, string $body, ?string $toName = null, ?string $templateType = null): int
    {
        return (new EmailQueueModel())
            ->enqueue($clubId, $toEmail, $toName, $subject, $body, $templateType, Auth::id());
    }

    /** Kolejkuje wiadomość z szablonu, renderując placeholdery. */
    public static function queueFromTemplate(int $clubId, string $templateType, string $toEmail, array $vars, ?string $toName = null): ?int
    {
        $tpl = (new EmailTemplateModel())->resolve($templateType, $clubId);
        if (!$tpl) return null;
        $rendered = EmailTemplateModel::render($tpl, $vars);
        return self::queue($clubId, $toEmail, $rendered['subject'], $rendered['body'], $toName, $templateType);
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

    private static function sendViaSmtp(array $config, string $toEmail, ?string $toName, string $subject, string $body): bool
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
            $mail->Body    = $body;
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
