<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-sm-4">
        <div class="card p-3">
            <div class="text-muted small">Kluby (aktywne)</div>
            <div class="display-6"><?= (int)$metrics['clubs_active'] ?> / <?= (int)$metrics['clubs'] ?></div>
            <a href="<?= url('admin/clubs') ?>" class="stretched-link small">Zarządzaj &rarr;</a>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3">
            <div class="text-muted small">Użytkownicy</div>
            <div class="display-6"><?= (int)$metrics['users'] ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3">
            <div class="text-muted small">Zawodnicy aktywni</div>
            <div class="display-6"><?= (int)$metrics['members'] ?></div>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="card p-3">
            <div class="text-muted small">Sporty w katalogu</div>
            <div class="display-6"><?= (int)$metrics['sports'] ?></div>
            <a href="<?= url('admin/sports') ?>" class="stretched-link small">Katalog sportów &rarr;</a>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="card p-3">
            <div class="text-muted small">Aktywne sekcje klubowe</div>
            <div class="display-6"><?= (int)$metrics['club_sports'] ?></div>
        </div>
    </div>
</div>
