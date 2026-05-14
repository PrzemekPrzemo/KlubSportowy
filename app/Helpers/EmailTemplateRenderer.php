<?php

namespace App\Helpers;

/**
 * Render szablonu e-mail z placeholderami w stylu {{var.path}}.
 *
 * Wspiera kropkowane sciezki: {{member.first_name}}, {{fee.amount}} etc.
 * Niezdefiniowane placeholdery sa zastepowane pustym stringiem.
 *
 * Uzywany przez nowy katalog email_event_catalog. Klasyczne szablony
 * w `email_templates` uzywaja prostszej skladni {first_name} przez
 * EmailTemplateModel::render() — oba formaty wspolistnieja.
 */
class EmailTemplateRenderer
{
    /**
     * Zamienia {{var.path}} placeholdery w stringu uzywajac danych z $context.
     */
    public static function render(string $template, array $context): string
    {
        return preg_replace_callback(
            '/\{\{([a-z_][a-z0-9_.]*)\}\}/i',
            function ($m) use ($context) {
                return self::resolve($m[1], $context) ?? '';
            },
            $template
        ) ?? $template;
    }

    /**
     * Rozwiazuje sciezke "a.b.c" w $context (kropkowana notacja).
     * Zwraca null jesli sciezka nie istnieje lub wartosc nie jest skalar.
     */
    private static function resolve(string $path, array $context): ?string
    {
        $parts = explode('.', $path);
        $cur = $context;
        foreach ($parts as $p) {
            if (is_array($cur) && array_key_exists($p, $cur)) {
                $cur = $cur[$p];
            } else {
                return null;
            }
        }
        if (is_scalar($cur)) {
            return (string)$cur;
        }
        if ($cur === null) {
            return '';
        }
        return null;
    }

    /**
     * Renderuje subject + body w jednym callu. Zwraca ['subject', 'body'].
     */
    public static function renderTemplate(array $template, array $context): array
    {
        return [
            'subject' => self::render((string)($template['subject'] ?? ''), $context),
            'body'    => self::render((string)($template['body'] ?? ''), $context),
        ];
    }
}
