<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Gateway\GatewayException;
use App\Helpers\Gateway\GatewayFactory;
use App\Helpers\Gateway\WebhookEvent;
use App\Models\ClubPaymentGatewayModel;
use App\Models\OnlinePaymentModel;
use App\Models\PaymentDueModel;

/**
 * Faza T.3 — uniwersalny webhook router dla wszystkich bramek.
 *
 * Endpoint: POST /api/v1/payment/webhook/:provider
 * (BEZ CSRF — uwierzytelniane sygnaturą HMAC per adapter)
 *
 * Logic:
 *   1. Wyłuskaj provider z URL → wybierz adapter z GatewayFactory
 *   2. adapter.verifyWebhook(rawPayload, headers) → WebhookEvent
 *   3. Znajdź online_payment po external_id (provider_id) lub
 *      internalReference
 *   4. Dla statusu PAID:
 *      - online_payments.markPaid()
 *      - bookToPayments() → INSERT do payments
 *      - PaymentDueModel.applyPayment() jeśli ref pasuje do due#X
 *   5. Zwróć 200 OK
 *
 * Stary endpoint /webhook/payment (PaymentWebhookController) pozostaje
 * dla SaaS billing klubów (billing_invoices) — różny use case.
 */
class GatewayWebhookController extends BaseController
{
    public function handle(string $provider): void
    {
        $rawPayload = file_get_contents('php://input') ?: '';

        // Wyłuskaj club_id — albo z payload (Stripe metadata), albo
        // z hint param ?club_id=X w URL (P24/PayU/Tpay routing).
        $clubId = $this->detectClubId($provider, $rawPayload);
        if ($clubId === null) {
            $this->json(['error' => 'cannot determine club_id'], 400);
        }

        // Załaduj config bramki dla klubu (z P.5)
        $gatewayConfig = (new ClubPaymentGatewayModel())->findByProvider($provider);
        if (!$gatewayConfig || (int)$gatewayConfig['club_id'] !== $clubId) {
            $this->json(['error' => 'gateway not configured for club'], 404);
        }

        $adapter = GatewayFactory::forProvider($provider, $gatewayConfig);
        if ($adapter === null) {
            $this->json(['error' => 'unsupported provider: ' . $provider], 400);
        }

        // Headers (case-insensitive, normalize)
        $headers = $this->getHeaders();

        try {
            $event = $adapter->verifyWebhook($rawPayload, $headers);
        } catch (GatewayException $e) {
            error_log('Gateway webhook verification failed: ' . $e->getMessage());
            $this->json(['error' => 'verification failed: ' . $e->getMessage()], 403);
        }

        $this->processEvent($event, $clubId);
        $this->json(['status' => 'ok'], 200);
    }

    /**
     * Próba wyłuskania club_id z webhook'a:
     *   1. ?club_id=X w query string (rekomendowane dla P24/PayU/Tpay
     *      bo mają to w URL notify)
     *   2. Stripe metadata.club_id w body
     *   3. internalReference parsing — jeśli zawiera due#X, sprawdzamy
     *      payment_due.club_id
     */
    private function detectClubId(string $provider, string $payload): ?int
    {
        // 1. Query param
        if (!empty($_GET['club_id']) && is_numeric($_GET['club_id'])) {
            return (int)$_GET['club_id'];
        }

        // 2. Stripe metadata
        if ($provider === 'stripe') {
            $decoded = json_decode($payload, true);
            $cid = $decoded['data']['object']['metadata']['club_id'] ?? null;
            if (is_numeric($cid)) return (int)$cid;
        }

        // 3. Look up via online_payments by reference
        if (!empty($_GET['ref'])) {
            $stmt = Database::pdo()->prepare(
                "SELECT club_id FROM online_payments WHERE provider_id = ? OR description LIKE ? LIMIT 1"
            );
            $stmt->execute([(string)$_GET['ref'], '%' . $_GET['ref'] . '%']);
            $cid = $stmt->fetchColumn();
            if ($cid !== false) return (int)$cid;
        }

        return null;
    }

    private function getHeaders(): array
    {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders() ?: [];
        }
        // Fallback do $_SERVER (nginx FPM bez getallheaders)
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $name = ucwords(strtolower($name), '-');
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    /**
     * Process WebhookEvent — update online_payment, book do payments,
     * apply do payment_dues.
     */
    private function processEvent(WebhookEvent $event, int $clubId): void
    {
        $db = Database::pdo();

        // Znajdź online_payment po external_id (provider_id w DB) lub
        // po internal reference (description match)
        $opModel = new OnlinePaymentModel();
        $opStmt = $db->prepare(
            "SELECT * FROM online_payments
             WHERE club_id = ? AND (provider_id = ? OR description LIKE ?)
             ORDER BY id DESC LIMIT 1"
        );
        $opStmt->execute([$clubId, $event->externalId, '%' . ($event->internalReference ?? '') . '%']);
        $op = $opStmt->fetch();

        if (!$op) {
            error_log("Webhook event without matching online_payment: clubId={$clubId}, ext={$event->externalId}, ref={$event->internalReference}");
            return;
        }

        $opId = (int)$op['id'];

        if ($event->status === WebhookEvent::STATUS_PAID && $op['status'] !== 'paid') {
            $opModel->markPaid($opId, $event->externalId);
            $opModel->bookToPayments($opId);

            // Jeśli internalReference wskazuje na due#X → applyPayment
            if ($event->internalReference && preg_match('/due#(\d+)/', (string)$event->internalReference, $m)) {
                $dueId = (int)$m[1];
                (new PaymentDueModel())->applyPayment($dueId, (float)$op['amount']);
            }

            // Audit
            $db->prepare(
                "INSERT INTO activity_log (club_id, action, entity, details, ip_address)
                 VALUES (?, 'payment_received', 'online_payment', ?, ?)"
            )->execute([
                $clubId,
                json_encode([
                    'op_id' => $opId,
                    'amount' => $op['amount'],
                    'provider' => $event->rawPayload['type'] ?? null,
                ], JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } elseif ($event->status === WebhookEvent::STATUS_FAILED && $op['status'] !== 'paid') {
            $opModel->markFailed($opId, 'webhook reported failed');
        }
    }
}
