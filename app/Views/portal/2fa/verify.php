<?php use App\Helpers\View; ?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title><?= View::e($title) ?> | <?= View::e($appName) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<div class="container" style="max-width:480px;padding-top:4rem;">
    <div class="text-center mb-4">
        <h3><i class="bi bi-shield-lock-fill text-primary"></i> Weryfikacja 2FA</h3>
        <p class="text-muted">Wpisz kod z aplikacji lub 8-znakowy kod zapasowy.</p>
    </div>

    <?php if (isset($_SESSION['_flash']['error'])): ?>
        <div class="alert alert-danger"><?= View::e($_SESSION['_flash']['error']) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= url('portal/2fa/verify') ?>">
                <?= csrf_field() ?>
                <label class="form-label">Kod 2FA</label>
                <input type="text" name="code" class="form-control form-control-lg text-center font-monospace"
                       placeholder="000 000" maxlength="8" autocomplete="off" autofocus required>
                <div class="d-grid mt-3">
                    <button class="btn btn-primary btn-lg"><i class="bi bi-check-lg"></i> Zweryfikuj</button>
                </div>
            </form>
            <div class="text-center mt-3">
                <a href="<?= url('portal/login') ?>" class="text-muted small">← Wróć do logowania</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
