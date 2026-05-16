<?php

namespace App\Helpers;

/**
 * Manifest podręcznika administratora klubu.
 *
 * Każda strona to widok PHP renderowany przez HelpController::manualPage().
 * Slug w URL to `admin-<category>-<page>` (router nie obsługuje znaku `/`
 * w parametrze :slug, więc używamy myślników).
 *
 * Struktura zwracanej tablicy:
 *   categories[<categoryKey>] = [
 *     'title' => string,
 *     'icon'  => string,
 *     'desc'  => string,
 *     'pages' => [
 *        '<pageKey>' => [
 *           'title'        => string,
 *           'view'         => string (ścieżka widoku bez .php względem app/Views/)
 *           'reading_time' => string,
 *           'last_updated' => string,
 *        ],
 *        ...
 *     ],
 *   ]
 */
final class AdminManualManifest
{
    /** @return array<string,array{title:string,icon:string,desc:string,pages:array<string,array{title:string,view:string,reading_time:string,last_updated:string}>}> */
    public static function categories(): array
    {
        $lu = '2026-05-15';
        return [
            'getting-started' => [
                'title' => 'Pierwsze kroki',
                'icon'  => 'bi-rocket-takeoff',
                'desc'  => 'Konfiguracja konta administratora, brand klubu, plan subskrypcji i pierwsze sekcje sportowe.',
                'pages' => [
                    'intro'         => ['title' => 'Wprowadzenie do ClubDesk', 'view' => 'help/manual/getting-started/intro', 'reading_time' => '4 min', 'last_updated' => $lu],
                    'first-login'   => ['title' => 'Pierwsze logowanie i ustawienia konta', 'view' => 'help/manual/getting-started/first-login', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'branding'      => ['title' => 'Brand klubu — logo, kolory, motto', 'view' => 'help/manual/getting-started/branding', 'reading_time' => '6 min', 'last_updated' => $lu],
                    'subscription'  => ['title' => 'Plan subskrypcji i płatności', 'view' => 'help/manual/getting-started/subscription', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'first-sports'  => ['title' => 'Dodanie pierwszych sekcji sportowych', 'view' => 'help/manual/getting-started/first-sports', 'reading_time' => '5 min', 'last_updated' => $lu],
                ],
            ],
            'members' => [
                'title' => 'Członkowie',
                'icon'  => 'bi-people',
                'desc'  => 'Ewidencja członków, import danych, dokumenty, masowe operacje i obsługa RODO.',
                'pages' => [
                    'list'           => ['title' => 'Lista członków, filtry i wyszukiwanie', 'view' => 'help/manual/members/list', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'add'            => ['title' => 'Dodanie nowego członka manualnie', 'view' => 'help/manual/members/add', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'import'         => ['title' => 'Import członków z CSV/Excel', 'view' => 'help/manual/members/import', 'reading_time' => '7 min', 'last_updated' => $lu],
                    'athletes-vs-members' => ['title' => 'Zawodnicy vs członkowie — różnice', 'view' => 'help/manual/members/athletes-vs-members', 'reading_time' => '4 min', 'last_updated' => $lu],
                    'documents'      => ['title' => 'Dokumenty członka — umowy, RODO, oświadczenia', 'view' => 'help/manual/members/documents', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'bulk-ops'       => ['title' => 'Operacje grupowe — eksport, mass email/SMS', 'view' => 'help/manual/members/bulk-ops', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'anonymize'      => ['title' => 'Anonimizacja danych (RODO art. 17)', 'view' => 'help/manual/members/anonymize', 'reading_time' => '4 min', 'last_updated' => $lu],
                    'export-gdpr'    => ['title' => 'Eksport danych członka (RODO art. 20)', 'view' => 'help/manual/members/export-gdpr', 'reading_time' => '4 min', 'last_updated' => $lu],
                ],
            ],
            'sport' => [
                'title' => 'Sport — treningi, turnieje, obecność',
                'icon'  => 'bi-trophy',
                'desc'  => 'Sekcje sportowe, plan zajęć, obecność, turnieje, drabinki, wyniki i ranking.',
                'pages' => [
                    'sections'       => ['title' => 'Sekcje sportowe i trenerzy', 'view' => 'help/manual/sport/sections', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'schedule'       => ['title' => 'Plan zajęć (treningi cykliczne)', 'view' => 'help/manual/sport/schedule', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'attendance'     => ['title' => 'Zaznaczanie obecności', 'view' => 'help/manual/sport/attendance', 'reading_time' => '6 min', 'last_updated' => $lu],
                    'tournaments'    => ['title' => 'Turnieje — harmonogram i uczestnicy', 'view' => 'help/manual/sport/tournaments', 'reading_time' => '6 min', 'last_updated' => $lu],
                    'brackets'       => ['title' => 'Drabinki turniejowe', 'view' => 'help/manual/sport/brackets', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'results'        => ['title' => 'Wpisywanie wyników i auto-recalc', 'view' => 'help/manual/sport/results', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'protocol-pdf'   => ['title' => 'Protokół turniejowy PDF', 'view' => 'help/manual/sport/protocol-pdf', 'reading_time' => '4 min', 'last_updated' => $lu],
                    'ranking'        => ['title' => 'Cross-sport ranking członka', 'view' => 'help/manual/sport/ranking', 'reading_time' => '4 min', 'last_updated' => $lu],
                ],
            ],
            'finance' => [
                'title' => 'Finanse',
                'icon'  => 'bi-cash-coin',
                'desc'  => 'Składki, faktury, płatności online, prowizje, JPK i polecenia.',
                'pages' => [
                    'fees'            => ['title' => 'Składki członkowskie — konfiguracja stawek', 'view' => 'help/manual/finance/fees', 'reading_time' => '6 min', 'last_updated' => $lu],
                    'invoices'        => ['title' => 'Generowanie faktur i masowe naliczenia', 'view' => 'help/manual/finance/invoices', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'payments-online' => ['title' => 'Płatności online — Stripe, P24, PayU, Tpay', 'view' => 'help/manual/finance/payments-online', 'reading_time' => '7 min', 'last_updated' => $lu],
                    'recurring'       => ['title' => 'Płatności cykliczne (subskrypcja zawodnika)', 'view' => 'help/manual/finance/recurring', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'commissions'     => ['title' => 'Prowizje trenerów — reguły i raport', 'view' => 'help/manual/finance/commissions', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'jpk'             => ['title' => 'Eksport JPK_FA dla księgowości', 'view' => 'help/manual/finance/jpk', 'reading_time' => '4 min', 'last_updated' => $lu],
                    'bulk-invoices'   => ['title' => 'Faktury masowe (bulk_invoices)', 'view' => 'help/manual/finance/bulk-invoices', 'reading_time' => '4 min', 'last_updated' => $lu],
                    'invoices-club'   => ['title' => 'Faktury sprzedaży klubu (KSeF Phase 2)', 'view' => 'help/manual/finance/invoices-club', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'referral'        => ['title' => 'Polecenia i rabaty', 'view' => 'help/manual/finance/referral', 'reading_time' => '4 min', 'last_updated' => $lu],
                ],
            ],
            'communication' => [
                'title' => 'Komunikacja',
                'icon'  => 'bi-chat-dots',
                'desc'  => 'Ogłoszenia, kampanie email/SMS, czat i powiadomienia push.',
                'pages' => [
                    'announcements' => ['title' => 'Ogłoszenia — priorytety i targetowanie', 'view' => 'help/manual/communication/announcements', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'email'         => ['title' => 'Kampanie email i szablony', 'view' => 'help/manual/communication/email', 'reading_time' => '6 min', 'last_updated' => $lu],
                    'sms'           => ['title' => 'Kampanie SMS', 'view' => 'help/manual/communication/sms', 'reading_time' => '4 min', 'last_updated' => $lu],
                ],
            ],
            'compliance' => [
                'title' => 'Compliance i dokumenty',
                'icon'  => 'bi-shield-check',
                'desc'  => 'Badania medyczne, certyfikacje, zgody RODO, WADA, sprzęt.',
                'pages' => [
                    'medical'       => ['title' => 'Badania medyczne i alerty', 'view' => 'help/manual/compliance/medical', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'certifications'=> ['title' => 'Certyfikacje trenerów', 'view' => 'help/manual/compliance/certifications', 'reading_time' => '4 min', 'last_updated' => $lu],
                    'gdpr-portal'   => ['title' => 'Portal samoobsługowy GDPR', 'view' => 'help/manual/compliance/gdpr-portal', 'reading_time' => '5 min', 'last_updated' => $lu],
                ],
            ],
            'federations' => [
                'title' => 'Federacje i integracje',
                'icon'  => 'bi-diagram-3',
                'desc'  => 'Połączenie z federacjami, kalendarze, wysyłki, API mobilne.',
                'pages' => [
                    'federations'    => ['title' => 'Połączenie z federacją sportową', 'view' => 'help/manual/federations/federations', 'reading_time' => '6 min', 'last_updated' => $lu],
                    'google-calendar'=> ['title' => 'Google Calendar — eksport wydarzeń', 'view' => 'help/manual/federations/google-calendar', 'reading_time' => '4 min', 'last_updated' => $lu],
                    'mobile-api'     => ['title' => 'Dostęp API mobilny (tokeny i scope)', 'view' => 'help/manual/federations/mobile-api', 'reading_time' => '5 min', 'last_updated' => $lu],
                ],
            ],
            'reports' => [
                'title' => 'Raporty i analityka',
                'icon'  => 'bi-bar-chart',
                'desc'  => 'Dashboard KPI, statystyki, raporty i audit log.',
                'pages' => [
                    'dashboard'     => ['title' => 'Dashboard z KPI', 'view' => 'help/manual/reports/dashboard', 'reading_time' => '5 min', 'last_updated' => $lu],
                    'cross-sport'   => ['title' => 'Cross-sport statystyki', 'view' => 'help/manual/reports/cross-sport', 'reading_time' => '4 min', 'last_updated' => $lu],
                    'audit-log'     => ['title' => 'Audit log — kto, co, kiedy', 'view' => 'help/manual/reports/audit-log', 'reading_time' => '4 min', 'last_updated' => $lu],
                ],
            ],
            'security' => [
                'title' => 'Bezpieczeństwo i RODO admina',
                'icon'  => 'bi-lock',
                'desc'  => 'MFA/2FA, sesje, audyt aktywności.',
                'pages' => [
                    'mfa'      => ['title' => 'MFA / 2FA dla administratora', 'view' => 'help/manual/security/mfa', 'reading_time' => '4 min', 'last_updated' => $lu],
                    'sessions' => ['title' => 'Sesje i wylogowanie', 'view' => 'help/manual/security/sessions', 'reading_time' => '4 min', 'last_updated' => $lu],
                ],
            ],
        ];
    }

    /**
     * Płaska mapa slug → metadane (slug ma format `admin-{cat}-{page}`).
     * @return array<string,array{title:string,view:string,category:string,categoryTitle:string,categoryIcon:string,reading_time:string,last_updated:string,pageKey:string,categoryKey:string}>
     */
    public static function flatPages(): array
    {
        $flat = [];
        foreach (self::categories() as $catKey => $cat) {
            foreach ($cat['pages'] as $pageKey => $page) {
                $slug = 'admin-' . $catKey . '-' . $pageKey;
                $flat[$slug] = [
                    'title'         => $page['title'],
                    'view'          => $page['view'],
                    'category'      => $cat['title'],
                    'categoryTitle' => $cat['title'],
                    'categoryIcon'  => $cat['icon'],
                    'categoryKey'   => $catKey,
                    'pageKey'       => $pageKey,
                    'reading_time'  => $page['reading_time'],
                    'last_updated'  => $page['last_updated'],
                ];
            }
        }
        return $flat;
    }

    /**
     * Zwraca [prevSlug|null, nextSlug|null] dla danego slug-a.
     * @return array{0:?string,1:?string}
     */
    public static function neighbors(string $slug): array
    {
        $slugs = array_keys(self::flatPages());
        $idx = array_search($slug, $slugs, true);
        if ($idx === false) {
            return [null, null];
        }
        $prev = $idx > 0 ? $slugs[$idx - 1] : null;
        $next = $idx < count($slugs) - 1 ? $slugs[$idx + 1] : null;
        return [$prev, $next];
    }
}
