<?php use App\Helpers\View; ?>

<div class="card p-4 mb-3">
    <div class="d-flex align-items-center mb-3">
        <div class="me-3" style="font-size:2rem;color:#dc3545;">
            <i class="bi bi-bullseye"></i>
        </div>
        <div>
            <h4 class="mb-0">Strzelectwo: shootero.pl</h4>
            <div class="text-muted small">Zewnętrzny system zarządzania strzelectwem sportowym.</div>
        </div>
    </div>

    <p>
        Zaawansowane funkcje strzeleckie (zawody, kategorie ISSF, scoringi,
        harmonogramy strzelnic, klasyfikacje, wyniki i statystyki strzałów)
        realizujemy przez dedykowany serwis
        <a href="https://shootero.pl" target="_blank" rel="noopener"><strong>shootero.pl</strong></a>.
        ClubDesk jest z nim powiązany — w naszym module trzymamy tylko ewidencję
        broni klubowej, amunicji, licencji PZSS i sędziów.
    </p>

    <div class="row g-3 mt-2">
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <h6><i class="bi bi-trophy text-warning"></i> Zawody i wyniki</h6>
                <p class="small text-muted mb-2">Zawody klubowe, wojewódzkie i krajowe; scoringi tarczowe; klasyfikacje.</p>
                <a class="btn btn-sm btn-outline-primary" href="https://shootero.pl" target="_blank" rel="noopener">
                    Otwórz w shootero.pl <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <h6><i class="bi bi-calendar-event text-primary"></i> Strzelnice i sloty</h6>
                <p class="small text-muted mb-2">Rezerwacje stanowisk, harmonogramy, dostępność osi.</p>
                <a class="btn btn-sm btn-outline-primary" href="https://shootero.pl" target="_blank" rel="noopener">
                    Otwórz w shootero.pl <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <h6><i class="bi bi-bar-chart text-success"></i> Statystyki strzelca</h6>
                <p class="small text-muted mb-2">Historia wyników, klasy ISSF, postęp, rekordy klubowe.</p>
                <a class="btn btn-sm btn-outline-primary" href="https://shootero.pl" target="_blank" rel="noopener">
                    Otwórz w shootero.pl <i class="bi bi-box-arrow-up-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card p-3">
    <h6 class="mb-2">Co zostaje w ClubDesk?</h6>
    <ul class="mb-0 small">
        <li><a href="<?= url('shooting/weapons') ?>">Broń klubowa</a> — ewidencja, przydziały, zwroty</li>
        <li><a href="<?= url('shooting/ammo') ?>">Amunicja</a> — stany magazynowe</li>
        <li><a href="<?= url('shooting/licenses') ?>">Licencje PZSS</a> — daty ważności, alerty</li>
        <li><a href="<?= url('shooting/judges') ?>">Sędziowie</a> — kadra, klasy sędziowskie</li>
    </ul>
</div>
