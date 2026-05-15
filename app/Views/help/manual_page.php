<?php
/**
 * Layout pojedynczej strony podręcznika administratora.
 *
 * @var array{title:string,view:string,category:string,categoryTitle:string,categoryIcon:string,categoryKey:string,pageKey:string,reading_time:string,last_updated:string} $pageMeta
 * @var array<string,array{title:string,icon:string,desc:string,pages:array<string,array<string,string>>}> $categories
 * @var string $currentSlug
 * @var ?array{slug:string,title:string} $prev
 * @var ?array{slug:string,title:string} $next
 * @var string $innerView  Widok wewnętrzny do dołączenia (ścieżka bez .php)
 */
use App\Helpers\View;
?>
<style>
/* ── Layout podręcznika ───────────────────────────────────────── */
.manual-wrap { background: #fafafa; min-height: 70vh; }
.manual-sidebar {
    position: sticky; top: 1rem;
    max-height: calc(100vh - 2rem); overflow-y: auto;
    padding-right: .5rem;
}
.manual-sidebar .input-group { margin-bottom: .75rem; }
.manual-sidebar .manual-cat-title {
    font-size: .78rem; font-weight: 700; text-transform: uppercase;
    color: #777; letter-spacing: .04em; margin: 1rem 0 .35rem;
    display: flex; align-items: center; gap: .35rem;
}
.manual-sidebar .manual-cat-title:first-child { margin-top: 0; }
.manual-sidebar .manual-nav-link {
    display: block; padding: .35rem .6rem; margin: 1px 0;
    border-radius: 6px; color: #333; text-decoration: none;
    font-size: .88rem; line-height: 1.3;
}
.manual-sidebar .manual-nav-link:hover { background: #f0f0f0; }
.manual-sidebar .manual-nav-link.active {
    background: var(--app-primary, #ee2c28); color: #fff; font-weight: 500;
}

/* ── Treść ────────────────────────────────────────────────────── */
.manual-article {
    max-width: 880px; margin: 0 auto;
    background: #fff; padding: 2rem 2.5rem;
    border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.manual-article h1 { font-size: 1.85rem; font-weight: 700; margin-bottom: .35rem; }
.manual-article h2 { font-size: 1.35rem; font-weight: 600; margin-top: 2rem; padding-bottom: .35rem; border-bottom: 1px solid #eee; }
.manual-article h3 { font-size: 1.1rem; font-weight: 600; margin-top: 1.5rem; }
.manual-article .lead { color: #555; font-size: 1.05rem; }
.manual-article p, .manual-article ul, .manual-article ol { line-height: 1.7; color: #2c2c2c; }
.manual-article ul li, .manual-article ol li { margin-bottom: .35rem; }
.manual-article code { background: #f6f6f6; padding: .1em .35em; border-radius: 3px; font-size: .9em; }
.manual-article kbd { background: #2d2d2d; color: #fff; padding: .1em .4em; border-radius: 4px; font-size: .85em; }
.manual-article .meta-strip {
    color: #777; font-size: .82rem; margin-bottom: 1.25rem;
    display: flex; flex-wrap: wrap; gap: 1rem;
}

/* ── Mockup ───────────────────────────────────────────────────── */
.manual-mockup {
    border: 1px solid #d8d8d8; border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin: 1.5rem 0; overflow: hidden;
    background: #fff;
}
.manual-mockup-toolbar {
    background: #2d2d2d; color: #aaa;
    padding: .5rem 1rem; font-family: monospace; font-size: .85rem;
    display: flex; align-items: center; gap: .5rem;
}
.manual-mockup-toolbar .dots { display: inline-flex; gap: 5px; margin-right: .6rem; }
.manual-mockup-toolbar .dots span { width: 11px; height: 11px; border-radius: 50%; background: #555; display: inline-block; }
.manual-mockup-toolbar .dots span:first-child { background: #ff5f56; }
.manual-mockup-toolbar .dots span:nth-child(2) { background: #ffbd2e; }
.manual-mockup-toolbar .dots span:nth-child(3) { background: #27c93f; }
.manual-mockup-content { background: #fff; padding: 1rem; }
.manual-mockup-content table, .manual-mockup-content .card, .manual-mockup-content button, .manual-mockup-content a, .manual-mockup-content input, .manual-mockup-content select { pointer-events: none; }
.manual-mockup-caption { font-size: .82rem; color: #666; padding: .35rem 1rem .75rem; font-style: italic; }

/* ── Callouts ─────────────────────────────────────────────────── */
.manual-callout { border-left: 4px solid; padding: .75rem 1rem; border-radius: 0 6px 6px 0; margin: 1rem 0; }
.manual-callout-tip { border-color: #0d6efd; background: #e7f1ff; }
.manual-callout-warn { border-color: #ffc107; background: #fff8e1; }
.manual-callout-danger { border-color: #dc3545; background: #fde8ea; }
.manual-callout-success { border-color: #198754; background: #e6f4ec; }
.manual-callout p:last-child { margin-bottom: 0; }

/* ── FAQ accordion ────────────────────────────────────────────── */
.manual-faq summary { cursor: pointer; font-weight: 500; padding: .65rem 0; border-bottom: 1px solid #eee; list-style: none; display: flex; align-items: center; justify-content: space-between; }
.manual-faq summary::-webkit-details-marker { display: none; }
.manual-faq summary::after { content: '+'; font-size: 1.3rem; color: #888; }
.manual-faq details[open] summary::after { content: '−'; }
.manual-faq details { padding: 0; }
.manual-faq details[open] summary { border-bottom-color: transparent; }
.manual-faq .faq-body { padding: 0 0 .75rem; color: #555; font-size: .95rem; }

/* ── Navigation footer ────────────────────────────────────────── */
.manual-nav-footer { display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #eee; }
.manual-nav-footer a { flex: 1; text-decoration: none; padding: .75rem 1rem; border: 1px solid #eee; border-radius: 8px; color: #333; transition: border-color .15s; }
.manual-nav-footer a:hover { border-color: var(--app-primary, #ee2c28); }
.manual-nav-footer .nav-prev { text-align: left; }
.manual-nav-footer .nav-next { text-align: right; }
.manual-nav-footer .label { display: block; font-size: .72rem; color: #888; text-transform: uppercase; letter-spacing: .04em; }
.manual-nav-footer .title { display: block; font-weight: 600; margin-top: 2px; }
</style>

<div class="manual-wrap">
    <div class="container-fluid py-4 px-md-4">
        <nav aria-label="breadcrumb" class="small mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('help') ?>">Pomoc</a></li>
                <li class="breadcrumb-item"><a href="<?= url('help/admin') ?>">Podręcznik administratora</a></li>
                <li class="breadcrumb-item"><?= View::e($pageMeta['categoryTitle']) ?></li>
                <li class="breadcrumb-item active" aria-current="page"><?= View::e($pageMeta['title']) ?></li>
            </ol>
        </nav>

        <div class="row g-4">
            <aside class="col-12 col-lg-3">
                <div class="manual-sidebar">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" id="manual-side-search" class="form-control" placeholder="Filtruj sekcje…" autocomplete="off">
                    </div>

                    <?php foreach ($categories as $catKey => $cat): ?>
                        <div class="manual-cat-block">
                            <div class="manual-cat-title"><i class="bi <?= View::e($cat['icon']) ?>"></i><?= View::e($cat['title']) ?></div>
                            <?php foreach ($cat['pages'] as $pageKey => $page):
                                $slug = 'admin-' . $catKey . '-' . $pageKey;
                                $isActive = $slug === $currentSlug;
                            ?>
                                <a href="<?= url('help/' . $slug) ?>"
                                   class="manual-nav-link<?= $isActive ? ' active' : '' ?>"
                                   data-title="<?= View::e(strtolower($page['title'])) ?>">
                                    <?= View::e($page['title']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>

            <main class="col-12 col-lg-9">
                <article class="manual-article">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-light text-dark border"><i class="bi <?= View::e($pageMeta['categoryIcon']) ?>"></i> <?= View::e($pageMeta['categoryTitle']) ?></span>
                    </div>
                    <h1><?= View::e($pageMeta['title']) ?></h1>
                    <div class="meta-strip">
                        <span><i class="bi bi-clock"></i> Czas czytania: <?= View::e($pageMeta['reading_time']) ?></span>
                        <span><i class="bi bi-calendar3"></i> Ostatnia aktualizacja: <?= View::e($pageMeta['last_updated']) ?></span>
                    </div>

                    <?php
                    $innerPath = View::resolveTemplate($innerView);
                    if ($innerPath !== null) {
                        include $innerPath;
                    } else {
                        echo '<div class="alert alert-warning">Treść w przygotowaniu.</div>';
                    }
                    ?>

                    <div class="manual-nav-footer">
                        <?php if ($prev): ?>
                            <a href="<?= url('help/' . $prev['slug']) ?>" class="nav-prev">
                                <span class="label"><i class="bi bi-arrow-left"></i> Poprzednia</span>
                                <span class="title"><?= View::e($prev['title']) ?></span>
                            </a>
                        <?php else: ?>
                            <a href="<?= url('help/admin') ?>" class="nav-prev">
                                <span class="label"><i class="bi bi-arrow-left"></i> Wróć</span>
                                <span class="title">Spis treści</span>
                            </a>
                        <?php endif; ?>

                        <?php if ($next): ?>
                            <a href="<?= url('help/' . $next['slug']) ?>" class="nav-next">
                                <span class="label">Następna <i class="bi bi-arrow-right"></i></span>
                                <span class="title"><?= View::e($next['title']) ?></span>
                            </a>
                        <?php else: ?>
                            <a href="<?= url('help/admin') ?>" class="nav-next">
                                <span class="label">Spis treści <i class="bi bi-list"></i></span>
                                <span class="title">Wszystkie strony</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </article>
            </main>
        </div>
    </div>
</div>

<script>
(function(){
    var input = document.getElementById('manual-side-search');
    if (!input) return;
    var links = document.querySelectorAll('.manual-sidebar .manual-nav-link');
    var titles = document.querySelectorAll('.manual-sidebar .manual-cat-title');
    input.addEventListener('input', function(){
        var q = this.value.trim().toLowerCase();
        links.forEach(function(a){
            a.style.display = (!q || a.dataset.title.indexOf(q) !== -1) ? '' : 'none';
        });
        // Hide empty category headers
        titles.forEach(function(t){
            var any = false;
            var n = t.nextElementSibling;
            while (n && n.classList && n.classList.contains('manual-nav-link')) {
                if (n.style.display !== 'none') { any = true; break; }
                n = n.nextElementSibling;
            }
            t.style.display = any ? '' : 'none';
        });
    });
})();
</script>
