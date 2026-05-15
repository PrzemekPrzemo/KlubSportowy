<?php use App\Helpers\View; ?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title ?? 'ClubDesk') ?> — <?= View::e($appName ?? 'ClubDesk') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <?= $GLOBALS['__pageHead'] ?? '' ?>
    <style>
        body { background: #f8f9fa; font-family: 'Poppins', system-ui, -apple-system, sans-serif; font-size: 16px; color: #232232; }
        .pub-hero { background: #EE2C28; color: #fff; padding: 3rem 0; }
        .pub-hero h1 { font-weight: 700; }
        .sport-badge { display:inline-block; padding: .2rem .5rem; border-radius: .3rem; font-size: .75rem; color:#fff; }
        .card { border: 0; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
        footer { background: #191421; color: #adb5bd; padding: 1.5rem 0; font-size: .85rem; }
        footer a { color: #F9C6CE; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background: #232232;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('pub') ?>">
            <img src="<?= View::e(system_logo('white')) ?>" alt="CD" style="height:36px;">
            <span style="font-weight:700;"><?= View::e($appName ?? 'ClubDesk') ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#pubNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="pubNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('pub') ?>"><i class="bi bi-grid"></i> Kluby</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('auth/login') ?>"><i class="bi bi-box-arrow-in-right"></i> Logowanie</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main>
    <?= $content ?? '' ?>
</main>

<footer class="mt-5">
    <div class="container">
        <div class="row gy-3 align-items-center">
            <div class="col-md-7">
                <strong style="color:#fff;">Sendormeco Holding Sp. z o.o.</strong><br>
                <span class="small">NIP 5252866457 &middot; KRS 0000906110 &middot; ul. Złota 75A/7, 00-819 Warszawa</span>
            </div>
            <div class="col-md-5 text-md-end small">
                <a href="<?= url('legal/regulamin') ?>">Regulamin</a> &middot;
                <a href="<?= url('legal/polityka-prywatnosci') ?>">Polityka prywatności</a> &middot;
                <a href="<?= url('legal/cookies') ?>">Cookies</a> &middot;
                <a href="<?= url('legal/dpa') ?>">RODO/DPA</a> &middot;
                <a href="mailto:kontakt@clubdesk.pl">Kontakt</a>
            </div>
        </div>
        <hr style="border-color: rgba(255,255,255,.1);">
        <div class="text-center small">
            &copy; <?= date('Y') ?> <?= View::e($appName ?? 'ClubDesk') ?>. Wszystkie prawa zastrzeżone.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('js/cookie-consent.js') ?>"></script>
</body>
</html>
