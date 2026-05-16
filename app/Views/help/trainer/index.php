<?php
/**
 * Index manuala Trenera — hero + kafelki kategorii.
 * @var array<int, array{slug:string,title:string,group:string,reading_time:string}> $manualPages
 */
use App\Helpers\View;

$groups = [];
foreach ($manualPages as $p) {
    $groups[$p['group']][] = $p;
}

$groupMeta = [
    'Wprowadzenie'        => ['icon' => 'bi-rocket-takeoff',  'desc' => 'Pierwsze kroki w panelu trenera — logowanie, dashboard, uprawnienia.'],
    'Sekcje i zawodnicy'  => ['icon' => 'bi-people-fill',     'desc' => 'Zarządzanie sekcjami, dodawanie zawodników, profile i komunikacja.'],
    'Treningi i obecność' => ['icon' => 'bi-calendar-week',   'desc' => 'Harmonogram, obecności, notatki, substytucje, raporty frekwencji.'],
    'Turnieje i wyniki'   => ['icon' => 'bi-trophy-fill',     'desc' => 'Zgłoszenia, drabinki, wyniki, rankingi i statystyki zawodników.'],
    'Prowizje trenera'    => ['icon' => 'bi-cash-stack',      'desc' => 'System prowizji, raporty wypłat, rozliczenia z klubem.'],
];
?>
<div class="container py-4">
    <nav aria-label="breadcrumb" class="small mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= url('help') ?>">Pomoc</a></li>
            <li class="breadcrumb-item active">Manual Trenera</li>
        </ol>
    </nav>

    <div class="p-4 p-md-5 mb-4 rounded-3"
         style="background: linear-gradient(135deg, #1e293b 0%, #ee2c28 100%); color: #fff;">
        <div class="row align-items-center g-4">
            <div class="col-md-9">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <i class="bi bi-stopwatch-fill" style="font-size:2.5rem;"></i>
                    <h1 class="mb-0">Manual Trenera</h1>
                </div>
                <p class="lead mb-3" style="color: rgba(255,255,255,0.9);">
                    Kompletny podręcznik dla trenerów ClubDesk. <?= count($manualPages) ?> stron z mockupami
                    ekranów, krok po kroku — od pierwszego logowania po raporty prowizji.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark">
                        <i class="bi bi-book"></i> <?= count($manualPages) ?> stron
                    </span>
                    <span class="badge bg-light text-dark">
                        <i class="bi bi-image"></i> Mockupy UI
                    </span>
                    <span class="badge bg-light text-dark">
                        <i class="bi bi-translate"></i> Polski
                    </span>
                    <span class="badge bg-light text-dark">
                        <i class="bi bi-calendar-check"></i> Aktualizacja 2026-05-15
                    </span>
                </div>
            </div>
            <div class="col-md-3 text-md-end">
                <a href="<?= url('help/trainer/' . $manualPages[0]['slug']) ?>"
                   class="btn btn-light btn-lg">
                    <i class="bi bi-play-circle"></i> Zacznij od początku
                </a>
            </div>
        </div>
    </div>

    <h2 class="h4 mb-3">Kategorie</h2>
    <div class="row g-3 mb-4">
        <?php foreach ($groups as $groupName => $items): ?>
            <?php $meta = $groupMeta[$groupName] ?? ['icon' => 'bi-folder', 'desc' => '']; ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi <?= View::e($meta['icon']) ?> fs-2 text-primary"></i>
                            <h5 class="card-title mb-0"><?= View::e($groupName) ?></h5>
                        </div>
                        <p class="card-text text-muted small">
                            <?= View::e($meta['desc']) ?>
                        </p>
                        <ul class="list-unstyled small mb-0">
                            <?php foreach ($items as $item): ?>
                                <li class="mb-1">
                                    <a href="<?= url('help/trainer/' . $item['slug']) ?>"
                                       class="text-decoration-none">
                                        <i class="bi bi-chevron-right small text-muted"></i>
                                        <?= View::e($item['title']) ?>
                                        <span class="text-muted">· <?= View::e($item['reading_time']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="alert alert-info small mb-0">
        <i class="bi bi-info-circle"></i>
        Manuał zawiera <strong>realistyczne mockupy ekranów</strong> — wszystkie tabele, formularze i karty
        są poglądowe (nieinteraktywne). Faktyczne dane w Twoim panelu mogą się różnić.
    </div>
</div>
