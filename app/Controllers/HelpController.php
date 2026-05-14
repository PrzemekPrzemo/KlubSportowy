<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Markdown;

/**
 * In-app help center — renderuje wybrane dokumenty z katalogu `docs/`
 * jako przeglądalne strony pomocy pod /help i /help/:slug.
 *
 * Bezpieczeństwo: slugi są sztywno whitelistowane — żaden user-input nie
 * trafia bezpośrednio do `file_get_contents`. Renderer markdown to prosty
 * inline parser (TODO: zamienić na Parsedown gdy zostanie dodany do composer).
 */
class HelpController extends BaseController
{
    /**
     * Whitelist slug → ścieżka pliku markdown w docs/.
     * Pliki internal (outreach-plan, ROADMAP) celowo nie są tu uwzględnione.
     *
     * @return array<string, array{file: string, title: string, icon: string, desc: string}>
     */
    private function sections(): array
    {
        return [
            'getting-started' => [
                'file'  => 'getting-started.md',
                'title' => 'Pierwsze kroki',
                'icon'  => 'bi-rocket-takeoff',
                'desc'  => 'Walkthrough dla nowego administratora klubu — od loginu po pierwsze składki.',
            ],
            'admin' => [
                'file'  => 'admin-guide.md',
                'title' => 'Przewodnik administratora',
                'icon'  => 'bi-shield-check',
                'desc'  => 'Konfiguracja klubu, role, uprawnienia, migrations runner i zaawansowane ustawienia.',
            ],
            'api' => [
                'file'  => 'api-reference.md',
                'title' => 'Dokumentacja API',
                'icon'  => 'bi-braces',
                'desc'  => 'REST API ClubDesk — endpointy, autoryzacja, przykłady requestów.',
            ],
            // 'installation' — celowo wyłączone w trybie SaaS (instrukcje deploy
            // nie powinny być widoczne dla klientów portalu klubdesk.pl).
            // Plik docs/plesk-installation.md istnieje w repo dla self-hosting.
            'pricing' => [
                'file'  => 'CLUBDESK_PL_PRICING.md',
                'title' => 'Cennik i plany',
                'icon'  => 'bi-currency-exchange',
                'desc'  => 'Przegląd dostępnych planów subskrypcyjnych i ich limitów.',
            ],
            'guide-common' => [
                'file'  => 'guides/common.md',
                'title' => 'Konto i podstawy',
                'icon'  => 'bi-person-circle',
                'desc'  => 'Logowanie, 2FA, dark mode, język, instalacja PWA — wspólne dla każdej roli.',
            ],
            'guide-zarzad' => [
                'file'  => 'guides/zarzad.md',
                'title' => 'Przewodnik: zarząd klubu',
                'icon'  => 'bi-shield-fill-check',
                'desc'  => 'Pełna konfiguracja klubu — branding, składki, bramki, federacje, integracje.',
            ],
            'guide-trener' => [
                'file'  => 'guides/trener.md',
                'title' => 'Przewodnik: trener',
                'icon'  => 'bi-stopwatch',
                'desc'  => 'Treningi, zawodnicy, składy, statystyki, wydarzenia, komunikacja.',
            ],
            'guide-instruktor' => [
                'file'  => 'guides/instruktor.md',
                'title' => 'Przewodnik: instruktor',
                'icon'  => 'bi-clipboard-check',
                'desc'  => 'Asysta przy zajęciach — obecność, lista zawodników, komunikacja.',
            ],
            'guide-sedzia' => [
                'file'  => 'guides/sedzia.md',
                'title' => 'Przewodnik: sędzia',
                'icon'  => 'bi-flag-fill',
                'desc'  => 'Wyniki meczów i turniejów, drabinki, protokoły, live updates.',
            ],
            'guide-ksiegowy' => [
                'file'  => 'guides/ksiegowy.md',
                'title' => 'Przewodnik: księgowy',
                'icon'  => 'bi-cash-coin',
                'desc'  => 'Składki, płatności, faktury, raporty, prowizje trenerów.',
            ],
            'guide-lekarz' => [
                'file'  => 'guides/lekarz.md',
                'title' => 'Przewodnik: lekarz',
                'icon'  => 'bi-heart-pulse',
                'desc'  => 'Karty medyczne, badania okresowe, kontuzje, zgodność WADA.',
            ],
            'guide-czlonek' => [
                'file'  => 'guides/czlonek.md',
                'title' => 'Przewodnik: członek (portal)',
                'icon'  => 'bi-person-badge',
                'desc'  => 'Mój profil, składki, kalendarz, wyniki, dokumenty, PWA.',
            ],
        ];
    }

    public function index(): void
    {
        $sections = [];
        foreach ($this->sections() as $slug => $meta) {
            $path = ROOT_PATH . '/docs/' . $meta['file'];
            $sections[$slug] = $meta + ['available' => is_readable($path)];
        }

        // Layout: zalogowani widzą help w głównym layoucie, anonimowi w landing.
        if (!Auth::id()) {
            $this->view->setLayout('landing');
        }

        $this->render('help/index', [
            'title'    => 'Pomoc — ClubDesk',
            'sections' => $sections,
        ]);
    }

    public function page(string $slug = ''): void
    {
        $sections = $this->sections();

        if ($slug === '' || !isset($sections[$slug])) {
            http_response_code(404);
            if (!Auth::id()) {
                $this->view->setLayout('landing');
            }
            $this->render('help/not_found', [
                'title'    => 'Strona pomocy nie znaleziona',
                'sections' => $sections,
            ]);
            return;
        }

        $meta = $sections[$slug];
        $path = ROOT_PATH . '/docs/' . $meta['file'];

        // Defense in depth — realpath nie może wyjść poza ROOT/docs.
        $real     = realpath($path);
        $docsRoot = realpath(ROOT_PATH . '/docs');
        if ($real === false || $docsRoot === false || !str_starts_with($real, $docsRoot . DIRECTORY_SEPARATOR)) {
            http_response_code(404);
            if (!Auth::id()) {
                $this->view->setLayout('landing');
            }
            $this->render('help/not_found', [
                'title'    => 'Strona pomocy nie znaleziona',
                'sections' => $sections,
            ]);
            return;
        }

        $raw = @file_get_contents($real);
        if ($raw === false) {
            $raw = '# Treść niedostępna' . "\n\nNie udało się wczytać dokumentu.";
        }

        $html = Markdown::render($raw);
        $toc  = Markdown::extractToc($raw);

        if (!Auth::id()) {
            $this->view->setLayout('landing');
        }

        $this->render('help/page', [
            'title'       => $meta['title'] . ' — Pomoc',
            'slug'        => $slug,
            'pageTitle'   => $meta['title'],
            'icon'        => $meta['icon'],
            'html'        => $html,
            'toc'         => $toc,
            'sections'    => $sections,
            'currentSlug' => $slug,
        ]);
    }
}
