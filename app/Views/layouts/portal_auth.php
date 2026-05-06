<?php use App\Helpers\View; ?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title ?? 'Portal zawodnika') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #EE2C28; min-height:100vh; display:flex; align-items:center; justify-content:center; font-family: 'Poppins', system-ui, sans-serif; }
        .portal-card { background:#fff; border-radius:12px; padding:2.5rem; max-width:420px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,.25); }
        .portal-brand { text-align:center; margin-bottom:1.5rem; }
        .portal-brand h1 { font-size:1.4rem; color:#232232; margin:.5rem 0 0; font-weight:700; }
    </style>
</head>
<body>
<div class="portal-card">
    <div class="portal-brand">
        <img src="<?= View::e(system_logo('color')) ?>" alt="ClubDesk" style="height:48px; margin-bottom:.5rem;">
        <h1>Portal zawodnika</h1>
        <small><?= View::e($appName ?? 'ClubDesk') ?></small>
    </div>
    <?php foreach (['flashError'=>'danger','flashSuccess'=>'success'] as $k => $cls): ?>
        <?php if (!empty($$k)): ?>
            <div class="alert alert-<?= $cls ?>"><?= View::e($$k) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>
    <?= $content ?? '' ?>
</div>
</body>
</html>
