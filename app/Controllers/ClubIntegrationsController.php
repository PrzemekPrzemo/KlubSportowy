<?php

namespace App\Controllers;

use App\Helpers\Csrf;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\Webhooks\WebhookDispatcher;

/**
 * Klub admin UI: Webhooki + Tokeny API v2.
 *
 * Dostepne dla roli `zarzad`. Wszystkie POST = CSRF.
 * Multi-tenant: club_id z ClubContext + jawny WHERE w SQL.
 */
class ClubIntegrationsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
        $this->requireClubContext();
        $this->requireRole(['zarzad']);
    }

    /** Index = dwa "taby" w jednym widoku: webhooki + tokeny API. */
    public function index(): void
    {
        $clubId = $this->currentClub();
        $db = Database::pdo();

        $webhooks = $db->prepare(
            "SELECT * FROM webhook_subscriptions WHERE club_id = ? ORDER BY id DESC"
        );
        $webhooks->execute([$clubId]);

        $recentDeliveries = $db->prepare(
            "SELECT d.id, d.event_type, d.status, d.http_status, d.attempts, d.created_at,
                    s.name AS subscription_name
             FROM webhook_deliveries d
             JOIN webhook_subscriptions s ON s.id = d.subscription_id
             WHERE s.club_id = ?
             ORDER BY d.id DESC
             LIMIT 30"
        );
        $recentDeliveries->execute([$clubId]);

        $tokens = $db->prepare(
            "SELECT id, name, scopes, last_used_at, expires_at, revoked_at, created_at
             FROM api_v2_tokens
             WHERE club_id = ?
             ORDER BY id DESC"
        );
        $tokens->execute([$clubId]);

        $this->render('club_integrations/index', [
            'title'         => 'Integracje (Webhooki + API)',
            'webhooks'      => $webhooks->fetchAll(),
            'deliveries'    => $recentDeliveries->fetchAll(),
            'tokens'        => $tokens->fetchAll(),
            'availableEvents' => WebhookDispatcher::EVENT_TYPES,
            'availableScopes' => self::availableScopes(),
            'plainToken'    => Session::getFlash('plain_token'),
        ]);
    }

    // ── Webhooki ────────────────────────────────────────────────────────

    public function storeWebhook(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();

        $name      = trim((string)($_POST['name'] ?? ''));
        $targetUrl = trim((string)($_POST['target_url'] ?? ''));
        $events    = (array)($_POST['events'] ?? []);

        if ($name === '' || $targetUrl === '' || empty($events)) {
            Session::flash('error', 'Nazwa, URL i co najmniej jeden event sa wymagane.');
            $this->redirect('club/integrations');
        }
        if (!filter_var($targetUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $targetUrl)) {
            Session::flash('error', 'Podaj prawidlowy URL (http/https).');
            $this->redirect('club/integrations');
        }
        if (strlen($targetUrl) > 500) {
            Session::flash('error', 'URL za dlugi (max 500 znakow).');
            $this->redirect('club/integrations');
        }

        $allowed = WebhookDispatcher::EVENT_TYPES;
        $events  = array_values(array_intersect($events, $allowed));
        if (empty($events)) {
            Session::flash('error', 'Wybrane eventy nie sa obslugiwane.');
            $this->redirect('club/integrations');
        }

        $secret = bin2hex(random_bytes(32)); // 64 hex chars

        $stmt = Database::pdo()->prepare(
            "INSERT INTO webhook_subscriptions (club_id, name, target_url, secret, event_types, active)
             VALUES (?, ?, ?, ?, ?, 1)"
        );
        $stmt->execute([
            $clubId,
            mb_substr($name, 0, 100),
            $targetUrl,
            $secret,
            json_encode($events, JSON_UNESCAPED_UNICODE),
        ]);

        Session::flash('success', 'Webhook dodany. Secret (do weryfikacji podpisu): ' . $secret);
        $this->redirect('club/integrations');
    }

    public function deleteWebhook(string $id): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $stmt = Database::pdo()->prepare(
            "DELETE FROM webhook_subscriptions WHERE id = ? AND club_id = ?"
        );
        $stmt->execute([(int)$id, $clubId]);
        Session::flash('success', 'Webhook usuniety.');
        $this->redirect('club/integrations');
    }

    public function testWebhook(string $id): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        // Multi-tenant guard — najpierw weryfikacja przynaleznosci.
        $stmt = Database::pdo()->prepare(
            "SELECT id FROM webhook_subscriptions WHERE id = ? AND club_id = ? LIMIT 1"
        );
        $stmt->execute([(int)$id, $clubId]);
        if (!$stmt->fetch()) {
            Session::flash('error', 'Webhook nie istnieje lub nie nalezy do tego klubu.');
            $this->redirect('club/integrations');
        }

        [$status, $body] = WebhookDispatcher::sendTest((int)$id);
        if ($status >= 200 && $status < 300) {
            Session::flash('success', "Test webhook OK ({$status}). Odpowiedz: " . mb_substr($body, 0, 200));
        } else {
            Session::flash('error', "Test webhook nieudany (status: {$status}). " . mb_substr($body, 0, 200));
        }
        $this->redirect('club/integrations');
    }

    // ── Tokeny API v2 ───────────────────────────────────────────────────

    public function storeToken(): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();

        $name      = trim((string)($_POST['name'] ?? ''));
        $scopes    = (array)($_POST['scopes'] ?? []);
        $expiresAt = trim((string)($_POST['expires_at'] ?? '')); // YYYY-MM-DD or empty

        if ($name === '' || empty($scopes)) {
            Session::flash('error', 'Nazwa i co najmniej jeden scope sa wymagane.');
            $this->redirect('club/integrations');
        }

        $allowed = self::availableScopes();
        $scopes  = array_values(array_intersect($scopes, $allowed));
        if (empty($scopes)) {
            Session::flash('error', 'Wybrane scope-y nie sa rozpoznawalne.');
            $this->redirect('club/integrations');
        }

        $expiresAtDb = null;
        if ($expiresAt !== '') {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiresAt)) {
                Session::flash('error', 'Format daty wygasniecia: YYYY-MM-DD.');
                $this->redirect('club/integrations');
            }
            $expiresAtDb = $expiresAt . ' 23:59:59';
        }

        // Plain token format: "cdk_v2_<48 hex>" — niemoz pomylic z mt_/ks_ tokenami v1.
        $plain = 'cdk_v2_' . bin2hex(random_bytes(24));
        $hash  = hash('sha256', $plain);

        $stmt = Database::pdo()->prepare(
            "INSERT INTO api_v2_tokens (club_id, name, token_hash, scopes, expires_at)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $clubId,
            mb_substr($name, 0, 100),
            $hash,
            json_encode($scopes, JSON_UNESCAPED_UNICODE),
            $expiresAtDb,
        ]);

        // Plain token pokazany TYLKO raz przez flash session.
        Session::flash('plain_token', $plain);
        Session::flash('success', 'Token utworzony. Skopiuj go teraz — nie bedzie widoczny ponownie.');
        $this->redirect('club/integrations');
    }

    public function revokeToken(string $id): void
    {
        Csrf::verify();
        $clubId = $this->currentClub();
        $stmt = Database::pdo()->prepare(
            "UPDATE api_v2_tokens SET revoked_at = NOW()
             WHERE id = ? AND club_id = ? AND revoked_at IS NULL"
        );
        $stmt->execute([(int)$id, $clubId]);
        Session::flash('success', 'Token uniewazniony.');
        $this->redirect('club/integrations');
    }

    /** Lista dostepnych scope-ow dla UI. */
    public static function availableScopes(): array
    {
        return [
            'members:read',
            'trainings:read',
            'tournaments:read',
            'payments:read',
        ];
    }
}
