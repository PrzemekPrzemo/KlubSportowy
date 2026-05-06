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
    <style>
        body { font-family: 'Poppins', system-ui, -apple-system, sans-serif; font-size: 16px; color: #232232; }
        .hero-section { background: #EE2C28; color: #fff; padding: 5rem 0; }
        .feature-card { border: 0; box-shadow: 0 2px 8px rgba(0,0,0,.08); transition: transform .2s; }
        .feature-card:hover { transform: translateY(-4px); }
        .pricing-card { border: 0; box-shadow: 0 2px 12px rgba(0,0,0,.1); }
        .pricing-card.featured { border: 2px solid #EE2C28; }
        footer { background: #191421; color: #adb5bd; padding: 2rem 0; font-size: .85rem; }
        footer a { color: #F9C6CE; text-decoration: none; }
        footer a:hover { color: #fff; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: #232232;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('') ?>">
            <img src="<?= View::e(system_logo('white')) ?>" alt="CD" style="height:36px;">
            <span style="font-weight:700;">ClubDesk</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landingNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="landingNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item">
                    <a class="btn btn-outline-light btn-sm" href="<?= url('auth/login') ?>">
                        <i class="bi bi-box-arrow-in-right"></i> Zaloguj
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-primary btn-sm" href="<?= url('register') ?>">
                        <i class="bi bi-person-plus"></i> Rejestracja
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main>
    <?= $content ?? '' ?>
</main>

<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                &copy; <?= date('Y') ?> ClubDesk. Wszystkie prawa zastrzezone.
            </div>
            <div class="col-md-6 text-md-end">
                <a href="<?= url('auth/login') ?>">Logowanie</a>
                <span class="mx-2">|</span>
                <a href="<?= url('register') ?>">Rejestracja</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('js/cookie-consent.js') ?>"></script>
</body>
</html>
