<?php

namespace App\Helpers\Webhooks;

use App\Helpers\Database;
use PDO;

/**
 * WebhookDispatcher — publikuje eventy systemowe do subskrybentow klubu
 * i obsluguje niezawodne dostarczenie z retry.
 *
 * Architektura:
 *   - publish() — synchroniczne; tworzy wpisy `webhook_deliveries` ze status='pending'.
 *     Wywolywane z controllerow przy zdarzeniach domenowych (member.created itp.).
 *     Nie blokuje user-facing requestu wysylka HTTP, tylko zapisuje do DB.
 *   - deliverPending() — wywolywane z cli/webhook_worker.php (cron co minute).
 *     Bierze batch pending/retrying, probuje wyslac z timeout 5s, signing HMAC-SHA256.
 *   - Retry policy: 1m, 5m, 30m, 2h, 12h (exponential backoff). Po 5 nieudanych
 *     attempts status -> 'failed' i przestaje retry.
 *
 * Header X-ClubDesk-Signature: sha256=<hmac> liczony z RAW body i `subscription.secret`.
 * Klient weryfikuje: hash_equals($expected, hash_hmac('sha256', $rawBody, $sharedSecret)).
 */
class WebhookDispatcher
{
    /** Backoff w sekundach per attempt index (0-based). */
    private const RETRY_BACKOFF_SEC = [
        0 => 60,        // 1 min (po pierwszej nieudanej probie)
        1 => 300,       // 5 min
        2 => 1800,      // 30 min
        3 => 7200,      // 2h
        4 => 43200,     // 12h
    ];

    public const MAX_ATTEMPTS = 5;
    public const HTTP_TIMEOUT_SEC = 5;

    /** Dostepne typy eventow — UI render checkbox lista, walidacja inputu. */
    public const EVENT_TYPES = [
        'member.created',
        'member.updated',
        'member.deleted',
        'payment.received',
        'training.completed',
        'tournament.finished',
        'subscription.changed',
        'webhook.test',
    ];

    /**
     * Publikuj event do wszystkich aktywnych subskrypcji klubu, ktore subskrybuja `eventType`.
     * Tworzy wpisy `webhook_deliveries` w status='pending' — worker je odbierze.
     *
     * Nigdy nie rzuca wyjatkow do callera (publish jest "fire and forget" z perspektywy domeny).
     */
    public static function publish(int $clubId, string $eventType, array $payload): void
    {
        try {
            $db = Database::pdo();

            $stmt = $db->prepare(
                "SELECT id, event_types FROM webhook_subscriptions
                 WHERE club_id = ? AND active = 1"
            );
            $stmt->execute([$clubId]);
            $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($subs)) return;

            $envelope = [
                'event'    => $eventType,
                'club_id'  => $clubId,
                'data'     => $payload,
                'sent_at'  => date('c'),
            ];
            $payloadJson = json_encode($envelope, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $insert = $db->prepare(
                "INSERT INTO webhook_deliveries
                    (subscription_id, event_type, payload_json, status, next_retry_at)
                 VALUES (?, ?, ?, 'pending', NOW())"
            );

            foreach ($subs as $sub) {
                $types = json_decode((string)$sub['event_types'], true) ?: [];
                if (!in_array($eventType, $types, true)) continue;
                $insert->execute([(int)$sub['id'], $eventType, $payloadJson]);
            }
        } catch (\Throwable $e) {
            error_log('WebhookDispatcher::publish failed: ' . $e->getMessage());
        }
    }

    /**
     * Process pending/retrying deliveries — wywolywane przez worker (cron).
     * Batch 100, sortowane FIFO. Zwraca liczbe przetworzonych wpisow.
     */
    public static function deliverPending(int $batchSize = 100): int
    {
        $db = Database::pdo();

        $stmt = $db->prepare(
            "SELECT d.*, s.target_url, s.secret, s.club_id
             FROM webhook_deliveries d
             JOIN webhook_subscriptions s ON s.id = d.subscription_id
             WHERE d.status IN ('pending','retrying')
               AND (d.next_retry_at IS NULL OR d.next_retry_at <= NOW())
               AND s.active = 1
             ORDER BY d.id ASC
             LIMIT " . (int)$batchSize
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processed = 0;
        foreach ($rows as $row) {
            self::attemptDelivery($db, $row);
            $processed++;
        }
        return $processed;
    }

    /** Wyslij jeden delivery, zapisz rezultat / zaplanuj retry. */
    private static function attemptDelivery(PDO $db, array $row): void
    {
        $deliveryId   = (int)$row['id'];
        $subId        = (int)$row['subscription_id'];
        $url          = (string)$row['target_url'];
        $secret       = (string)$row['secret'];
        $payloadJson  = (string)$row['payload_json'];
        $eventType    = (string)$row['event_type'];
        $attemptsSoFar = (int)$row['attempts'];

        $signature = self::sign($payloadJson, $secret);
        [$httpStatus, $responseBody] = self::httpPost($url, $payloadJson, [
            'Content-Type: application/json; charset=utf-8',
            'X-ClubDesk-Signature: sha256=' . $signature,
            'X-ClubDesk-Event: ' . $eventType,
            'X-ClubDesk-Delivery: ' . $deliveryId,
            'User-Agent: ClubDesk-Webhook/2.0',
        ]);

        $newAttempts = $attemptsSoFar + 1;
        $success     = ($httpStatus >= 200 && $httpStatus < 300);

        if ($success) {
            $db->prepare(
                "UPDATE webhook_deliveries
                 SET status = 'delivered', http_status = ?, response_body = ?,
                     attempts = ?, delivered_at = NOW(), next_retry_at = NULL
                 WHERE id = ?"
            )->execute([$httpStatus, mb_substr($responseBody, 0, 4000), $newAttempts, $deliveryId]);

            $db->prepare(
                "UPDATE webhook_subscriptions
                 SET last_success_at = NOW(), failure_count = 0
                 WHERE id = ?"
            )->execute([$subId]);
            return;
        }

        if ($newAttempts >= self::MAX_ATTEMPTS) {
            $db->prepare(
                "UPDATE webhook_deliveries
                 SET status = 'failed', http_status = ?, response_body = ?,
                     attempts = ?, next_retry_at = NULL
                 WHERE id = ?"
            )->execute([$httpStatus, mb_substr($responseBody, 0, 4000), $newAttempts, $deliveryId]);
        } else {
            $delaySec = self::RETRY_BACKOFF_SEC[$attemptsSoFar] ?? end(self::RETRY_BACKOFF_SEC);
            $nextRetry = (new \DateTimeImmutable('now'))
                ->modify('+' . (int)$delaySec . ' seconds')
                ->format('Y-m-d H:i:s');
            $db->prepare(
                "UPDATE webhook_deliveries
                 SET status = 'retrying', http_status = ?, response_body = ?,
                     attempts = ?, next_retry_at = ?
                 WHERE id = ?"
            )->execute([$httpStatus, mb_substr($responseBody, 0, 4000), $newAttempts, $nextRetry, $deliveryId]);
        }

        $db->prepare(
            "UPDATE webhook_subscriptions
             SET last_failure_at = NOW(), failure_count = failure_count + 1
             WHERE id = ?"
        )->execute([$subId]);
    }

    /**
     * Podpisz payload HMAC-SHA256 z subscription.secret.
     * Header: X-ClubDesk-Signature: sha256=<hex>
     */
    public static function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * POST do URL z timeoutem. Zwraca [httpStatus, responseBody].
     * Na blad transportu zwraca [0, errorMessage].
     */
    private static function httpPost(string $url, string $body, array $headers): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return [0, 'curl_init failed'];
        }
        curl_setopt_array($ch, [
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $body,
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_CONNECTTIMEOUT  => 3,
            CURLOPT_TIMEOUT         => self::HTTP_TIMEOUT_SEC,
            CURLOPT_FOLLOWLOCATION  => false,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
        ]);
        $response = curl_exec($ch);
        $status   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === false) {
            $err = curl_error($ch) ?: 'unknown curl error';
            curl_close($ch);
            return [0, $err];
        }
        curl_close($ch);
        return [$status, (string)$response];
    }

    /**
     * Test endpoint — wysyla od razu (synchronicznie) sample event do podanej subskrypcji.
     * Uzywane przez UI "Test webhook" w admin panelu. Zwraca [httpStatus, body].
     */
    public static function sendTest(int $subscriptionId): array
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            "SELECT id, target_url, secret, club_id FROM webhook_subscriptions WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$subscriptionId]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sub) {
            return [0, 'subscription not found'];
        }

        $envelope = [
            'event'   => 'webhook.test',
            'club_id' => (int)$sub['club_id'],
            'data'    => ['message' => 'Test webhook from ClubDesk', 'subscription_id' => (int)$sub['id']],
            'sent_at' => date('c'),
        ];
        $payload   = json_encode($envelope, JSON_UNESCAPED_UNICODE);
        $signature = self::sign($payload, (string)$sub['secret']);

        return self::httpPost($sub['target_url'], $payload, [
            'Content-Type: application/json; charset=utf-8',
            'X-ClubDesk-Signature: sha256=' . $signature,
            'X-ClubDesk-Event: webhook.test',
            'User-Agent: ClubDesk-Webhook/2.0',
        ]);
    }
}
