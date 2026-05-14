<?php

namespace App\Helpers;

/**
 * Minimalny markdown → HTML renderer dla in-app help center.
 *
 * Pokrywa podstawowy podzbiór CommonMark wystarczający dla naszych
 * docs/*.md: nagłówki, listy (ul/ol), fenced code blocks, inline code,
 * bold/italic, linki, blockquotes, paragrafy.
 *
 * Wszystkie segmenty są escape'owane przez htmlspecialchars przed
 * markdown-ową konwersją — XSS bezpieczne nawet jeśli ktoś wsadzi
 * `<script>` do .md.
 *
 * TODO: zamienić na Parsedown po dodaniu do composer.json.
 *       Powered by minimal inline parser — wystarczające dla MVP.
 */
final class Markdown
{
    public static function render(string $markdown): string
    {
        // Normalizuj EOL
        $md = str_replace(["\r\n", "\r"], "\n", $markdown);

        // Wyciągnij fenced code blocks żeby nie były przetwarzane jako markdown.
        $codeBlocks = [];
        $md = preg_replace_callback(
            '/```([a-zA-Z0-9_-]*)\n(.*?)\n```/s',
            function ($m) use (&$codeBlocks): string {
                $lang = $m[1] !== '' ? ' class="language-' . htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8') . '"' : '';
                $code = htmlspecialchars($m[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $codeBlocks[] = '<pre><code' . $lang . '>' . $code . '</code></pre>';
                return "\x00CODEBLOCK" . (count($codeBlocks) - 1) . "\x00";
            },
            $md
        ) ?? $md;

        // Escape całą resztę żeby uniknąć HTML/JS injection
        $md = htmlspecialchars($md, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $lines = explode("\n", $md);
        $out   = [];
        $i     = 0;
        $n     = count($lines);

        while ($i < $n) {
            $line = $lines[$i];

            // Horizontal rule
            if (preg_match('/^\s*---+\s*$/', $line)) {
                $out[] = '<hr>';
                $i++;
                continue;
            }

            // Headings (H1-H6)
            if (preg_match('/^(#{1,6})\s+(.+?)\s*#*\s*$/', $line, $m)) {
                $level = strlen($m[1]);
                $text  = self::inline($m[2]);
                $id    = self::slugify(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
                $out[] = '<h' . $level . ' id="' . $id . '">' . $text . '</h' . $level . '>';
                $i++;
                continue;
            }

            // Blockquote (grupowanie kolejnych linii)
            if (preg_match('/^&gt;\s?(.*)$/', $line)) {
                $buf = [];
                while ($i < $n && preg_match('/^&gt;\s?(.*)$/', $lines[$i], $m)) {
                    $buf[] = self::inline($m[1]);
                    $i++;
                }
                $out[] = '<blockquote>' . implode('<br>', $buf) . '</blockquote>';
                continue;
            }

            // Unordered list
            if (preg_match('/^\s*[-*+]\s+(.+)$/', $line)) {
                $items = [];
                while ($i < $n && preg_match('/^\s*[-*+]\s+(.+)$/', $lines[$i], $m)) {
                    $items[] = '<li>' . self::inline($m[1]) . '</li>';
                    $i++;
                }
                $out[] = '<ul>' . implode('', $items) . '</ul>';
                continue;
            }

            // Ordered list
            if (preg_match('/^\s*\d+\.\s+(.+)$/', $line)) {
                $items = [];
                while ($i < $n && preg_match('/^\s*\d+\.\s+(.+)$/', $lines[$i], $m)) {
                    $items[] = '<li>' . self::inline($m[1]) . '</li>';
                    $i++;
                }
                $out[] = '<ol>' . implode('', $items) . '</ol>';
                continue;
            }

            // Code block placeholder — wstaw bezpośrednio
            if (preg_match('/^\x00CODEBLOCK(\d+)\x00$/', $line, $m)) {
                $out[] = $codeBlocks[(int)$m[1]];
                $i++;
                continue;
            }

            // Pusta linia
            if (trim($line) === '') {
                $i++;
                continue;
            }

            // Paragraf — zbierz kolejne nie-puste linie
            $buf = [];
            while ($i < $n && trim($lines[$i]) !== ''
                && !preg_match('/^(#{1,6}\s|&gt;\s|\s*[-*+]\s|\s*\d+\.\s|\s*---+\s*$|\x00CODEBLOCK)/', $lines[$i])
            ) {
                $buf[] = self::inline($lines[$i]);
                $i++;
            }
            if ($buf) {
                $out[] = '<p>' . implode('<br>', $buf) . '</p>';
            }
        }

        $html = implode("\n", $out);

        // Odtwórz placeholdery code blocks (gdyby zostały w paragrafach)
        $html = preg_replace_callback(
            '/\x00CODEBLOCK(\d+)\x00/',
            fn($m) => $codeBlocks[(int)$m[1]] ?? '',
            $html
        ) ?? $html;

        return $html;
    }

    /**
     * Wyciąga TOC (H2 + H3) z surowego markdownu.
     *
     * @return list<array{level:int, text:string, id:string}>
     */
    public static function extractToc(string $markdown): array
    {
        $md   = str_replace(["\r\n", "\r"], "\n", $markdown);
        // Wyrzuć code blocks żeby # w środku kodu nie liczyło się jako heading
        $md   = preg_replace('/```.*?```/s', '', $md) ?? $md;
        $toc  = [];
        foreach (explode("\n", $md) as $line) {
            if (preg_match('/^(#{2,3})\s+(.+?)\s*#*\s*$/', $line, $m)) {
                $level = strlen($m[1]);
                $text  = trim($m[2]);
                $toc[] = [
                    'level' => $level,
                    'text'  => $text,
                    'id'    => self::slugify($text),
                ];
            }
        }
        return $toc;
    }

    /**
     * Inline markdown: code, bold, italic, links.
     * Input MUSI być już htmlspecialchars-escape'owany.
     */
    private static function inline(string $text): string
    {
        // Inline code `code`
        $text = preg_replace_callback(
            '/`([^`]+)`/',
            fn($m) => '<code>' . $m[1] . '</code>',
            $text
        ) ?? $text;

        // Bold **text** lub __text__
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text) ?? $text;

        // Italic *text* lub _text_ (po bold, żeby ** już nie matchowało)
        $text = preg_replace('/(?<![\*\w])\*([^\*\n]+?)\*(?!\*)/', '<em>$1</em>', $text) ?? $text;
        $text = preg_replace('/(?<![_\w])_([^_\n]+?)_(?!_)/', '<em>$1</em>', $text) ?? $text;

        // Links [text](url) — url już escape'owany przez htmlspecialchars
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            function ($m): string {
                $url = $m[2];
                // Tylko http(s), mailto, relative (#anchor, /path, docs)
                if (!preg_match('#^(https?:|mailto:|/|#|[a-zA-Z0-9._-]+(/|$))#', $url)) {
                    return $m[1];
                }
                return '<a href="' . $url . '">' . $m[1] . '</a>';
            },
            $text
        ) ?? $text;

        return $text;
    }

    private static function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        // Polskie znaki → ascii (proste)
        $map  = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z',
        ];
        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-') ?: 'section';
    }
}
