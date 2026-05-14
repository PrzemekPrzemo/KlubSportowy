<?php

namespace App\Controllers;

use App\Helpers\ClubBranding;
use App\Helpers\ClubContext;

/**
 * Serwuje per-klub branding assets (favicon, custom CSS jako plik).
 *
 * Routing: /favicon.ico — kontroler czyta aktywny ClubContext;
 * jesli klub ma wlasny favicon to serwuje plik z public/uploads/branding/,
 * w przeciwnym razie 302 na domyslny /favicon.svg.
 */
class BrandingAssetController extends BaseController
{
    public function __construct()
    {
        // Bez auth — favicon jest publiczny.
        \App\Helpers\Session::start();
    }

    public function favicon(): void
    {
        // Pozwala na ?club=:subdomain (przydatne gdy brak sesyjnego ClubContext-u).
        $sub = isset($_GET['club']) ? trim((string)$_GET['club']) : '';
        $clubId = null;
        if ($sub !== '' && preg_match('/^[a-z0-9-]{1,80}$/i', $sub) === 1) {
            try {
                $stmt = \App\Helpers\Database::pdo()->prepare(
                    'SELECT club_id FROM club_customization WHERE subdomain = ? LIMIT 1'
                );
                $stmt->execute([$sub]);
                $row = $stmt->fetch();
                if ($row) $clubId = (int)$row['club_id'];
            } catch (\Throwable) {}
        }
        if ($clubId === null) {
            $clubId = ClubContext::current();
        }

        $branding = $clubId !== null ? ClubBranding::forClub($clubId) : ClubBranding::current();
        $rel = $branding->faviconPath();
        if ($rel !== null) {
            $abs = ROOT_PATH . '/public/' . ltrim($rel, '/');
            if (is_file($abs)) {
                $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
                $mime = $ext === 'png' ? 'image/png' : 'image/x-icon';
                header('Content-Type: ' . $mime);
                header('Cache-Control: public, max-age=86400');
                header('Content-Length: ' . filesize($abs));
                readfile($abs);
                exit;
            }
        }

        // Fallback: domyslny systemowy favicon (SVG).
        $defaultSvg = ROOT_PATH . '/public/favicon.svg';
        if (is_file($defaultSvg)) {
            header('Content-Type: image/svg+xml');
            header('Cache-Control: public, max-age=86400');
            readfile($defaultSvg);
            exit;
        }

        // No favicon at all — wymus 204 zamiast 404 (przegladarki spamuja log).
        http_response_code(204);
        exit;
    }
}
