<?php
/**
 * Index manuala Sekretariatu — hero + kafelki kategorii.
 * @var array<int, array{slug:string,title:string,group:string,reading_time:string}> $manualPages
 */
use App\Helpers\View;

$groups = [];
foreach ($manualPages as $p) {
    $groups[$p['group']][] = $p;
}

$groupMeta = [
    'Wprowadzenie'              => ['icon' => 'bi-rocket-takeoff', 'desc' => 'Rola sekretariatu w ClubDesk i przegląd dashboardu.'],
    'Członkowie'                => ['icon' => 'bi-person-vcard',   'desc' => 'Rejestracja, aktualizacja danych, dokumenty członkowskie, eksporty.'],
    'Składki i finanse'         => ['icon' => 'bi-receipt',        'desc' => 'Generowanie faktur, statusy płatności, przypomnienia, korekty.'],
    'Korespondencja'            => ['icon' => 'bi-envelope-paper', 'desc' => 'Kampanie email, SMS przypomnienia, drukowanie zaświadczeń PDF.'],
    'Dokumenty i compliance'    => ['icon' => 'bi-shield-check',   'desc' => 'Badania medyczne, zgody RODO, zaświadczenia o przynależności.'],
];
?>
<div class="container py-4">
    <nav aria-label="breadcrumb" class="small mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= url('help') ?>">Pomoc</a></li>
            <li class="breadcrumb-item active">Manual Sekretariatu</li>
        </ol>
    </nav>

    <div class="p-4 p-md-5 mb-4 rounded-3"
         style="background: linear-gradient(135deg, #0f172a 0%, #2563eb 100%); color: #fff;">
        <div class="row align-items-center g-4">
            <div class="col-md-9">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <i class="bi bi-folder-check" style="font-size:2.5rem;"></i>
                    <h1 class="mb-0">Manual Sekretariatu</h1>
                </div>
                <p class="lead mb-3" style="color: rgba(255,255,255,0.9);">
                    Kompletny podręcznik dla sekretariatu klubu. <?= count($manualPages) ?> stron z mockupami
                    formularzy i tabel — od rejestracji członka po zaświadczenia i RODO.
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
                <a href="<?= url('help/secretariat/' . $manualPages[0]['slug']) ?>"
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
                                    <a href="<?= url('help/secretariat/' . $item['slug']) ?>"
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
