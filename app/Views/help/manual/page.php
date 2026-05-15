<?php
/**
 * Renderer pojedynczej strony manualu (Zawodnik / Rodzic).
 *
 * Treść strony to czysty HTML/PHP — plik strony (włączany przez `include`)
 * sam produkuje swój `<h1>`, wstępy, mockupy itd. Tu rysujemy ramę:
 * breadcrumbs, sidebar z listą stron i meta-pasek.
 *
 * @var array  $page             ['title','reading_time','last_updated','group',...]
 * @var string $manualFile       Pełna ścieżka pliku strony (zwalidowana w kontrolerze).
 * @var array  $manualPages      Lista wszystkich stron tego manualu.
 * @var string $currentSlug
 * @var string $manualBaseUrl    np. 'help/member'
 * @var string $manualLabel      np. 'Portal zawodnika'
 */

use App\Helpers\View;

// Grupowanie sidebara
$sidebarGroups = [];
foreach ($manualPages as $p) {
    $g = (string)($p['group'] ?? 'Inne');
    $sidebarGroups[$g][] = $p;
}
?>
<style>
    .manual-wrap { background: #fafafa; min-height: 70vh; }
    .manual-sidebar { position: sticky; top: 1rem; max-height: calc(100vh - 2rem); overflow-y: auto; padding-right: .5rem; }
    .manual-sidebar .manual-cat-title { font-size: .78rem; font-weight: 700; text-transform: uppercase; color: #777; letter-spacing: .04em; margin: 1rem 0 .35rem; }
    .manual-sidebar .manual-cat-title:first-child { margin-top: 0; }
    .manual-sidebar .manual-nav-link { display: block; padding: .35rem .6rem; margin: 1px 0; border-radius: 6px; color: #333; text-decoration: none; font-size: .88rem; line-height: 1.3; }
    .manual-sidebar .manual-nav-link:hover { background: #f0f0f0; }
    .manual-sidebar .manual-nav-link.active { background: var(--app-primary, #ee2c28); color: #fff; font-weight: 500; }
    .manual-article { max-width: 880px; margin: 0 auto; background: #fff; padding: 2rem 2.5rem; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
    .manual-article h1 { font-size: 1.85rem; font-weight: 700; margin-bottom: .35rem; }
    .manual-article h2 { font-size: 1.35rem; font-weight: 600; margin-top: 2rem; padding-bottom: .35rem; border-bottom: 1px solid #eee; }
    .manual-article h3 { font-size: 1.1rem; font-weight: 600; margin-top: 1.5rem; }
    .manual-article .lead { color: #555; font-size: 1.05rem; }
    .manual-article p, .manual-article ul, .manual-article ol { line-height: 1.7; color: #2c2c2c; }
    .manual-article ul li, .manual-article ol li { margin-bottom: .35rem; }
    .manual-article .meta-strip { color: #777; font-size: .82rem; margin-bottom: 1.25rem; display: flex; flex-wrap: wrap; gap: 1rem; }
    .manual-article details { background:#f8f9fa; border:1px solid #e5e7eb; border-radius:.4rem; padding: .6rem .9rem; margin: .5rem 0; }
    .manual-article details summary { cursor: pointer; font-weight: 600; }
    .manual-article details[open] summary { margin-bottom: .4rem; }
    .manual-mockup { border: 1px solid #d8d8d8; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin: 1.5rem 0; overflow: hidden; background: #fff; }
    .manual-mockup-toolbar { background: #2d2d2d; color: #ddd; padding: .5rem 1rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .85rem; display:flex; align-items:center; gap:.5rem; }
    .manual-mockup-toolbar .dots { display:inline-flex; gap:6px; margin-right: 10px; }
    .manual-mockup-toolbar .dots span { width:10px; height:10px; border-radius:50%; display:inline-block; }
    .manual-mockup-toolbar .dots .r { background:#ff5f56; }
    .manual-mockup-toolbar .dots .y { background:#ffbd2e; }
    .manual-mockup-toolbar .dots .g { background:#27c93f; }
    .manual-mockup-content { background: #fff; padding: 1rem; }
    .manual-mockup-content .btn { cursor: default !important; }
    .manual-mockup-content table, .manual-mockup-content .card, .manual-mockup-content button, .manual-mockup-content input, .manual-mockup-content select { pointer-events: none; }
    .manual-mockup-caption { font-size:.82rem; color:#666; padding: .35rem 1rem .75rem; font-style: italic; }
    .manual-tip { background:#eef9f1; border-left:4px solid #28a745; padding:.7rem 1rem; border-radius:.3rem; margin: 1rem 0; }
    .manual-warn { background:#fff8e1; border-left:4px solid #f5a623; padding:.7rem 1rem; border-radius:.3rem; margin: 1rem 0; }
    .manual-info { background:#eef4ff; border-left:4px solid #0d6efd; padding:.7rem 1rem; border-radius:.3rem; margin: 1rem 0; }
    .manual-step-num { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:50%; background:var(--app-primary, #EE2C28); color:#fff; font-weight:700; margin-right:.5rem; }
</style>

<div class="manual-wrap">
    <div class="container-fluid py-4 px-md-4">
        <nav aria-label="breadcrumb" class="small mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('help') ?>">Pomoc</a></li>
                <li class="breadcrumb-item"><a href="<?= url($manualBaseUrl) ?>"><?= View::e($manualLabel) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= View::e($page['title']) ?></li>
            </ol>
        </nav>

        <div class="row g-4">
            <aside class="col-12 col-lg-3">
                <div class="manual-sidebar">
                    <?php foreach ($sidebarGroups as $groupName => $items): ?>
                        <div class="manual-cat-block">
                            <div class="manual-cat-title"><?= View::e($groupName) ?></div>
                            <?php foreach ($items as $item):
                                $isActive = $item['slug'] === $currentSlug;
                            ?>
                                <a href="<?= url($manualBaseUrl . '/' . $item['slug']) ?>"
                                   class="manual-nav-link<?= $isActive ? ' active' : '' ?>">
                                    <?php if (!empty($item['icon'])): ?><i class="bi <?= View::e($item['icon']) ?> me-1"></i><?php endif; ?>
                                    <?= View::e($item['title']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    <hr>
                    <a href="<?= url('help') ?>" class="small text-muted">
                        <i class="bi bi-arrow-left"></i> Wszystkie podręczniki
                    </a>
                </div>
            </aside>

            <main class="col-12 col-lg-9">
                <article class="manual-article">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <?php if (!empty($page['group'])): ?>
                            <span class="badge bg-light text-dark border"><?= View::e($page['group']) ?></span>
                        <?php endif; ?>
                        <span class="badge bg-secondary"><?= View::e($manualLabel) ?></span>
                    </div>
                    <div class="meta-strip mt-2">
                        <?php if (!empty($page['reading_time'])): ?>
                            <span><i class="bi bi-clock"></i> Czas czytania: <?= View::e($page['reading_time']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($page['last_updated'])): ?>
                            <span><i class="bi bi-calendar3"></i> Ostatnia aktualizacja: <?= View::e($page['last_updated']) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php
                    // Include strony — to ona produkuje treść <h1>...<details>...
                    include $manualFile;
                    ?>

                    <hr class="mt-5">
                    <p class="small text-muted mb-0">
                        Potrzebujesz pomocy? Napisz na
                        <a href="mailto:support@clubdesk.pl">support@clubdesk.pl</a>
                        albo zapytaj swojego trenera / sekretariat.
                        <span class="ms-2"><a href="<?= url($manualBaseUrl) ?>"><i class="bi bi-arrow-left"></i> Spis treści</a></span>
                    </p>
                </article>
            </main>
        </div>
    </div>
</div>
