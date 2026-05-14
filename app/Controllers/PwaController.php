<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ClubBranding;
use App\Helpers\ClubContext;
use App\Helpers\Csrf;
use App\Helpers\MemberAuth;
use App\Models\DeviceTokenModel;

/**
 * Progressive Web App endpoints dla portalu czlonka.
 *
 * - GET  /portal/manifest.json — dynamic per-klub manifest (branding)
 * - GET  /portal/sw.js         — service worker (cache + push)
 * - GET  /portal/offline.html  — offline shell
 * - POST /portal/push/subscribe   — rejestracja FCM tokenu (wymaga MemberAuth)
 * - POST /portal/push/unsubscribe — wyrejestrowanie tokenu (wymaga MemberAuth)
 *
 * Multi-tenant: branding pochodzi z ClubContext (subdomena → club_id).
 * Fallback do defaultu gdy brak kontekstu.
 */
class PwaController extends BaseController
{
    /** Dynamic manifest.json oparty o branding aktywnego klubu. */
    public function manifest(): never
    {
        $branding   = ClubBranding::current();
        $clubId     = ClubContext::current();
        $clubName   = $clubId !== null ? current_club_name('ClubDesk') : 'ClubDesk';
        $shortName  = mb_substr($clubName, 0, 12, 'UTF-8');
        $primary    = $branding->primaryColor();

        $manifest = [
            'name'             => 'ClubDesk - ' . $clubName,
            'short_name'       => $shortName !== '' ? $shortName : 'ClubDesk',
            'description'      => 'Portal czlonka klubu sportowego',
            'start_url'        => '/portal/dashboard',
            'scope'            => '/portal/',
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'theme_color'      => $primary,
            'background_color' => '#ffffff',
            'lang'             => 'pl',
            'icons'            => $branding->iconUrls(),
            'shortcuts'        => [
                [
                    'name'        => 'Skladki',
                    'short_name'  => 'Skladki',
                    'description' => 'Twoje skladki klubowe',
                    'url'         => '/portal/fees',
                    'icons'       => [['src' => url('/icons/icon-192.png'), 'sizes' => '192x192']],
                ],
                [
                    'name'        => 'Plan treningow',
                    'short_name'  => 'Treningi',
                    'description' => 'Harmonogram treningow',
                    'url'         => '/portal/schedule',
                    'icons'       => [['src' => url('/icons/icon-192.png'), 'sizes' => '192x192']],
                ],
                [
                    'name'        => 'Powiadomienia',
                    'short_name'  => 'Alerty',
                    'description' => 'Twoje powiadomienia',
                    'url'         => '/portal/notifications',
                    'icons'       => [['src' => url('/icons/icon-192.png'), 'sizes' => '192x192']],
                ],
            ],
        ];

        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=300'); // 5 min — pozwala podmienic po rebrandzie
        echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Service worker (JavaScript) wygenerowany dynamicznie. */
    public function serviceWorker(): never
    {
        $clubId   = ClubContext::current();
        $branding = ClubBranding::current();
        $icons    = $branding->iconUrls();
        $iconUrl  = $icons[0]['src'] ?? '/icons/icon-192.png';
        // Cache-bust per rebrand: time() w wersji cache wymusi update SW.
        // SW i tak revaliduje sie przy kazdym wczytaniu strony (browser pyta).
        $cacheVersion = 'clubdesk-' . ($clubId ?? 0) . '-v' . date('YmdH');

        header('Content-Type: application/javascript; charset=utf-8');
        // SW MUSI byc serwowany z prawidlowym scope. Cache-Control: no-cache
        // zeby browser zawsze pytal czy SW sie zmienil.
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Service-Worker-Allowed: /');

        $this->renderNoLayout('pwa/sw.js', [
            'cacheVersion' => $cacheVersion,
            'iconUrl'      => $iconUrl,
            'clubId'       => $clubId,
        ]);
        exit;
    }

    /** Offline fallback page. */
    public function offline(): void
    {
        $branding = ClubBranding::current();
        $clubId   = ClubContext::current();
        $clubName = $clubId !== null ? current_club_name('ClubDesk') : 'ClubDesk';
        $primary  = $branding->primaryColor();
        $logoUrl  = club_logo('main', $clubId) ?? url('/favicon.svg');

        header('Content-Type: text/html; charset=utf-8');
        // Cache offline page — safe, no user data
        header('Cache-Control: public, max-age=300');

        $this->view->setLayout('none');
        $this->view->render('pwa/offline', [
            'clubName'     => $clubName,
            'primaryColor' => $primary,
            'logoUrl'      => $logoUrl,
        ]);
    }

    /**
     * POST /portal/push/subscribe
     * Body JSON: { token: string, platform?: 'web'|'android'|'ios' }
     *
     * Reuses istniejacy `device_tokens` (z PR push v1) — nie tworzymy
     * duplikatu tabeli member_push_tokens.
     */
    public function subscribe(): never
    {
        if (!MemberAuth::check() || MemberAuth::id() === null) {
            $this->json(['ok' => false, 'error' => 'not_authenticated'], 401);
        }
        // Akceptujemy JSON body LUB form data; CSRF tylko gdy form.
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isJson      = stripos($contentType, 'application/json') !== false;
        if ($isJson) {
            $raw    = file_get_contents('php://input') ?: '';
            $input  = json_decode($raw, true) ?: [];
        } else {
            Csrf::verify();
            $input = $_POST;
        }
        $token    = is_string($input['token'] ?? null) ? trim($input['token']) : '';
        $platform = $input['platform'] ?? 'web';
        if (!in_array($platform, ['web', 'android', 'ios'], true)) {
            $platform = 'web';
        }
        if ($token === '' || strlen($token) > 500) {
            $this->json(['ok' => false, 'error' => 'invalid_token'], 422);
        }
        try {
            (new DeviceTokenModel())->register((int)MemberAuth::id(), $token, $platform);
        } catch (\Throwable $e) {
            error_log('PwaController::subscribe failed: ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'server_error'], 500);
        }
        $this->json(['ok' => true]);
    }

    /** POST /portal/push/unsubscribe — usuwa token (no auth-check, idempotent). */
    public function unsubscribe(): never
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isJson      = stripos($contentType, 'application/json') !== false;
        if ($isJson) {
            $raw   = file_get_contents('php://input') ?: '';
            $input = json_decode($raw, true) ?: [];
        } else {
            Csrf::verify();
            $input = $_POST;
        }
        $token = is_string($input['token'] ?? null) ? trim($input['token']) : '';
        if ($token === '') {
            $this->json(['ok' => false, 'error' => 'invalid_token'], 422);
        }
        try {
            (new DeviceTokenModel())->unregister($token);
        } catch (\Throwable $e) {
            error_log('PwaController::unsubscribe failed: ' . $e->getMessage());
        }
        $this->json(['ok' => true]);
    }
}
