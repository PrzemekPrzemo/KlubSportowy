<?php
/**
 * @var array<string, array{file:string,title:string,icon:string,desc:string,available:bool}> $sections
 */
use App\Helpers\View;
use App\Helpers\Auth;

// ── Podział sekcji: per-rola vs. ogólne ─────────────────────────
$guideSlugs = [
    'guide-common', 'guide-zarzad', 'guide-trener', 'guide-instruktor',
    'guide-sedzia', 'guide-ksiegowy', 'guide-lekarz', 'guide-czlonek',
];
$roleGuides    = [];
$otherSections = [];
foreach ($sections as $slug => $sec) {
    if (in_array($slug, $guideSlugs, true)) {
        $roleGuides[$slug] = $sec;
    } else {
        $otherSections[$slug] = $sec;
    }
}

// ── Wyróżnienie przewodnika dla aktualnej roli zalogowanego ─────
$highlightSlug = null;
if (Auth::id()) {
    $roleMap = [
        'zarzad'     => 'guide-zarzad',
        'trener'     => 'guide-trener',
        'instruktor' => 'guide-instruktor',
        'sedzia'     => 'guide-sedzia',
        'ksiegowy'   => 'guide-ksiegowy',
        'lekarz'     => 'guide-lekarz',
    ];
    foreach ($roleMap as $role => $slug) {
        if (Auth::hasRole($role)) {
            $highlightSlug = $slug;
            break;
        }
    }
}
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="mb-1"><i class="bi bi-question-circle"></i> Centrum pomocy</h1>
            <p class="text-muted mb-0">Wszystkie dokumenty, których potrzebujesz, w jednym miejscu — bez czytania surowego markdownu.</p>
        </div>
        <form class="d-flex" role="search" onsubmit="return false;">
            <div class="input-group" style="max-width: 320px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" id="help-search-input" class="form-control" placeholder="Szukaj w pomocy (TODO)…" disabled>
            </div>
        </form>
    </div>

    <a href="<?= url('help/admin') ?>" class="d-block text-decoration-none mb-4">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #ee2c28 0%, #b71d1a 100%); color: #fff;">
            <div class="card-body p-4 d-flex align-items-center gap-3">
                <i class="bi bi-shield-fill-check" style="font-size: 2.5rem; opacity:.9;"></i>
                <div class="flex-grow-1">
                    <h3 class="h5 mb-1">Podręcznik administratora klubu</h3>
                    <p class="mb-0 small" style="opacity:.92;">Kompletna instrukcja krok po kroku ze screenami: członkowie, sport, finanse, komunikacja, compliance, integracje, raporty.</p>
                </div>
                <span class="btn btn-light btn-sm fw-semibold">Otwórz <i class="bi bi-arrow-right"></i></span>
            </div>
        </div>
    </a>

    <?php if (!empty($roleGuides)): ?>
    <h2 class="h5 mt-2 mb-3"><i class="bi bi-people"></i> Przewodniki per rola</h2>
    <div class="row g-3 mb-4">
        <?php foreach ($roleGuides as $slug => $sec): ?>
            <?php $isHighlighted = ($slug === $highlightSlug); ?>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="<?= url('help/' . $slug) ?>"
                   class="card h-100 text-decoration-none text-body position-relative <?= $sec['available'] ? '' : 'opacity-50' ?>"
                   style="border:<?= $isHighlighted ? '2px solid var(--app-primary, #EE2C28)' : '1px solid #e5e7eb' ?>;
                          <?= $isHighlighted ? 'box-shadow: 0 0 0 3px rgba(238,44,40,0.12);' : '' ?>">
                    <?php if ($isHighlighted): ?>
                        <span class="badge bg-primary position-absolute"
                              style="top:-10px; right:10px; font-size:.7rem;">
                            <i class="bi bi-star-fill"></i> Twój przewodnik
                        </span>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi <?= View::e($sec['icon']) ?> fs-3 text-primary"></i>
                            <h5 class="card-title mb-0"><?= View::e($sec['title']) ?></h5>
                        </div>
                        <p class="card-text text-muted small mb-0"><?= View::e($sec['desc']) ?></p>
                        <?php if (!$sec['available']): ?>
                            <span class="badge bg-warning text-dark mt-2">Wkrótce</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white border-0 pt-0 pb-3">
                        <small class="text-primary">Otwórz <i class="bi bi-arrow-right"></i></small>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <h2 class="h5 mt-4 mb-3"><i class="bi bi-book-half"></i> Manuale dla użytkowników</h2>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-6">
            <a href="<?= url('help/member') ?>" class="card h-100 text-decoration-none text-body" style="border:1px solid #e5e7eb;">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-person-arms-up fs-3 text-primary"></i>
                        <h5 class="card-title mb-0">Portal zawodnika</h5>
                        <span class="badge bg-success ms-2">PL</span>
                    </div>
                    <p class="card-text text-muted small mb-0">Kompletny przewodnik dla zawodnika — od pierwszego logowania po RODO i odznaki. 18 stron z mockupami ekranów.</p>
                </div>
                <div class="card-footer bg-white border-0 pt-0 pb-3">
                    <small class="text-primary">Otwórz manual <i class="bi bi-arrow-right"></i></small>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6 col-lg-6">
            <a href="<?= url('help/parent') ?>" class="card h-100 text-decoration-none text-body" style="border:1px solid #e5e7eb;">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-people fs-3 text-primary"></i>
                        <h5 class="card-title mb-0">Portal rodzica / opiekuna</h5>
                        <span class="badge bg-success ms-2">PL</span>
                    </div>
                    <p class="card-text text-muted small mb-0">Przewodnik dla rodzica — dostęp do konta dziecka, składki, RODO za niepełnoletniego. 10 stron.</p>
                </div>
                <div class="card-footer bg-white border-0 pt-0 pb-3">
                    <small class="text-primary">Otwórz manual <i class="bi bi-arrow-right"></i></small>
                </div>
            </a>
        </div>
    </div>

    <?php if (!empty($otherSections)): ?>
    <h2 class="h5 mt-4 mb-3"><i class="bi bi-book"></i> Dokumentacja ogólna</h2>
    <div class="row g-3">
        <?php foreach ($otherSections as $slug => $sec): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <a href="<?= url('help/' . $slug) ?>"
                   class="card h-100 text-decoration-none text-body <?= $sec['available'] ? '' : 'opacity-50' ?>"
                   style="border:1px solid #e5e7eb;">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi <?= View::e($sec['icon']) ?> fs-3 text-primary"></i>
                            <h5 class="card-title mb-0"><?= View::e($sec['title']) ?></h5>
                        </div>
                        <p class="card-text text-muted small mb-0"><?= View::e($sec['desc']) ?></p>
                        <?php if (!$sec['available']): ?>
                            <span class="badge bg-warning text-dark mt-2">Wkrótce</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white border-0 pt-0 pb-3">
                        <small class="text-primary">Otwórz <i class="bi bi-arrow-right"></i></small>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="alert alert-info mt-4 small mb-0">
        <i class="bi bi-info-circle"></i>
        Nie znalazłeś odpowiedzi? Napisz do nas na
        <a href="mailto:support@clubdesk.pl">support@clubdesk.pl</a>.
    </div>
</div>
