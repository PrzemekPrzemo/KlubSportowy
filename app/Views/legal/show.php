<?php
use App\Helpers\View;
/** @var array $doc */
/** @var string $htmlBody */
/** @var array $toc */
/** @var string $slug */
/** @var array $versions */

$effDate = !empty($doc['effective_from']) ? date('d.m.Y', strtotime((string)$doc['effective_from'])) : '';
?>
<style>
    .legal-page { background: #fff; }
    .legal-toc {
        position: sticky; top: 1rem; max-height: calc(100vh - 2rem);
        overflow-y: auto; font-size: .9rem;
        padding: 1rem 1rem 1rem 0; border-right: 1px solid #e9ecef;
    }
    .legal-toc h6 { font-size: .75rem; text-transform: uppercase; letter-spacing: .05em; color: #6c757d; }
    .legal-toc a { display: block; padding: .35rem .6rem; border-radius: .35rem;
        color: #495057; text-decoration: none; line-height: 1.35; }
    .legal-toc a:hover { background: #f1f3f5; color: #232232; }
    .legal-toc a.active { background: rgba(238,44,40,.08); color: #EE2C28; font-weight: 500; }
    .legal-content { max-width: 820px; line-height: 1.65; color: #232232; font-size: 1rem; }
    .legal-content h1 { font-size: 2rem; font-weight: 700; margin-top: 0; }
    .legal-content h2 { font-size: 1.5rem; font-weight: 600; margin-top: 2.5rem; padding-top: 1rem;
        border-top: 1px solid #e9ecef; color: #232232; scroll-margin-top: 1rem; }
    .legal-content h3 { font-size: 1.2rem; font-weight: 600; margin-top: 2rem; }
    .legal-content h4 { font-size: 1.05rem; font-weight: 600; margin-top: 1.5rem; }
    .legal-content p, .legal-content li { font-size: 1rem; }
    .legal-content table { font-size: .9rem; margin: 1.25rem 0; }
    .legal-content table th { background: #f8f9fa; }
    .legal-content blockquote { border-left: 4px solid #EE2C28; padding: .75rem 1rem; background: #f8f9fa;
        margin: 1.25rem 0; border-radius: 0 .35rem .35rem 0; color: #495057; }
    .legal-content hr { border-top: 1px dashed #ced4da; margin: 1.5rem 0; }
    .legal-content code { background: #f8f9fa; padding: .1rem .35rem; border-radius: .25rem;
        font-size: .9em; color: #d63384; }
    .legal-meta { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: .5rem;
        padding: 1rem 1.25rem; font-size: .9rem; }
    .legal-actions .btn { font-size: .85rem; }
    .doc-version-list { font-size: .85rem; }
    .doc-version-list .current { font-weight: 600; color: #EE2C28; }

    /* Print-friendly */
    @media print {
        nav, footer, .legal-toc, .legal-actions, .navbar, .alert { display: none !important; }
        body { background: #fff; }
        .legal-content { max-width: 100%; font-size: 11pt; line-height: 1.45; }
        .legal-content h2 { page-break-after: avoid; }
        a { color: #000; text-decoration: none; }
        a[href^="http"]::after { content: " (" attr(href) ")"; font-size: 9pt; color: #555; }
    }
</style>

<section class="legal-page py-4">
    <div class="container">
        <div class="row">
            <aside class="col-lg-3 d-none d-lg-block">
                <div class="legal-toc">
                    <h6 class="mb-3"><i class="bi bi-list-ul me-1"></i> Spis treści</h6>
                    <a href="<?= url('legal') ?>" class="mb-3" style="color:#6c757d;">
                        <i class="bi bi-arrow-left"></i> Wszystkie dokumenty
                    </a>
                    <nav id="legal-toc-nav">
                        <?php foreach ($toc as $t): ?>
                            <a href="#<?= View::e($t['id']) ?>"
                               data-target="<?= View::e($t['id']) ?>"><?= View::e($t['text']) ?></a>
                        <?php endforeach; ?>
                    </nav>

                    <?php if (count($versions) > 1): ?>
                        <hr class="my-3">
                        <h6 class="mb-2"><i class="bi bi-clock-history me-1"></i> Historia wersji</h6>
                        <ul class="list-unstyled doc-version-list mb-0">
                            <?php foreach ($versions as $v): ?>
                                <li class="<?= !empty($v['is_current']) ? 'current' : '' ?>">
                                    <?php if ((int)$v['id'] === (int)$doc['id']): ?>
                                        <span><?= View::e($v['version']) ?> &middot; <?= View::e(date('d.m.Y', strtotime((string)$v['effective_from']))) ?></span>
                                    <?php else: ?>
                                        <a href="<?= url('legal/' . $slug . '/v/' . $v['version']) ?>"
                                           style="color:#6c757d; text-decoration:underline;">
                                            <?= View::e($v['version']) ?> &middot; <?= View::e(date('d.m.Y', strtotime((string)$v['effective_from']))) ?>
                                        </a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </aside>

            <div class="col-lg-9">
                <div class="legal-meta d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                    <div>
                        <strong><i class="bi bi-shield-check text-success me-1"></i> Wersja <?= View::e($doc['version']) ?></strong>
                        &middot; obowiązuje od <strong><?= View::e($effDate) ?></strong>
                        <?php if (empty($doc['is_current'])): ?>
                            <span class="badge bg-warning text-dark ms-2">Wersja archiwalna</span>
                        <?php endif; ?>
                    </div>
                    <div class="legal-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                            <i class="bi bi-printer me-1"></i> Drukuj / PDF
                        </button>
                        <a href="<?= url('legal') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Powrót
                        </a>
                    </div>
                </div>

                <article class="legal-content">
                    <?= $htmlBody // already sanitized HTML from SimpleMarkdown ?>
                </article>

                <div class="legal-meta mt-4">
                    <p class="mb-1 small text-muted">
                        Dokument opublikowany w wersji <?= View::e($doc['version']) ?>,
                        obowiązuje od <?= View::e($effDate) ?>.
                    </p>
                    <p class="mb-0 small text-muted">
                        Operator platformy: <strong>Sendormeco Holding Sp. z o.o.</strong>,
                        NIP 5252866457, KRS 0000906110, ul. Złota 75A/7, 00-819 Warszawa.
                        Kontakt: <a href="mailto:kontakt@clubdesk.pl">kontakt@clubdesk.pl</a> &middot;
                        IOD: <a href="mailto:iod@clubdesk.pl">iod@clubdesk.pl</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Highlight currently visible TOC entry on scroll.
(function() {
    var links = document.querySelectorAll('#legal-toc-nav a[data-target]');
    var sections = Array.from(links)
        .map(function(a) { return document.getElementById(a.getAttribute('data-target')); })
        .filter(Boolean);
    if (!sections.length) return;

    function onScroll() {
        var pos = window.scrollY + 120;
        var active = sections[0];
        for (var i = 0; i < sections.length; i++) {
            if (sections[i].offsetTop <= pos) { active = sections[i]; } else { break; }
        }
        links.forEach(function(a) {
            a.classList.toggle('active', a.getAttribute('data-target') === active.id);
        });
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();
</script>
