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

    /**
     * Pełne manuale dla nietechnicznego użytkownika (Zawodnik, Rodzic).
     * Każdy manual ma własny katalog widoków PHP w app/Views/help/manual/{key}/
     * i własną listę stron whitelistowanych po slugu.
     *
     * @return array<string, array{label:string, baseUrl:string, dir:string, icon:string, desc:string, intro:string, pages: array<int, array{slug:string,title:string,icon?:string,group?:string,desc?:string}>}>
     */
    private function manuals(): array
    {
        return [
            'member' => [
                'label'   => 'Portal zawodnika',
                'baseUrl' => 'help/member',
                'dir'     => 'help/manual/member',
                'icon'    => 'bi-person-arms-up',
                'desc'    => 'Kompletny przewodnik dla zawodnika — od pierwszego logowania po RODO i odznaki.',
                'intro'   => 'Wszystko, czego potrzebujesz, żeby korzystać z portalu zawodnika ClubDesk.',
                'pages'   => [
                    ['slug' => 'welcome',         'title' => 'Co to jest portal zawodnika', 'icon' => 'bi-stars',              'group' => 'Pierwsze kroki', 'desc' => 'Twoja osobista przestrzeń w klubie — krótkie wprowadzenie.'],
                    ['slug' => 'login',           'title' => 'Logowanie i hasło',           'icon' => 'bi-box-arrow-in-right', 'group' => 'Pierwsze kroki', 'desc' => 'Jak się zalogować, jak odzyskać hasło, 2FA.'],
                    ['slug' => 'pwa',             'title' => 'Instalacja jako aplikacja',   'icon' => 'bi-download',           'group' => 'Pierwsze kroki', 'desc' => 'Dodaj ClubDesk do ekranu telefonu jak zwykłą aplikację.'],
                    ['slug' => 'profile',         'title' => 'Moje dane osobowe',           'icon' => 'bi-person',             'group' => 'Mój profil',     'desc' => 'Co jest w profilu, jak edytować, kto to widzi.'],
                    ['slug' => 'profile-photo',   'title' => 'Zdjęcie i dokumenty',         'icon' => 'bi-camera',             'group' => 'Mój profil',     'desc' => 'Zdjęcie profilowe i wgrywanie dokumentów.'],
                    ['slug' => 'member-card',     'title' => 'Karta członkowska',           'icon' => 'bi-person-badge',       'group' => 'Mój profil',     'desc' => 'Twoja wirtualna legitymacja klubowa z kodem QR.'],
                    ['slug' => 'schedule',        'title' => 'Mój kalendarz',               'icon' => 'bi-calendar3',          'group' => 'Treningi',       'desc' => 'Najbliższe treningi, mecze, wydarzenia.'],
                    ['slug' => 'attendance',      'title' => 'Moja frekwencja',             'icon' => 'bi-list-check',         'group' => 'Treningi',       'desc' => 'Statystyki obecności i historia treningów.'],
                    ['slug' => 'training-signup', 'title' => 'Zapisy na trening',           'icon' => 'bi-pencil-square',      'group' => 'Treningi',       'desc' => 'Jak zapisać się i wypisać z zajęć.'],
                    ['slug' => 'fees',            'title' => 'Status moich składek',        'icon' => 'bi-cash-stack',         'group' => 'Płatności',      'desc' => 'Co masz do zapłaty, terminy, kolory.'],
                    ['slug' => 'fees-online',     'title' => 'Płatność online',             'icon' => 'bi-credit-card',        'group' => 'Płatności',      'desc' => 'Karta, BLIK, Apple/Google Pay — krok po kroku.'],
                    ['slug' => 'fees-history',    'title' => 'Historia płatności i faktury','icon' => 'bi-receipt',            'group' => 'Płatności',      'desc' => 'Pobieranie faktur PDF i potwierdzeń.'],
                    ['slug' => 'announcements',   'title' => 'Ogłoszenia i powiadomienia',  'icon' => 'bi-megaphone',          'group' => 'Komunikacja',    'desc' => 'Ogłoszenia klubowe + konfiguracja powiadomień.'],
                    ['slug' => 'messages',        'title' => 'Wiadomości od trenera',       'icon' => 'bi-chat-dots',          'group' => 'Komunikacja',    'desc' => 'Bezpośredni kontakt z trenerem i klubem.'],
                    ['slug' => 'results',         'title' => 'Wyniki i turnieje',           'icon' => 'bi-trophy',             'group' => 'Wyniki',         'desc' => 'Twoja historia startów i miejsca w klasyfikacjach.'],
                    ['slug' => 'achievements',    'title' => 'Statystyki i odznaki',        'icon' => 'bi-award',              'group' => 'Wyniki',         'desc' => 'Odznaki, osiągnięcia i kolekcja achievementów.'],
                    ['slug' => 'gdpr-consents',   'title' => 'Moje zgody (RODO)',           'icon' => 'bi-shield-check',       'group' => 'Prywatność',     'desc' => 'Lista aktywnych zgód i jak je zmienić.'],
                    ['slug' => 'gdpr-rights',     'title' => 'Eksport i usunięcie danych',  'icon' => 'bi-file-earmark-zip',   'group' => 'Prywatność',     'desc' => 'Twoje prawa z art. 17 i 20 RODO + ekran portalu.'],
                ],
            ],
            'parent' => [
                'label'   => 'Portal rodzica',
                'baseUrl' => 'help/parent',
                'dir'     => 'help/manual/parent',
                'icon'    => 'bi-people',
                'desc'    => 'Przewodnik dla rodzica / opiekuna prawnego — wszystko o opiece nad podopiecznym w klubie.',
                'intro'   => 'Jak korzystać z portalu opiekuna, płacić składki za dziecko i zarządzać zgodami RODO.',
                'pages'   => [
                    ['slug' => 'welcome',       'title' => 'Czym jest portal opiekuna',   'icon' => 'bi-heart',                'group' => 'Wprowadzenie',  'desc' => 'Dlaczego rodzice mają osobne konto i co tam zobaczysz.'],
                    ['slug' => 'invite',        'title' => 'Jak otrzymać dostęp',         'icon' => 'bi-envelope-paper',       'group' => 'Wprowadzenie',  'desc' => 'Zaproszenie od klubu i pierwsze logowanie.'],
                    ['slug' => 'wards',         'title' => 'Lista moich podopiecznych',   'icon' => 'bi-people-fill',          'group' => 'Moje dziecko',  'desc' => 'Jeden ekran dla wszystkich dzieci w klubie.'],
                    ['slug' => 'ward-profile',  'title' => 'Profil dziecka',              'icon' => 'bi-person-vcard',         'group' => 'Moje dziecko',  'desc' => 'Co rodzic widzi w karcie zawodnika.'],
                    ['slug' => 'ward-consents', 'title' => 'Zgody w imieniu dziecka',     'icon' => 'bi-shield-check',         'group' => 'Moje dziecko',  'desc' => 'Wyrażanie zgód za osobę niepełnoletnią.'],
                    ['slug' => 'fees',          'title' => 'Płatność składek za dziecko', 'icon' => 'bi-credit-card-2-front',  'group' => 'Składki',       'desc' => 'Karta, BLIK, autopłatność.'],
                    ['slug' => 'fees-history',  'title' => 'Historia i faktury',          'icon' => 'bi-receipt',              'group' => 'Składki',       'desc' => 'Pobieranie potwierdzeń i faktur PDF.'],
                    ['slug' => 'attendance',    'title' => 'Obecność dziecka',            'icon' => 'bi-list-check',           'group' => 'Aktywności',    'desc' => 'Statystyki frekwencji podopiecznego.'],
                    ['slug' => 'achievements',  'title' => 'Wyniki i osiągnięcia',        'icon' => 'bi-trophy',               'group' => 'Aktywności',    'desc' => 'Co osiągnęło Twoje dziecko w klubie.'],
                    ['slug' => 'gdpr-minor',    'title' => 'RODO za niepełnoletniego',    'icon' => 'bi-shield-lock',          'group' => 'Prywatność',    'desc' => 'Specyfika art. 8 RODO + cofnięcie zgody.'],
                ],
            ],
        ];
    }

    /** Public entry: /help/member */
    public function memberIndex(): void { $this->manualIndex('member'); }

    /** Public entry: /help/member/:slug */
    public function memberPage(string $slug = ''): void { $this->manualPage('member', $slug); }

    /** Public entry: /help/parent */
    public function parentIndex(): void { $this->manualIndex('parent'); }

    /** Public entry: /help/parent/:slug */
    public function parentPage(string $slug = ''): void { $this->manualPage('parent', $slug); }

    /**
     * Index strony manualu Zawodnika/Rodzica — hero + kafelki z grupami.
     */
    private function manualIndex(string $key): void
    {
        $manuals = $this->manuals();
        if (!isset($manuals[$key])) {
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

        if (!Auth::id()) {
            $this->view->setLayout('landing');
        }

        $manual = $manuals[$key];
        $groups = [];
        foreach ($manual['pages'] as $page) {
            $g = (string)($page['group'] ?? 'Inne');
            $groups[$g][] = $page;
        }

        $this->render('help/manual/index', [
            'title'     => $manual['label'] . ' — Pomoc',
            'manual'    => $manual,
            'manualKey' => $key,
            'groups'    => $groups,
        ]);
    }

    /**
     * Pojedyncza strona manualu — slug w pełni whitelistowany przez manifest.
     */
    private function manualPage(string $key, string $slug): void
    {
        $manuals = $this->manuals();
        if (!isset($manuals[$key])) {
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

        $manual = $manuals[$key];
        $page   = null;
        foreach ($manual['pages'] as $p) {
            if ($p['slug'] === $slug) { $page = $p; break; }
        }
        if ($page === null) {
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

        $manualFile = ROOT_PATH . '/app/Views/' . $manual['dir'] . '/' . $page['slug'] . '.php';
        // Defense in depth — realpath nie może wyjść poza katalog manuali.
        $real    = realpath($manualFile);
        $manRoot = realpath(ROOT_PATH . '/app/Views/' . $manual['dir']);
        if ($real === false || $manRoot === false || !str_starts_with($real, $manRoot . DIRECTORY_SEPARATOR)) {
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

        if (!Auth::id()) {
            $this->view->setLayout('landing');
        }

        $pageMeta = $page + [
            'last_updated' => '2026-05-15',
            'reading_time' => '3 min',
            'category'     => $manual['label'],
        ];

        $this->render('help/manual/page', [
            'title'         => $page['title'] . ' — ' . $manual['label'],
            'manualFile'    => $real,
            'manualPages'   => $manual['pages'],
            'currentSlug'   => $page['slug'],
            'manualBaseUrl' => $manual['baseUrl'],
            'manualLabel'   => $manual['label'],
            'page'          => $pageMeta,
        ]);
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
