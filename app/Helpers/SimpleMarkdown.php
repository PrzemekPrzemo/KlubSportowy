<?php

namespace App\Helpers;

/**
 * Minimal, dependency-free Markdown -> HTML renderer for legal documents.
 *
 * Supports a deliberately small subset used by the legal documents seed:
 *   - ATX headings:  # .. ######  (H1..H6) with auto-generated ids for TOC
 *   - Horizontal rules: --- on its own line
 *   - Unordered lists: lines starting with "- " or "* "
 *   - Ordered lists:   lines starting with "1. "
 *   - Block quotes:    "> " at line start
 *   - Pipe tables (header row + |---|---| separator)
 *   - Paragraphs (blank-line separated)
 *   - Inline: **bold**, *italic*, `code`, [text](url)
 *   - Auto-linking of bare URLs
 *
 * Every block is HTML-escaped before tokens are replaced — no raw HTML
 * from the source is preserved (safe by default).
 */
class SimpleMarkdown
{
    /** Public entry point. */
    public static function render(string $md): string
    {
        $md   = str_replace(["\r\n", "\r"], "\n", $md);
        $lines = explode("\n", $md);

        $html  = [];
        $i     = 0;
        $count = count($lines);

        while ($i < $count) {
            $line = $lines[$i];
            $trim = trim($line);

            // Skip blank lines.
            if ($trim === '') { $i++; continue; }

            // Horizontal rule.
            if (preg_match('/^-{3,}$/', $trim)) {
                $html[] = '<hr>';
                $i++;
                continue;
            }

            // Heading.
            if (preg_match('/^(#{1,6})\s+(.+)$/', $trim, $m)) {
                $level = strlen($m[1]);
                $text  = $m[2];
                $id    = self::slug($text);
                $html[] = sprintf(
                    '<h%d id="%s">%s</h%d>',
                    $level,
                    htmlspecialchars($id, ENT_QUOTES, 'UTF-8'),
                    self::inline($text),
                    $level
                );
                $i++;
                continue;
            }

            // Table.
            if (str_contains($trim, '|') && isset($lines[$i + 1])
                && preg_match('/^\s*\|?(\s*:?-{3,}:?\s*\|)+\s*:?-{3,}:?\s*\|?\s*$/', trim($lines[$i + 1]))) {
                [$tableHtml, $newI] = self::renderTable($lines, $i);
                $html[] = $tableHtml;
                $i = $newI;
                continue;
            }

            // Blockquote.
            if (str_starts_with($trim, '> ')) {
                [$bqHtml, $newI] = self::renderBlockquote($lines, $i);
                $html[] = $bqHtml;
                $i = $newI;
                continue;
            }

            // Ordered list.
            if (preg_match('/^\d+\.\s/', $trim)) {
                [$listHtml, $newI] = self::renderList($lines, $i, true);
                $html[] = $listHtml;
                $i = $newI;
                continue;
            }

            // Unordered list.
            if (preg_match('/^[-*]\s/', $trim)) {
                [$listHtml, $newI] = self::renderList($lines, $i, false);
                $html[] = $listHtml;
                $i = $newI;
                continue;
            }

            // Paragraph (collect until blank line / new block).
            $para = [];
            while ($i < $count) {
                $cur = $lines[$i];
                $ct  = trim($cur);
                if ($ct === '') break;
                if (preg_match('/^(#{1,6})\s+/', $ct)) break;
                if (preg_match('/^-{3,}$/', $ct)) break;
                if (preg_match('/^[-*]\s/', $ct)) break;
                if (preg_match('/^\d+\.\s/', $ct)) break;
                if (str_starts_with($ct, '> ')) break;
                $para[] = $cur;
                $i++;
            }
            if ($para) {
                $html[] = '<p>' . self::inline(implode("\n", $para)) . '</p>';
            }
        }

        return implode("\n", $html);
    }

    /** Generate a TOC (list of H2 entries) for a markdown document. */
    public static function tableOfContents(string $md): array
    {
        $md   = str_replace(["\r\n", "\r"], "\n", $md);
        $items = [];
        foreach (explode("\n", $md) as $line) {
            if (preg_match('/^##\s+(.+)$/', trim($line), $m)) {
                $text = $m[1];
                $items[] = [
                    'id'   => self::slug($text),
                    'text' => $text,
                ];
            }
        }
        return $items;
    }

    // ------------------------------------------------------------------

    private static function renderList(array $lines, int $i, bool $ordered): array
    {
        $count = count($lines);
        $items = [];
        $tag   = $ordered ? 'ol' : 'ul';
        $regex = $ordered ? '/^\d+\.\s+(.*)$/' : '/^[-*]\s+(.*)$/';

        while ($i < $count) {
            $line = $lines[$i];
            $trim = trim($line);
            if ($trim === '') { $i++; break; }
            if (!preg_match($regex, $trim, $m)) break;
            $items[] = $m[1];
            $i++;
        }

        $html = '<' . $tag . '>';
        foreach ($items as $it) {
            $html .= '<li>' . self::inline($it) . '</li>';
        }
        $html .= '</' . $tag . '>';

        return [$html, $i];
    }

    private static function renderBlockquote(array $lines, int $i): array
    {
        $count = count($lines);
        $buf   = [];
        while ($i < $count) {
            $trim = trim($lines[$i]);
            if (str_starts_with($trim, '> ')) {
                $buf[] = substr($trim, 2);
                $i++;
            } elseif ($trim === '>') {
                $buf[] = '';
                $i++;
            } else {
                break;
            }
        }
        return ['<blockquote>' . self::inline(implode("\n", $buf)) . '</blockquote>', $i];
    }

    private static function renderTable(array $lines, int $i): array
    {
        $count = count($lines);
        // Header row
        $header = self::splitTableRow($lines[$i]);
        $i += 2; // skip separator

        $rows = [];
        while ($i < $count) {
            $trim = trim($lines[$i]);
            if ($trim === '' || !str_contains($trim, '|')) break;
            $rows[] = self::splitTableRow($lines[$i]);
            $i++;
        }

        $html = '<div class="table-responsive"><table class="table table-sm legal-table"><thead><tr>';
        foreach ($header as $h) {
            $html .= '<th>' . self::inline($h) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr>';
            foreach ($r as $c) {
                $html .= '<td>' . self::inline($c) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        return [$html, $i];
    }

    private static function splitTableRow(string $line): array
    {
        $line = trim($line);
        // strip leading/trailing pipes
        $line = preg_replace('/^\||\|$/', '', $line) ?? '';
        $parts = array_map('trim', explode('|', $line));
        return $parts;
    }

    /** Apply inline conversions (bold, italic, code, links). Escapes everything first. */
    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Inline code first to protect contents from other replacements.
        $codes = [];
        $text = preg_replace_callback('/`([^`]+)`/u', function ($m) use (&$codes) {
            $codes[] = '<code>' . $m[1] . '</code>';
            return "\0code" . (count($codes) - 1) . "\0";
        }, $text);

        // Markdown links [text](url) — url is already escaped, validate scheme.
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/u', function ($m) {
            $url = $m[2];
            if (!preg_match('/^(https?:|mailto:|\/|#)/i', $url)) {
                $url = '#';
            }
            return '<a href="' . $url . '" rel="noopener">' . $m[1] . '</a>';
        }, $text);

        // Bold ** **
        $text = preg_replace('/\*\*([^*]+)\*\*/u', '<strong>$1</strong>', $text);
        // Italic * *
        $text = preg_replace('/(?<![\w*])\*([^*\n]+)\*(?!\w)/u', '<em>$1</em>', $text);

        // Auto-link bare URLs (only when not already inside an <a>).
        $text = preg_replace_callback('~(?<![">/=])\b(https?://[^\s<]+)~i', function ($m) {
            $u = $m[1];
            return '<a href="' . $u . '" rel="noopener">' . $u . '</a>';
        }, $text);

        // Bare email addresses (limited).
        $text = preg_replace_callback('~\b([a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,})\b~i', function ($m) {
            return '<a href="mailto:' . $m[1] . '">' . $m[1] . '</a>';
        }, $text);

        // Restore code spans.
        $text = preg_replace_callback("/\0code(\d+)\0/", function ($m) use ($codes) {
            return $codes[(int)$m[1]] ?? '';
        }, $text);

        // Soft line breaks inside paragraphs.
        $text = nl2br($text, false);

        return $text;
    }

    /** ASCII slug for heading anchors. */
    private static function slug(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $map = [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n',
            'ó' => 'o', 'ś' => 's', 'ż' => 'z', 'ź' => 'z',
            'Ą' => 'a', 'Ć' => 'c', 'Ę' => 'e', 'Ł' => 'l', 'Ń' => 'n',
            'Ó' => 'o', 'Ś' => 's', 'Ż' => 'z', 'Ź' => 'z',
            '§' => 'par',
        ];
        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
        return trim($text, '-') ?: 'sec';
    }
}
