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
            'ksef' => [
                'file'  => 'ksef.md',
                'title' => 'KSeF — integracja',
                'icon'  => 'bi-receipt-cutoff',
                'desc'  => 'Krajowy System e-Faktur (MF) — jak skonfigurować integrację dla klubu (Phase 1: foundation).',
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
            'api-v2' => [
                'file'  => '',
                'title' => 'API v2 dla integracji',
                'icon'  => 'bi-braces',
                'desc'  => 'Public REST API v2 + webhooki — dokumentacja dla developerów integrujących systemy zewnetrzne.',
                'url'   => 'help/api/v2',
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

    /**
     * `/help/api/v2` — dokumentacja Public API v2 dla integracji zewnetrznych.
     * Statyczna strona; bez auth dostepna.
     */
    public function apiV2(): void
    {
        if (!Auth::id()) {
            $this->view->setLayout('landing');
        }
        $this->render('help/api/v2', [
            'title' => 'Public API v2 — dokumentacja dla integracji',
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
