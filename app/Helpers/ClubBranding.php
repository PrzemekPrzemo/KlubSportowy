<?php

namespace App\Helpers;

use App\Models\ClubCustomizationModel;

/**
 * Whitelabel — facade nad ClubCustomizationModel dla aktywnego klubu.
 *
 * Daje wygodne accessory do brandingu (custom CSS, favicon, email header,
 * SMS sender ID, email from name) z fallbackami i request-scope cache.
 *
 * Uzywany w:
 *   - layouts/main.php          → custom CSS + favicon
 *   - EmailService              → email_header_html + email_from_name
 *   - SmsService                → sms_sender_id
 *   - BrandingAssetController   → favicon serving
 *
 * Sanitization jest robiona w czasie zapisu (controller), nie w runtime
 * read-em (wczytane dane juz sa bezpieczne).
 */
class ClubBranding
{
    /** Request-scope cache: clubId => raw row */
    private static array $cache = [];

    /** Convenience: branding dla aktywnego ClubContext (lub defaults). */
    public static function current(): self
    {
        $clubId = ClubContext::current();
        return self::forClub($clubId);
    }

    public static function forClub(?int $clubId): self
    {
        $key = $clubId === null ? 0 : $clubId;
        if (!isset(self::$cache[$key])) {
            if ($clubId === null) {
                self::$cache[$key] = ClubCustomizationModel::defaults();
            } else {
                try {
                    $row = (new ClubCustomizationModel())->findForClub($clubId);
                    self::$cache[$key] = $row ?? ClubCustomizationModel::defaults();
                } catch (\Throwable) {
                    // DB nie gotowa albo brak migracji — degrade do defaults.
                    self::$cache[$key] = ClubCustomizationModel::defaults();
                }
            }
        }
        return new self((int)$key, self::$cache[$key]);
    }

    /** Reset cache — przydatny dla testow lub po zapisie. */
    public static function flushCache(?int $clubId = null): void
    {
        if ($clubId === null) {
            self::$cache = [];
        } else {
            unset(self::$cache[$clubId]);
        }
    }

    private function __construct(private int $clubId, private array $row) {}

    public function __get(string $name): mixed
    {
        return $this->row[$name] ?? null;
    }

    public function clubId(): int
    {
        return $this->clubId;
    }

    public function toArray(): array
    {
        return $this->row;
    }

    /** Custom CSS (juz sanitized w zapisie) lub null jesli brak. */
    public function customCss(): ?string
    {
        $css = $this->row['custom_css'] ?? null;
        return is_string($css) && $css !== '' ? $css : null;
    }

    /** Sciezka relatywna do public/ favicon, lub null. */
    public function faviconPath(): ?string
    {
        $p = $this->row['favicon_path'] ?? null;
        return is_string($p) && $p !== '' ? $p : null;
    }

    /** Email header HTML — fallback na default z logo + nazwa klubu. */
    public function emailHeaderOrDefault(string $clubName = '', string $logoUrl = '', string $primaryColor = '#EE2C28'): string
    {
        $custom = $this->row['email_header_html'] ?? null;
        if (is_string($custom) && trim($custom) !== '') {
            return $custom;
        }
        // Default header: logo + nazwa klubu na tle primary color.
        $name  = htmlspecialchars($clubName, ENT_QUOTES, 'UTF-8');
        $color = htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8');
        $logo  = '';
        if ($logoUrl !== '') {
            $logoEsc = htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8');
            $logo = '<img src="' . $logoEsc . '" alt="' . $name . '" style="max-height:50px; max-width:160px; vertical-align:middle;">';
        }
        return '<div style="background:' . $color . '; padding:20px 24px; color:#fff;">'
             . $logo
             . '<strong style="font-size:16px; margin-left:8px; vertical-align:middle;">' . $name . '</strong>'
             . '</div>';
    }

    /** SMS sender ID — fallback na globalny default. */
    public function smsSenderOrDefault(string $globalDefault): string
    {
        $s = $this->row['sms_sender_id'] ?? null;
        return is_string($s) && $s !== '' ? $s : $globalDefault;
    }

    /** Email from name (display name). */
    public function emailFromNameOrDefault(string $globalDefault): string
    {
        $s = $this->row['email_from_name'] ?? null;
        return is_string($s) && $s !== '' ? $s : $globalDefault;
    }

    /** Primary color (CSS color) z fallbackiem na default. */
    public function primaryColor(): string
    {
        $c = $this->row['primary_color'] ?? null;
        if (is_string($c) && preg_match('/^#[0-9A-Fa-f]{3,8}$/', $c)) {
            return $c;
        }
        return '#EE2C28';
    }

    /**
     * Zwraca URL-e do ikon klubu w roznych rozmiarach (PWA manifest +
     * apple-touch). Browser sam zeskaluje pojedynczy PNG do mniejszych
     * rozmiarow, wiec nie generujemy w PHP — wskazujemy jeden URL z
     * deklarowanymi rozmiarami.
     *
     * Fallback: domyslne ikony ClubDesk w /icons/.
     *
     * @return array{src:string,sizes:string,type:string,purpose?:string}[]
     */
    public function iconUrls(): array
    {
        $logo = $this->row['logo_path'] ?? null;
        // PNG-y (raster) sa preferowane dla manifestu PWA — Chrome/Android
        // wymaga 192 i 512. SVG akceptowane jako 'any maskable'.
        $isPng = is_string($logo) && str_ends_with(strtolower($logo), '.png');
        if ($isPng && $logo !== null) {
            $url = url($logo);
            return [
                ['src' => $url, 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => $url, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ];
        }
        // Fallback: domyslne ikony ClubDesk
        return [
            ['src' => url('/favicon.svg'),       'sizes' => 'any',     'type' => 'image/svg+xml', 'purpose' => 'any maskable'],
            ['src' => url('/icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => url('/icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
        ];
    }

    /** Apple touch icon URL (180x180 idealne, ale browser sobie poradzi). */
    public function appleTouchIconUrl(): string
    {
        $logo = $this->row['logo_path'] ?? null;
        if (is_string($logo) && str_ends_with(strtolower($logo), '.png')) {
            return url($logo);
        }
        return url('/icons/icon-192.png');
    }
}
