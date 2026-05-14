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
}
