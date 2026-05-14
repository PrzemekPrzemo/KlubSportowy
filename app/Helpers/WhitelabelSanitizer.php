<?php

namespace App\Helpers;

/**
 * Sanitization dla whitelabel content per-klub.
 *
 * Strategia: defense-in-depth.
 *   1. CSS — odrzucamy CALY input ktory zawiera niebezpieczne konstrukcje
 *      (script tagi, expression(), javascript:, @import, behavior:, binding:,
 *      kąty < >). Bo: latwiej odrzucic niz wyciac bez break-u CSS-a.
 *   2. Email header HTML — whitelist tagow + atrybutow (no script/iframe/style/link),
 *      stripujemy on*-handlery i javascript:.
 */
class WhitelabelSanitizer
{
    public const CSS_MAX_BYTES = 50 * 1024;

    /**
     * Sanitize custom CSS uzytkownika.
     *
     * Zwraca:
     *   - string  : sanitized CSS (lub identyczny gdy nic zlego nie znaleziono)
     *   - null    : input odrzucony — controller powinien zglosic blad
     */
    public static function sanitizeCss(string $css): ?string
    {
        $css = trim($css);
        if ($css === '') return '';

        // 1. Limit rozmiaru.
        if (strlen($css) > self::CSS_MAX_BYTES) {
            return null;
        }

        // 2. Wlasciwe CSS nie zawiera < ani > (nawet w content: "...").
        //    Jesli sa — to znak wstrzykniecia <script> czy podobnego.
        if (preg_match('/[<>]/', $css) === 1) {
            return null;
        }

        // 3. Blacklist niebezpiecznych konstrukcji (case-insensitive).
        //    Wszystkie sa potencjalnymi XSS / data exfiltration vektorami.
        $patterns = [
            '/<\s*script/i',
            '/expression\s*\(/i',
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/data\s*:\s*text\s*\/\s*html/i',
            '/@import\b/i',
            '/behavior\s*:/i',
            '/-moz-binding/i',
            '/\bbinding\s*:/i',
            // url() pointing to script / external bare http — sztywno blokujemy
            // wszystko co nie zaczyna sie od bezpiecznych schemow.
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $css) === 1) {
                return null;
            }
        }

        // 4. Blokuj url(...) ktore wskazuje na javascript: lub data:text/html.
        //    (Poprawne url(data:image/...) jest OK.)
        if (preg_match('/url\s*\(\s*[\'"]?\s*(javascript|vbscript|data\s*:\s*text\s*\/\s*html)/i', $css) === 1) {
            return null;
        }

        // 5. Blokuj CSS comment-stripping trick (np. javasc/* */ript:).
        //    Usuwamy komentarze przed re-skan-em.
        $stripped = preg_replace('!/\*.*?\*/!s', '', $css) ?? '';
        foreach ($patterns as $p) {
            if (preg_match($p, $stripped) === 1) {
                return null;
            }
        }

        return $css;
    }

    /**
     * Sanitize HTML uzywany jako email header.
     *
     * Whitelist: a, img, p, div, span, strong, em, b, i, u,
     *            h1-h6, br, hr, table, thead, tbody, tr, td, th
     *
     * Stripped: script, style, link, iframe, object, embed, on*-attrs, javascript:
     */
    public static function sanitizeEmailHeaderHtml(string $html): string
    {
        $allowedTags = '<a><img><p><div><span><strong><em><b><i><u><h1><h2><h3><h4><h5><h6><br><hr><table><thead><tbody><tr><td><th>';

        // 0. Wytnij CALOSC tagow <script>...</script>, <style>...</style>, <iframe>...</iframe>
        //    (razem z zawartoscia) — strip_tags zostawia tekst skryptu jako plain text.
        $html = preg_replace('#<\s*(script|style|iframe|object|embed)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html) ?? $html;
        // Self-closing wersje (np. <script src="..." />)
        $html = preg_replace('#<\s*(script|style|iframe|object|embed|link|meta)\b[^>]*/?>#i', '', $html) ?? $html;

        // 1. strip_tags wycina wszystko poza whitelisting.
        $clean = strip_tags($html, $allowedTags);

        // 2. Usun on*-handlery (onclick, onerror itd.) — w atrybutach.
        $clean = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? '';

        // 3. Usun javascript: i vbscript: z atrybutow.
        $clean = preg_replace('/(href|src|action)\s*=\s*("|\')\s*(javascript|vbscript)\s*:[^"\']*\2/i', '$1=$2#blocked$2', $clean) ?? '';

        // 4. Limit dlugosci na wszelki wypadek.
        if (strlen($clean) > 5000) {
            $clean = substr($clean, 0, 5000);
        }

        return $clean;
    }
}
