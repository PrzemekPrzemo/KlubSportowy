<?php use App\Helpers\View; ?>

<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="display-4 fw-bold mb-3">Zaloz klub sportowy online w 5 minut</h1>
                <p class="lead mb-4">Pelne zarzadzanie czlonkami, skladkami, treningami i sekcjami — w jednej platformie. Bez wdrozenia, bez umowy, bez karty kredytowej.</p>
                <a href="<?= url('trial/start') ?>" class="btn btn-light btn-lg fw-semibold">
                    <i class="bi bi-rocket-takeoff me-2"></i>Zaloz konto trial (30 dni za darmo)
                </a>
                <p class="small mt-3 mb-0 opacity-75">Bez karty kredytowej. Pelny dostep do wszystkich funkcji przez 30 dni.</p>
            </div>
            <div class="col-lg-5 text-center d-none d-lg-block">
                <i class="bi bi-trophy" style="font-size:10rem;opacity:.25;"></i>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Wszystko czego potrzebuje nowoczesny klub</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card card h-100 p-4">
                    <i class="bi bi-people-fill text-primary mb-2" style="font-size:2rem;"></i>
                    <h5>Czlonkowie i skladki</h5>
                    <p class="text-muted mb-0">Rejestr czlonkow, automatyczne przypisanie skladek, przypomnienia o platnosciach.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card h-100 p-4">
                    <i class="bi bi-calendar3 text-success mb-2" style="font-size:2rem;"></i>
                    <h5>Treningi i wydarzenia</h5>
                    <p class="text-muted mb-0">Harmonogramy, zapisy online, frekwencja, Google Calendar sync.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card h-100 p-4">
                    <i class="bi bi-trophy-fill text-warning mb-2" style="font-size:2rem;"></i>
                    <h5>Multi-sport</h5>
                    <p class="text-muted mb-0">Pilka nozna, strzelectwo, koszykowka, judo i wiele innych — w jednym klubie.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card h-100 p-4">
                    <i class="bi bi-credit-card text-info mb-2" style="font-size:2rem;"></i>
                    <h5>Platnosci online</h5>
                    <p class="text-muted mb-0">Tpay, Przelewy24, BLIK. Skladki, wpisowe, faktury VAT.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card h-100 p-4">
                    <i class="bi bi-shield-check text-danger mb-2" style="font-size:2rem;"></i>
                    <h5>RODO compliant</h5>
                    <p class="text-muted mb-0">Zgody marketingowe, eksport danych, retention policy, audit log.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card h-100 p-4">
                    <i class="bi bi-phone text-secondary mb-2" style="font-size:2rem;"></i>
                    <h5>Portal czlonka</h5>
                    <p class="text-muted mb-0">Aplikacja mobilna, samoobsluga rodzicow, powiadomienia push.</p>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="<?= url('trial/start') ?>" class="btn btn-primary btn-lg">
                Zaczynamy! <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>
