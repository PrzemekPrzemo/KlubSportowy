<?php

namespace App\Helpers;

use App\Models\WebhookEndpointModel;
use App\Models\WebhookLogModel;

class WebhookService
{
    /**
     * Wyslij webhook do wszystkich aktywnych endpointow subskrybujacych dany event.
     */
    public static function fire(int $clubId, string $event, array $data): void
    {
        $endpointModel = (new WebhookEndpointModel())->withoutScope();
        $endpoints = $endpointModel->activeForEvent($clubId, $event);

        $logModel = new WebhookLogModel();

        foreach ($endpoints as $ep) {
            $payload = json_encode([
                'event'   => $event,
                'club_id' => $clubId,
                'data'    => $data,
                'sent_at' => date('Y-m-d\TH:i:sP'),
            ], JSON_UNESCAPED_UNICODE);

            $signature = hash_hmac('sha256', $payload, $ep['secret']);

            $ch = curl_init($ep['url']);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-Webhook-Signature: sha256=' . $signature,
                    'X-Webhook-Event: ' . $event,
                    'User-Agent: KlubSportowy-Webhook/1.0',
                ],
            ]);

            $responseBody = curl_exec($ch);
            $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($responseBody === false) {
                $responseBody = curl_error($ch);
                $responseCode = 0;
            }

            curl_close($ch);

            $logModel->insert([
                'endpoint_id'   => (int)$ep['id'],
                'event'         => $event,
                'payload'       => $payload,
                'response_code' => $responseCode,
                'response_body' => mb_substr((string)$responseBody, 0, 5000),
            ]);
        }
    }

    /**
     * Lista obslugiwanych typow eventow.
     */
    public static function availableEvents(): array
    {
        return [
            'member.created',
            'member.updated',
            'member.deleted',
            'payment.received',
            'payment.deleted',
            'event.created',
            'event.updated',
            'training.created',
            'training.attendance',
            'announcement.published',
            'medical_exam.expiring',
        ];
    }
}
