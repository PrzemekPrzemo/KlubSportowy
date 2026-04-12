<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\PaymentGateway;

/**
 * Webhook endpoint dla Stripe/Przelewy24.
 * POST /webhook/payment — BEZ CSRF (uwierzytelniane podpisem HMAC).
 */
class PaymentWebhookController extends BaseController
{
    public function handle(): void
    {
        $payload   = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        if ($payload === '' || $sigHeader === '') {
            $this->json(['error' => 'missing payload or signature'], 400);
        }

        // Wyszukaj club_id z metadanych (lub nagłówka)
        $decoded = json_decode($payload, true);
        $clubId  = (int)($decoded['data']['object']['metadata']['club_id'] ?? 0);
        if ($clubId <= 0) {
            $this->json(['error' => 'missing club_id in metadata'], 400);
        }

        $event = PaymentGateway::verifyWebhook($payload, $sigHeader, $clubId);
        if ($event === null) {
            $this->json(['error' => 'invalid signature'], 403);
        }

        $db = Database::pdo();

        if ($event['type'] === 'checkout.session.completed') {
            // Oznacz najnowszą fakturę klubu jako opłaconą
            $stmt = $db->prepare(
                "UPDATE billing_invoices SET status = 'paid', paid_at = NOW()
                 WHERE club_id = ? AND status = 'issued'
                 ORDER BY issue_date DESC LIMIT 1"
            );
            $stmt->execute([$clubId]);

            // Aktywuj subskrypcję
            $db->prepare(
                "UPDATE club_subscriptions SET status = 'active' WHERE club_id = ?"
            )->execute([$clubId]);

            // Log
            $db->prepare(
                "INSERT INTO activity_log (club_id, action, entity, details, ip_address)
                 VALUES (?, 'payment_received', 'billing', ?, ?)"
            )->execute([$clubId, 'Stripe webhook: checkout.session.completed', $_SERVER['REMOTE_ADDR'] ?? null]);
        }

        $this->json(['status' => 'ok'], 200);
    }
}
