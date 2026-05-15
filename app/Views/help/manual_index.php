<?php
/**
 * Index podręcznika administratora klubu.
 *
 * @var array<string,array{title:string,icon:string,desc:string,pages:array<string,array<string,string>>}> $categories
 */
use App\Helpers\View;
?>
<style>
.manual-hero { background: linear-gradient(135deg, #ee2c28 0%, #b71d1a 100%); color: #fff; border-radius: 12px; padding: 2.5rem 2rem; margin-bottom: 2rem; }
.manual-hero h1 { font-weight: 700; margin-bottom: .5rem; }
.manual-hero .lead { opacity: .92; }
.manual-hero .input-group { max-width: 520px; }
.manual-cat-card { transition: transform .12s ease, box-shadow .12s ease; }
.manual-cat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
.manual-cat-card .icon-wrap { width: 48px; height: 48px; border-radius: 10px; background: #fff5f5; display: inline-flex; align-items: center; justify-content: center; font-size: 1.4rem; color: var(--app-primary, #ee2c28); }
.manual-page-list a { display: block; padding: .25rem 0; font-size: .87rem; color: #555; text-decoration: none; }
.manual-page-list a:hover { color: var(--app-primary, #ee2c28); text-decoration: underline; }
.manual-tile-footer { background: transparent; border-top: 1px dashed #eee; }
.manual-tutorial-thumb { background: #f1f1f1; border-radius: 8px; aspect-ratio: 16/9; display: flex; align-items: center; justify-content: center; color: #888; }
</style>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="small mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= url('help') ?>">Pomoc</a></li>
            <li class="breadcrumb-item active" aria-current="page">Podręcznik administratora</li>
        </ol>
    </nav>

    <section class="manual-hero">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <h1><i class="bi bi-shield-fill-check"></i> Podręcznik administratora klubu</h1>
                <p class="lead mb-3">Kompletna instrukcja dla zarządu i administratorów. Skonfiguruj klub, zarządzaj członkami, sportem, finansami i integracjami — krok po kroku.</p>
                <form role="search" onsubmit="return false;" class="mb-0">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="search" id="manual-search" class="form-control form-control-lg" placeholder="Wyszukaj temat (np. import członków, faktury, drabinka)…" autocomplete="off">
                    </div>
                </form>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-end">
                <i class="bi bi-journal-bookmark-fill" style="font-size: 7rem; opacity: .25;"></i>
            </div>
        </div>
    </section>

    <h2 class="h4 mb-3"><i class="bi bi-grid-3x3-gap"></i> Kategorie</h2>
    <div class="row g-3 mb-5" id="manual-categories">
        <?php foreach ($categories as $catKey => $cat):
            $firstPageKey = array_key_first($cat['pages']);
            $firstSlug    = 'admin-' . $catKey . '-' . $firstPageKey;
            $pageCount    = count($cat['pages']);
        ?>
            <div class="col-12 col-md-6 col-lg-4 manual-cat-item" data-keywords="<?= View::e(strtolower($cat['title'] . ' ' . $cat['desc'] . ' ' . implode(' ', array_column($cat['pages'], 'title')))) ?>">
                <div class="card h-100 manual-cat-card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <span class="icon-wrap"><i class="bi <?= View::e($cat['icon']) ?>"></i></span>
                            <div>
                                <h5 class="card-title mb-0"><?= View::e($cat['title']) ?></h5>
                                <small class="text-muted"><?= (int)$pageCount ?> stron(y)</small>
                            </div>
                        </div>
                        <p class="card-text text-muted small mb-3"><?= View::e($cat['desc']) ?></p>
                        <div class="manual-page-list">
                            <?php foreach (array_slice($cat['pages'], 0, 4, true) as $pageKey => $page):
                                $slug = 'admin-' . $catKey . '-' . $pageKey;
                            ?>
                                <a href="<?= url('help/' . $slug) ?>"><i class="bi bi-arrow-right-short"></i><?= View::e($page['title']) ?></a>
                            <?php endforeach; ?>
                            <?php if ($pageCount > 4): ?>
                                <small class="text-muted">+ <?= $pageCount - 4 ?> więcej…</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer manual-tile-footer">
                        <a href="<?= url('help/' . $firstSlug) ?>" class="text-decoration-none small fw-semibold">Otwórz kategorię <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <h2 class="h5 mb-3"><i class="bi bi-fire text-danger"></i> Popularne strony</h2>
            <div class="list-group">
                <a href="<?= url('help/admin-members-list') ?>" class="list-group-item list-group-item-action"><i class="bi bi-people me-2"></i>Lista członków, filtry i wyszukiwanie</a>
                <a href="<?= url('help/admin-members-import') ?>" class="list-group-item list-group-item-action"><i class="bi bi-upload me-2"></i>Import członków z CSV/Excel</a>
                <a href="<?= url('help/admin-finance-payments-online') ?>" class="list-group-item list-group-item-action"><i class="bi bi-credit-card me-2"></i>Konfiguracja płatności online</a>
                <a href="<?= url('help/admin-sport-tournaments') ?>" class="list-group-item list-group-item-action"><i class="bi bi-trophy me-2"></i>Turnieje — harmonogram i uczestnicy</a>
                <a href="<?= url('help/admin-getting-started-branding') ?>" class="list-group-item list-group-item-action"><i class="bi bi-palette me-2"></i>Brand klubu — logo i kolory</a>
            </div>
        </div>
        <div class="col-lg-6">
            <h2 class="h5 mb-3"><i class="bi bi-play-circle text-primary"></i> Wideo tutoriale</h2>
            <div class="row g-3">
                <div class="col-6">
                    <div class="manual-tutorial-thumb mb-1"><i class="bi bi-play-circle" style="font-size:2rem;"></i></div>
                    <small class="d-block">Pierwsza konfiguracja klubu (5:32)</small>
                </div>
                <div class="col-6">
                    <div class="manual-tutorial-thumb mb-1"><i class="bi bi-play-circle" style="font-size:2rem;"></i></div>
                    <small class="d-block">Masowe naliczanie składek (3:14)</small>
                </div>
            </div>
            <p class="text-muted small mt-2 mb-0">Wkrótce — pełna playlist na kanale YouTube ClubDesk.</p>
        </div>
    </div>

    <div class="alert alert-info mt-4 small mb-0">
        <i class="bi bi-info-circle"></i>
        Nie znalazłeś odpowiedzi? Napisz do nas na
        <a href="mailto:support@clubdesk.pl">support@clubdesk.pl</a> lub zajrzyj do <a href="<?= url('help') ?>">Centrum pomocy</a>.
    </div>
</div>

<script>
(function(){
    var input = document.getElementById('manual-search');
    if (!input) return;
    var items = document.querySelectorAll('.manual-cat-item');
    input.addEventListener('input', function(){
        var q = this.value.trim().toLowerCase();
        items.forEach(function(el){
            if (!q || el.dataset.keywords.indexOf(q) !== -1) {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });
    });
})();
</script>
