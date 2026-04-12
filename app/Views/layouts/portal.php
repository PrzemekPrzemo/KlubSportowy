<?php
use App\Helpers\Session;
use App\Helpers\View;
$flashSuccess = Session::getFlash('success');
$flashError   = Session::getFlash('error');
$memberName = Session::get('portal_member_name');
?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="/manifest.json">
    <title><?= View::e($title ?? 'Portal zawodnika') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: system-ui, sans-serif; background:#f0f2f5; }
        .portal-nav { background: linear-gradient(135deg,#198754,#0d6efd); color:#fff; padding:1rem 2rem; }
        .portal-nav a { color:#fff; text-decoration:none; margin-right: 1.5rem; }
        .portal-nav a:hover, .portal-nav a.active { text-decoration: underline; }
        .portal-container { max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
    </style>
</head>
<body>
<nav class="portal-nav">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <strong><i class="bi bi-person-badge"></i> Portal zawodnika</strong>
            <a href="<?= url('portal/dashboard') ?>" class="ms-4">Dashboard</a>
            <a href="<?= url('portal/profile') ?>">Mój profil</a>
            <a href="<?= url('portal/fees') ?>">Składki</a>
            <a href="<?= url('portal/payments') ?>">Opłać online</a>
            <a href="<?= url('portal/events') ?>">Wydarzenia</a>
        </div>
        <div>
            <span class="me-3 small"><i class="bi bi-person-circle"></i> <?= View::e($memberName ?? '') ?></span>
            <a href="<?= url('portal/logout') ?>"><i class="bi bi-box-arrow-right"></i> Wyloguj</a>
        </div>
    </div>
</nav>

<div class="portal-container">
    <?php if (\App\Helpers\Auth::isImpersonating() && \App\Helpers\Session::get('impersonating') === 'member'): ?>
        <div class="alert alert-warning d-flex justify-content-between align-items-center mb-3">
            <div>
                <i class="bi bi-person-fill-lock"></i>
                <strong>Impersonujesz zawodnika</strong> <?= View::e($memberName ?? '') ?>
            </div>
            <form method="POST" action="<?= url('impersonate/stop') ?>" class="m-0">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-warning"><i class="bi bi-arrow-return-left"></i> Powrót do admina</button>
            </form>
        </div>
    <?php endif; ?>

    <h2 class="mb-3"><?= View::e($title ?? '') ?></h2>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <?= $content ?? '' ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js').catch(function(err) {
    console.log('SW registration failed:', err);
  });
}
</script>
</body>
</html>
