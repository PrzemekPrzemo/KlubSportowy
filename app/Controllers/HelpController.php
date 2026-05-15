<?php

namespace App\Controllers;

use App\Helpers\AdminManualManifest;
use App\Helpers\Auth;
use App\Helpers\Markdown;

/**
 * In-app help center — renderuje wybrane dokumenty z katalogu `docs/`
 * jako przeglądalne strony pomocy pod /help i /help/:slug.
 *
 * Dodatkowo udostępnia "manuale" per rola (Trener, Sekretariat, …) —
 * dedykowane wieloekranowe instrukcje z mockupami UI renderowane z
 * lokalnych widoków `app/Views/help/<role>/<slug>.php`.
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
            // Linki do nowych manuali (osobne ścieżki /help/trainer i /help/secretariat).
            'manual-trainer' => [
                'file'  => '',
                'title' => 'Manual Trenera',
                'icon'  => 'bi-stopwatch-fill',
                'desc'  => 'Pełny podręcznik dla trenerów: treningi, obecności, turnieje, prowizje. Ekrany z mockupami.',
                'url'   => 'help/trainer',
            ],
            'manual-secretariat' => [
                'file'  => '',
                'title' => 'Manual Sekretariatu',
                'icon'  => 'bi-folder-check',
                'desc'  => 'Pełny podręcznik dla sekretariatu: członkowie, faktury, korespondencja, compliance.',
                'url'   => 'help/secretariat',
            ],
        ];
    }

    /**
     * Nawigacja manuala Trener — strony w app/Views/help/trainer/*.php.
     *
     * @return array<int, array{slug:string,title:string,group:string,reading_time:string}>
     */
    private function trainerPages(): array
    {
        return [
            // 1. Wprowadzenie
            ['slug' => 'intro',            'title' => 'Czym jest panel trenera',         'group' => 'Wprowadzenie',            'reading_time' => '3 min'],
            ['slug' => 'first-login',      'title' => 'Pierwsze logowanie',              'group' => 'Wprowadzenie',            'reading_time' => '3 min'],
            ['slug' => 'permissions',      'title' => 'Co widzi trener (uprawnienia)',   'group' => 'Wprowadzenie',            'reading_time' => '4 min'],
            // 2. Sekcje i zawodnicy
            ['slug' => 'sections',         'title' => 'Moje sekcje i zawodnicy',         'group' => 'Sekcje i zawodnicy',      'reading_time' => '4 min'],
            ['slug' => 'add-athlete',      'title' => 'Dodanie zawodnika do sekcji',     'group' => 'Sekcje i zawodnicy',      'reading_time' => '3 min'],
            ['slug' => 'athlete-profile',  'title' => 'Profil zawodnika',                'group' => 'Sekcje i zawodnicy',      'reading_time' => '4 min'],
            ['slug' => 'communication',    'title' => 'Komunikacja z zawodnikiem',       'group' => 'Sekcje i zawodnicy',      'reading_time' => '3 min'],
            // 3. Treningi i obecność
            ['slug' => 'schedule',         'title' => 'Harmonogram treningów',           'group' => 'Treningi i obecność',     'reading_time' => '4 min'],
            ['slug' => 'attendance',       'title' => 'Zaznaczanie obecności',           'group' => 'Treningi i obecność',     'reading_time' => '4 min'],
            ['slug' => 'training-notes',   'title' => 'Notatki z treningu',              'group' => 'Treningi i obecność',     'reading_time' => '3 min'],
            ['slug' => 'substitutions',    'title' => 'Substytucje (zastępstwo)',        'group' => 'Treningi i obecność',     'reading_time' => '3 min'],
            ['slug' => 'attendance-report','title' => 'Raport frekwencji (CSV)',          'group' => 'Treningi i obecność',     'reading_time' => '4 min'],
            // 4. Turnieje i wyniki
            ['slug' => 'tournaments',      'title' => 'Nadchodzące turnieje',            'group' => 'Turnieje i wyniki',       'reading_time' => '3 min'],
            ['slug' => 'tournament-entry', 'title' => 'Zgłoszenie zawodników',           'group' => 'Turnieje i wyniki',       'reading_time' => '4 min'],
            ['slug' => 'results',          'title' => 'Wpisywanie wyników',              'group' => 'Turnieje i wyniki',       'reading_time' => '4 min'],
            ['slug' => 'brackets',         'title' => 'Drabinka turniejowa',             'group' => 'Turnieje i wyniki',       'reading_time' => '3 min'],
            ['slug' => 'rankings',         'title' => 'Ranking i statystyki',            'group' => 'Turnieje i wyniki',       'reading_time' => '4 min'],
            // 5. Prowizje
            ['slug' => 'commission-rules', 'title' => 'Jak działa system prowizji',      'group' => 'Prowizje trenera',        'reading_time' => '4 min'],
            ['slug' => 'commission-report','title' => 'Mój raport prowizji',             'group' => 'Prowizje trenera',        'reading_time' => '3 min'],
            ['slug' => 'payouts',          'title' => 'Wypłaty i rozliczenia',           'group' => 'Prowizje trenera',        'reading_time' => '4 min'],
        ];
    }

    /**
     * Nawigacja manuala Sekretariat.
     *
     * @return array<int, array{slug:string,title:string,group:string,reading_time:string}>
     */
    private function secretariatPages(): array
    {
        return [
            // 1. Wprowadzenie
            ['slug' => 'intro',             'title' => 'Rola sekretariatu',              'group' => 'Wprowadzenie',          'reading_time' => '3 min'],
            ['slug' => 'dashboard',         'title' => 'Dashboard sekretariatu',         'group' => 'Wprowadzenie',          'reading_time' => '3 min'],
            // 2. Członkowie
            ['slug' => 'member-register',   'title' => 'Rejestracja nowego członka',     'group' => 'Członkowie',            'reading_time' => '5 min'],
            ['slug' => 'member-update',     'title' => 'Aktualizacja danych członka',    'group' => 'Członkowie',            'reading_time' => '3 min'],
            ['slug' => 'member-docs',       'title' => 'Dokumenty członka',              'group' => 'Członkowie',            'reading_time' => '4 min'],
            ['slug' => 'member-export',     'title' => 'Eksport listy członków',         'group' => 'Członkowie',            'reading_time' => '3 min'],
            // 3. Finanse
            ['slug' => 'invoice-generate',  'title' => 'Generowanie faktur',             'group' => 'Składki i finanse',     'reading_time' => '5 min'],
            ['slug' => 'payment-status',    'title' => 'Status płatności',               'group' => 'Składki i finanse',     'reading_time' => '4 min'],
            ['slug' => 'reminders',         'title' => 'Przypomnienia o zaległościach',  'group' => 'Składki i finanse',     'reading_time' => '4 min'],
            ['slug' => 'invoice-correct',   'title' => 'Korekty faktur',                 'group' => 'Składki i finanse',     'reading_time' => '4 min'],
            // 4. Korespondencja
            ['slug' => 'email-campaigns',   'title' => 'Email kampanie z szablonów',     'group' => 'Korespondencja',        'reading_time' => '4 min'],
            ['slug' => 'sms-reminders',     'title' => 'SMS przypomnienia',              'group' => 'Korespondencja',        'reading_time' => '3 min'],
            ['slug' => 'print-certs',       'title' => 'Drukowanie zaświadczeń (PDF)',   'group' => 'Korespondencja',        'reading_time' => '3 min'],
            // 5. Compliance
            ['slug' => 'medical-tracking',  'title' => 'Badania medyczne — ważność',     'group' => 'Dokumenty i compliance','reading_time' => '4 min'],
            ['slug' => 'gdpr-consents',     'title' => 'Zgody RODO',                     'group' => 'Dokumenty i compliance','reading_time' => '4 min'],
            ['slug' => 'membership-certs',  'title' => 'Zaświadczenia o przynależności', 'group' => 'Dokumenty i compliance','reading_time' => '3 min'],
        ];
    }

    public function index(): void
    {
        $sections = [];
        foreach ($this->sections() as $slug => $meta) {
            // Pozycje typu "manual" (klucz 'url') nie mają pliku — uznajemy dostępne.
            $available = isset($meta['url']) ? true : is_readable(ROOT_PATH . '/docs/' . $meta['file']);
            $sections[$slug] = $meta + ['available' => $available];
        }

        if (!Auth::id()) {
            $this->view->setLayout('landing');
        }

        $this->render('help/index', [
            'title'    => 'Pomoc — ClubDesk',
            'sections' => $sections,
        ]);
    }

    /**
     * Index podręcznika administratora — `/help/admin`.
     * Slug 'admin' jest rozpoznawany przez page() i przekierowywany tutaj.
     */
    private function adminManualIndex(): void
    {
        $categories = AdminManualManifest::categories();
        if (!Auth::id()) {
            $this->view->setLayout('landing');
        }
        $this->render('help/manual_index', [
            'title'      => 'Podręcznik administratora — ClubDesk',
            'categories' => $categories,
        ]);
    }

    /**
     * Wyświetla pojedynczą stronę podręcznika administratora.
     * Slug format: `admin-{categoryKey}-{pageKey}`.
     */
    private function adminManualPage(string $slug): void
    {
        $pages = AdminManualManifest::flatPages();
        if (!isset($pages[$slug])) {
            http_response_code(404);
            if (!Auth::id()) {
                $this->view->setLayout('landing');
            }
            $this->render('help/not_found', [
                'title'    => 'Strona pomocy nie znaleziona',
                'sections' => $this->sections(),
            ]);
            return;
        }

        $page = $pages[$slug];
        [$prevSlug, $nextSlug] = AdminManualManifest::neighbors($slug);
        $prev = $prevSlug ? ['slug' => $prevSlug, 'title' => $pages[$prevSlug]['title']] : null;
        $next = $nextSlug ? ['slug' => $nextSlug, 'title' => $pages[$nextSlug]['title']] : null;

        if (!Auth::id()) {
            $this->view->setLayout('landing');
        }

        $this->render('help/manual_page', [
            'title'         => $page['title'] . ' — Podręcznik administratora',
            'pageMeta'      => $page,
            'currentSlug'   => $slug,
            'categories'    => AdminManualManifest::categories(),
            'prev'          => $prev,
            'next'          => $next,
            'innerView'     => $page['view'],
        ]);
    }

    public function page(string $slug = ''): void
    {
        // Slug `admin` → index podręcznika administratora klubu.
        if ($slug === 'admin') {
            $this->adminManualIndex();
            return;
        }
        // Slugi w schemacie `admin-...` to strony podręcznika administratora.
        if (str_starts_with($slug, 'admin-')) {
            $this->adminManualPage($slug);
            return;
        }

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

        // Wpisy typu "manual" (linki do dedykowanych podręczników) — przekieruj.
        if (isset($meta['url'])) {
            header('Location: ' . url($meta['url']));
            exit;
        }

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

    // ──────────────────────────────────────────────────────────────────
    // Manual Trenera
    // ──────────────────────────────────────────────────────────────────

    public function trainerIndex(): void
    {
        if (!Auth::id()) {
            $this->view->setLayout('landing');
        }
        $this->render('help/trainer/index', [
            'title'        => 'Manual Trenera — Pomoc',
            'manualPages'  => $this->trainerPages(),
        ]);
    }

    public function trainerPage(string $slug = ''): void
    {
        $pages = $this->trainerPages();
        $page  = null;
        $idx   = -1;
        foreach ($pages as $i => $p) {
            if ($p['slug'] === $slug) { $page = $p; $idx = $i; break; }
        }
        if ($page === null) {
            http_response_code(404);
            if (!Auth::id()) {
                $this->view->setLayout('landing');
            }
            $this->render('help/not_found', [
                'title'    => 'Strona manuala nie znaleziona',
                'sections' => $this->sections(),
            ]);
            return;
        }

        $viewName = 'help/trainer/' . $slug;
        $viewFile = ROOT_PATH . '/app/Views/' . $viewName . '.php';
        if (!is_file($viewFile)) {
            http_response_code(404);
            if (!Auth::id()) {
                $this->view->setLayout('landing');
            }
            $this->render('help/not_found', [
                'title'    => 'Strona manuala nie znaleziona',
                'sections' => $this->sections(),
            ]);
            return;
        }

        $prev = $idx > 0 ? $pages[$idx - 1] : null;
        $next = $idx < count($pages) - 1 ? $pages[$idx + 1] : null;

        if (!Auth::id()) {
            $this->view->setLayout('landing');
        }

        $this->render($viewName, [
            'title'      => $page['title'] . ' — Manual Trenera',
            'manualNav'  => [
                'base'    => 'help/trainer',
                'current' => $slug,
                'items'   => $pages,
            ],
            'page' => [
                'title'        => $page['title'],
                'category'     => 'Trener',
                'last_updated' => '2026-05-15',
                'reading_time' => $page['reading_time'],
            ],
            'prev' => $prev,
            'next' => $next,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // Manual Sekretariatu
    // ──────────────────────────────────────────────────────────────────

    public function secretariatIndex(): void
    {
        if (!Auth::id()) {
            $this->view->setLayout('landing');
        }
        $this->render('help/secretariat/index', [
            'title'        => 'Manual Sekretariatu — Pomoc',
            'manualPages'  => $this->secretariatPages(),
        ]);
    }

    public function secretariatPage(string $slug = ''): void
    {
        $pages = $this->secretariatPages();
        $page  = null;
        $idx   = -1;
        foreach ($pages as $i => $p) {
            if ($p['slug'] === $slug) { $page = $p; $idx = $i; break; }
        }
        if ($page === null) {
            http_response_code(404);
            if (!Auth::id()) {
                $this->view->setLayout('landing');
            }
            $this->render('help/not_found', [
                'title'    => 'Strona manuala nie znaleziona',
                'sections' => $this->sections(),
            ]);
            return;
        }

        $viewName = 'help/secretariat/' . $slug;
        $viewFile = ROOT_PATH . '/app/Views/' . $viewName . '.php';
        if (!is_file($viewFile)) {
            http_response_code(404);
            if (!Auth::id()) {
                $this->view->setLayout('landing');
            }
            $this->render('help/not_found', [
                'title'    => 'Strona manuala nie znaleziona',
                'sections' => $this->sections(),
            ]);
            return;
        }

        $prev = $idx > 0 ? $pages[$idx - 1] : null;
        $next = $idx < count($pages) - 1 ? $pages[$idx + 1] : null;

        if (!Auth::id()) {
            $this->view->setLayout('landing');
        }

        $this->render($viewName, [
            'title'      => $page['title'] . ' — Manual Sekretariatu',
            'manualNav'  => [
                'base'    => 'help/secretariat',
                'current' => $slug,
                'items'   => $pages,
            ],
            'page' => [
                'title'        => $page['title'],
                'category'     => 'Sekretariat',
                'last_updated' => '2026-05-15',
                'reading_time' => $page['reading_time'],
            ],
            'prev' => $prev,
            'next' => $next,
        ]);
    }
}
