<?php
// ============================================================
// Global helper functions (loaded via require in public/index.php)
// ============================================================

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim(BASE_URL, '/');
        $path = ltrim($path, '/');
        return $base . '/' . $path;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $input = \App\Helpers\Session::getFlash('_old_input') ?? [];
        return $input[$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $default = null): mixed
    {
        return \App\Helpers\Session::getFlash($key, $default);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return \App\Helpers\Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Helpers\Csrf::token();
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd.m.Y'): string
    {
        if (!$date) return '—';
        $dt = DateTime::createFromFormat('Y-m-d', substr($date, 0, 10));
        return $dt ? $dt->format($format) : $date;
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $dt, string $format = 'd.m.Y H:i'): string
    {
        if (!$dt) return '—';
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $dt);
        return $d ? $d->format($format) : $dt;
    }
}

if (!function_exists('format_money')) {
    function format_money(mixed $amount): string
    {
        return number_format((float)$amount, 2, ',', ' ') . ' zł';
    }
}

if (!function_exists('days_until')) {
    function days_until(?string $date): ?int
    {
        if ($date === null) return null;
        $today  = new DateTime('today');
        $target = new DateTime($date);
        return (int)$today->diff($target)->days * ($target >= $today ? 1 : -1);
    }
}

if (!function_exists('alert_class')) {
    function alert_class(?int $days, int $warnDays = 30): string
    {
        if ($days === null)     return 'secondary';
        if ($days < 0)          return 'danger';
        if ($days <= $warnDays) return 'warning';
        return 'success';
    }
}

if (!function_exists('now')) {
    function now(string $format = 'Y-m-d H:i:s'): string
    {
        return (new DateTime())->format($format);
    }
}

if (!function_exists('__')) {
    function __(string $key, array $params = []): string
    {
        return \App\Helpers\Translator::t($key, $params);
    }
}

if (!function_exists('club_logo')) {
    /**
     * URL do logo aktywnego klubu (lub klubu o danym id).
     * @param string   $variant 'main'|'alt'|'dark'  — fallback na 'main' gdy brak
     * @param int|null $clubId  null = current club z kontekstu
     */
    function club_logo(string $variant = 'main', ?int $clubId = null): ?string
    {
        $clubId ??= \App\Helpers\ClubContext::current();
        if ($clubId === null) return null;
        static $cache = [];
        $key = $clubId . ':' . $variant;
        if (isset($cache[$key])) return $cache[$key];
        try {
            $row = (new \App\Models\ClubCustomizationModel())->findForClub($clubId);
            if (!$row) return $cache[$key] = null;
            $col  = $variant === 'main' ? 'logo_path' : "logo_{$variant}_path";
            $path = $row[$col] ?? null;
            // Fallback: alt/dark → main gdy brak konkretnego wariantu
            if (!$path && $variant !== 'main') {
                $path = $row['logo_path'] ?? null;
            }
            return $cache[$key] = $path ? url($path) : null;
        } catch (\Throwable) {
            return $cache[$key] = null;
        }
    }
}

if (!function_exists('sport_logo')) {
    /**
     * URL do logo sekcji sportowej klubu (club_sport_id).
     * Fallback: alt/dark → main → null gdy brak.
     */
    function sport_logo(int $clubSportId, string $variant = 'main'): ?string
    {
        static $cache = [];
        $key = $clubSportId . ':' . $variant;
        if (isset($cache[$key])) return $cache[$key];
        try {
            $row = (new \App\Models\ClubSportModel())->findById($clubSportId);
            if (!$row) return $cache[$key] = null;
            $col  = "logo_{$variant}_path";
            $path = $row[$col] ?? null;
            if (!$path && $variant !== 'main') {
                $path = $row['logo_main_path'] ?? null;
            }
            return $cache[$key] = $path ? url($path) : null;
        } catch (\Throwable) {
            return $cache[$key] = null;
        }
    }
}

if (!function_exists('current_club_name')) {
    function current_club_name(string $default = ''): string
    {
        $clubId = \App\Helpers\ClubContext::current();
        if ($clubId === null) {
            return $default;
        }
        static $cache = [];
        if (!isset($cache[$clubId])) {
            $row = (new \App\Models\ClubModel())->findById($clubId);
            $cache[$clubId] = $row['name'] ?? $default;
        }
        return $cache[$clubId];
    }
}

if (!function_exists('system_logo')) {
    /**
     * Zwraca URL do logo systemu skonfigurowanego przez Master Admina.
     * Fallback: wbudowane pliki SVG z public/images/.
     *
     * @param string $variant 'color' (na jasnym tle) | 'white' (na ciemnym tle)
     */
    function system_logo(string $variant = 'color'): string
    {
        static $cache = [];
        if (!isset($cache[$variant])) {
            try {
                $key = $variant === 'white' ? 'system_logo_white' : 'system_logo_color';
                $val = (new \App\Models\SettingModel())->get($key, '');
                $val = is_string($val) ? trim($val) : '';
                if ($val !== '') {
                    $cache[$variant] = url($val);
                } else {
                    $cache[$variant] = '/images/logo-cd' . ($variant === 'white' ? '-white' : '') . '.svg';
                }
            } catch (\Throwable) {
                $cache[$variant] = '/images/logo-cd' . ($variant === 'white' ? '-white' : '') . '.svg';
            }
        }
        return $cache[$variant];
    }
}
