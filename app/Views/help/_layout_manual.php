<?php
/**
 * Wspólny layout dla stron manuala (Trener / Sekretariat / Admin / Klub).
 *
 * Wejście:
 *   $page = [
 *       'title'        => string,
 *       'category'     => string,   // np. 'Trener' albo 'Sekretariat'
 *       'last_updated' => string,   // YYYY-MM-DD
 *       'reading_time' => string,   // np. '3 min'
 *   ];
 *   $manualNav = [
 *       'base'    => 'help/trainer',
 *       'current' => 'dashboard',
 *       'items'   => [ ['slug' => 'dashboard', 'title' => 'Dashboard', 'group' => 'Wprowadzenie'], ... ],
 *   ];
 *
 * Plik jest dołączany metodą `include __DIR__ . '/_layout_manual.php'`
 * z PHP-buffered zawartością strony — wszystko co po include zostanie
 * wyrenderowane w kolumnie głównej.
 *
 * Layout sam w sobie NIE jest "Views layoutem" w sensie View::setLayout — strona
 * po stronie controllera używa standardowego layoutu `main` (sidebar nawigacji,
 * topbar). Ten plik tylko opakowuje treść strony manuala w siatkę 3/9
 * z sidebar TOC po lewej.
 */

use App\Helpers\View;

$page      = $page ?? [];
$manualNav = $manualNav ?? ['base' => 'help', 'current' => '', 'items' => []];

// Zbuduj content z otwartego bufora — strona zaczyna echo'wać HTML po include.
// Trzymamy taki "post-include emit" wzorzec, więc TOP layoutu generujemy
// natychmiast, a stopkę poprzez register_shutdown / function — w tym wypadku
// najprościej: rozdzielamy się tak, że strona kończy się znacznikiem,
// a ten plik wypluwa nagłówek + otwarcie kolumny. Domknięcie HTML znajduje
// się w `_layout_manual_footer.php`, który strona dołącza na końcu.
?>
<style>
    .manual-wrapper { display: grid; grid-template-columns: 280px 1fr; gap: 2rem; align-items: start; }
    @media (max-width: 991.98px) { .manual-wrapper { grid-template-columns: 1fr; } }

    .manual-sidebar {
        position: sticky; top: 1rem;
        max-height: calc(100vh - 2rem);
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: .5rem;
        padding: 1rem;
        background: #fafafa;
    }
    .manual-sidebar h6 {
        font-size: .75rem; letter-spacing: .04em;
        text-transform: uppercase; color: #6b7280;
        margin-top: 1rem; margin-bottom: .35rem;
    }
    .manual-sidebar h6:first-child { margin-top: 0; }
    .manual-sidebar a {
        display: block; padding: .25rem .5rem;
        border-radius: .25rem; font-size: .9rem;
        color: #374151; text-decoration: none;
    }
    .manual-sidebar a:hover { background: #fff; }
    .manual-sidebar a.active {
        font-weight: 600;
        color: var(--app-primary, #EE2C28);
        background: #fff;
        border-left: 3px solid var(--app-primary, #EE2C28);
        padding-left: calc(.5rem - 3px);
    }

    .manual-article { max-width: 820px; line-height: 1.7; }
    .manual-article h1 { font-size: 2rem; margin-bottom: .25rem; }
    .manual-article h2 {
        font-size: 1.45rem; margin-top: 2.2rem; padding-top: .5rem;
        border-top: 1px solid #eee;
    }
    .manual-article h3 { font-size: 1.15rem; margin-top: 1.5rem; }
    .manual-article .lead { color: #4b5563; font-size: 1.05rem; }
    .manual-article ul, .manual-article ol { padding-left: 1.2rem; }
    .manual-article blockquote {
        border-left: 4px solid var(--app-primary, #EE2C28);
        background: #fff5f5;
        padding: .5rem 1rem;
        color: #555;
        margin: 1rem 0;
    }
    .manual-article code {
        background: #f6f8fa; padding: .1em .35em;
        border-radius: .25rem; font-size: .9em;
    }
    .manual-meta {
        font-size: .85rem; color: #6b7280;
        display: flex; flex-wrap: wrap; gap: 1rem;
        margin-bottom: 1.5rem;
        padding-bottom: .75rem;
        border-bottom: 1px dashed #e5e7eb;
    }

    /* Mockupy ekranów aplikacji */
    .manual-mockup {
        border: 1px solid #d8d8d8;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        margin: 1.5rem 0;
        overflow: hidden;
        background: #fff;
    }
    .manual-mockup-toolbar {
        background: #2d2d2d; color: #aaa;
        padding: .5rem 1rem;
        font-family: ui-monospace, SFMono-Regular, monospace;
        font-size: .85rem;
        display: flex; align-items: center; gap: .5rem;
    }
    .manual-mockup-toolbar::before {
        content: ""; display: inline-block;
        width: 10px; height: 10px; border-radius: 50%;
        background: #ff5f57;
        box-shadow: 18px 0 0 #febc2e, 36px 0 0 #28c840;
        margin-right: 28px;
    }
    .manual-mockup-content {
        background: #fff;
        padding: 1rem;
    }
    /* Mockupy są tylko poglądowe — wyłączamy interakcję */
    .manual-mockup-content table,
    .manual-mockup-content .card,
    .manual-mockup-content form,
    .manual-mockup-content .btn,
    .manual-mockup-content a {
        pointer-events: none;
        user-select: none;
    }
    .manual-mockup-caption {
        font-size: .85rem; color: #6b7280;
        text-align: center;
        padding: .35rem .75rem .75rem;
        font-style: italic;
    }

    .manual-pager {
        display: flex; justify-content: space-between;
        gap: 1rem; margin-top: 2rem; padding-top: 1.25rem;
        border-top: 1px solid #e5e7eb;
    }
    .manual-pager .btn { min-width: 0; }
    .manual-tip {
        background: #eff6ff; border-left: 4px solid #3b82f6;
        padding: .75rem 1rem; border-radius: .25rem;
        margin: 1rem 0;
    }
    .manual-warn {
        background: #fffbeb; border-left: 4px solid #f59e0b;
        padding: .75rem 1rem; border-radius: .25rem;
        margin: 1rem 0;
    }
</style>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="small mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= url('help') ?>">Pomoc</a></li>
            <li class="breadcrumb-item">
                <a href="<?= url($manualNav['base']) ?>">Manual <?= View::e($page['category'] ?? '') ?></a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= View::e($page['title'] ?? '') ?>
            </li>
        </ol>
    </nav>

    <div class="manual-wrapper">
        <aside class="manual-sidebar">
            <h6>Manual <?= View::e($page['category'] ?? '') ?></h6>
            <?php
            $lastGroup = null;
            foreach ($manualNav['items'] as $item):
                $group = $item['group'] ?? '';
                if ($group !== $lastGroup):
                    if ($lastGroup !== null): ?></div><?php endif; ?>
                    <h6><?= View::e($group) ?></h6>
                    <div>
                    <?php $lastGroup = $group;
                endif; ?>
                <a href="<?= url($manualNav['base'] . '/' . $item['slug']) ?>"
                   class="<?= $item['slug'] === ($manualNav['current'] ?? '') ? 'active' : '' ?>">
                    <?= View::e($item['title']) ?>
                </a>
            <?php endforeach;
            if ($lastGroup !== null): ?></div><?php endif; ?>
        </aside>

        <article class="manual-article">
            <div class="manual-meta">
                <span><i class="bi bi-tag"></i> <?= View::e($page['category'] ?? '') ?></span>
                <?php if (!empty($page['reading_time'])): ?>
                    <span><i class="bi bi-clock"></i> <?= View::e($page['reading_time']) ?> czytania</span>
                <?php endif; ?>
                <?php if (!empty($page['last_updated'])): ?>
                    <span><i class="bi bi-calendar-check"></i> Ostatnia aktualizacja: <?= View::e($page['last_updated']) ?></span>
                <?php endif; ?>
            </div>
