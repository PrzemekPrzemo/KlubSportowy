<?php use App\Helpers\View; ?>

<h4 class="mb-3">
    <i class="bi bi-graph-up text-primary me-2"></i>
    Raport karnetów — <?= View::e($sportName) ?>
</h4>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <small class="text-muted">Aktywne</small>
                <h3 class="mb-0 text-success"><?= (int)$stats['active'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <small class="text-muted">Wyczerpane</small>
                <h3 class="mb-0 text-secondary"><?= (int)$stats['exhausted'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <small class="text-muted">Wygasłe</small>
                <h3 class="mb-0 text-muted"><?= (int)$stats['expired'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <small class="text-muted">Przychód brutto</small>
                <h3 class="mb-0 text-primary"><?= number_format($stats['revenue_cents'] / 100, 2, ',', ' ') ?> PLN</h3>
            </div>
        </div>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <a href="<?= url('club/studio/' . $sport . '/schedules') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-calendar-week"></i> Klasy
    </a>
    <a href="<?= url('club/studio/' . $sport . '/pass-types') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-card-checklist"></i> Typy karnetów
    </a>
</div>
