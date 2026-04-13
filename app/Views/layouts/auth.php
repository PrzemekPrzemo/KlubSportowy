<?php use App\Helpers\View; ?><!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($title ?? 'Logowanie') ?> — <?= View::e($appName ?? 'ClubDesk') ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #EE2C28 0%, #6610f2 100%); min-height: 100vh; display:flex; align-items:center; justify-content:center; font-family: 'Poppins', system-ui, sans-serif; }
        .auth-card { background:#fff; border-radius: 12px; padding: 2.5rem; max-width: 440px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.25); }
        .auth-brand { text-align:center; margin-bottom: 1.5rem; }
        .auth-brand h1 { font-size: 1.8rem; color:#EE2C28; margin:0; }
        .auth-brand small { color:#6c757d; }
    </style>
</head>
<body>
<div class="auth-card">
    <div class="auth-brand">
        <h1><strong style="color:#EE2C28">CD</strong> <?= View::e($appName ?? 'ClubDesk') ?></h1>
        <small><?= __('auth.subtitle') ?></small>
    </div>

    <?php foreach (['flashError'=>'danger','flashSuccess'=>'success','flashWarning'=>'warning'] as $k => $cls): ?>
        <?php if (!empty($$k)): ?>
            <div class="alert alert-<?= $cls ?>"><?= View::e($$k) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?= $content ?? '' ?>
</div>
</body>
</html>
