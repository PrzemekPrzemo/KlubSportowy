<?php
use App\Helpers\View;
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="text-center mb-4">
                <div class="display-1">&#127881;</div>
                <h1 class="fw-bold mb-2">Witaj w ClubDesk!</h1>
                <p class="lead text-muted">Twoj klub jest gotowy. Masz <strong>30 dni</strong> bezplatnego dostepu do wszystkich funkcji.</p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <a href="<?= url('members/create') ?>" class="card h-100 text-decoration-none p-4 shadow-sm border-0">
                        <i class="bi bi-person-plus text-primary mb-2" style="font-size:2.2rem;"></i>
                        <h6 class="mb-1">Dodaj pierwszego czlonka</h6>
                        <small class="text-muted">Zarejestruj zawodnika lub rodzica</small>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?= url('club/gateways') ?>" class="card h-100 text-decoration-none p-4 shadow-sm border-0">
                        <i class="bi bi-credit-card text-success mb-2" style="font-size:2.2rem;"></i>
                        <h6 class="mb-1">Skonfiguruj platnosci</h6>
                        <small class="text-muted">Tpay, Przelewy24, BLIK</small>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?= url('club/users/create') ?>" class="card h-100 text-decoration-none p-4 shadow-sm border-0">
                        <i class="bi bi-person-badge text-warning mb-2" style="font-size:2.2rem;"></i>
                        <h6 class="mb-1">Zapros trenera</h6>
                        <small class="text-muted">Dodaj kolejnego uzytkownika do zespolu</small>
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5><i class="bi bi-list-check me-1"></i> Setup klubu — 5 zadan</h5>
                    <ol class="list-unstyled mb-0">
                        <li class="py-1"><span class="text-success me-1"><i class="bi bi-check-circle-fill"></i></span> Klub utworzony</li>
                        <li class="py-1"><span class="text-success me-1"><i class="bi bi-check-circle-fill"></i></span> Pierwsze stawki skladek</li>
                        <li class="py-1"><span class="text-muted me-1"><i class="bi bi-circle"></i></span> Zaproszenie trenerow</li>
                        <li class="py-1"><span class="text-muted me-1"><i class="bi bi-circle"></i></span> Import zawodnikow z CSV</li>
                        <li class="py-1"><span class="text-muted me-1"><i class="bi bi-circle"></i></span> Konfiguracja bramki platnosci</li>
                    </ol>
                </div>
            </div>

            <div class="text-center">
                <a href="<?= url('dashboard') ?>" class="btn btn-primary btn-lg me-2">
                    Przejdz do dashboardu <i class="bi bi-arrow-right ms-1"></i>
                </a>
                <a href="<?= url('help/getting-started') ?>" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-book me-1"></i> Przewodnik
                </a>
            </div>
        </div>
    </div>
</div>
