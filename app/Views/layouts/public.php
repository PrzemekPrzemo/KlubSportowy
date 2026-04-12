<?php use App\Helpers\View; ?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title ?? 'KlubSportowy') ?> — <?= View::e($appName ?? 'KlubSportowy') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; font-family: system-ui, -apple-system, sans-serif; }
        .pub-hero { background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); color: #fff; padding: 3rem 0; }
        .pub-hero h1 { font-weight: 700; }
        .sport-badge { display:inline-block; padding: .2rem .5rem; border-radius: .3rem; font-size: .75rem; color:#fff; }
        .card { border: 0; box-shadow: 0 1px 3px rgba(0,0,0,.05); }
        footer { background: #212529; color: #adb5bd; padding: 1.5rem 0; font-size: .85rem; }
        footer a { color: #cdd6f4; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= url('pub') ?>">
            <i class="bi bi-trophy"></i> <?= View::e($appName ?? 'KlubSportowy') ?>
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
    <div class="container text-center">
        &copy; <?= date('Y') ?> <?= View::e($appName ?? 'KlubSportowy') ?>. Wszystkie prawa zastrzezone.
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
