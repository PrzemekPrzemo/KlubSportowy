<?php
use App\Helpers\View;
?>
<div class="card shadow-sm" style="max-width:500px; width:100%">
    <div class="card-body p-4">
        <div class="text-center mb-3">
            <i class="bi bi-shield-lock text-primary" style="font-size:3rem"></i>
        </div>
        <h4 class="card-title text-center mb-3">Rejestracja klubów wstrzymana</h4>
        <p class="text-muted">
            Nowe kluby w <strong><?= View::e($appName) ?></strong> są tworzone wyłącznie
            przez administratora platformy — to gwarantuje weryfikację organizacji
            i poprawne skonfigurowanie planu subskrypcji.
        </p>
        <p class="text-muted small">
            Jeżeli chcesz uruchomić swój klub na ClubDesk, napisz do nas — odpowiemy
            w ciągu 24 godzin i wyślemy link do utworzenia konta.
        </p>
        <div class="d-grid gap-2 mt-4">
            <a href="mailto:<?= View::e($contactEmail) ?>?subject=Chcę uruchomić klub w ClubDesk"
               class="btn btn-primary">
                <i class="bi bi-envelope me-1"></i> Napisz do nas: <?= View::e($contactEmail) ?>
            </a>
            <a href="<?= url('auth/login') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Wróć do logowania
            </a>
        </div>
        <?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
            <div class="alert alert-warning small mt-3 mb-0"><?= View::e($flash) ?></div>
        <?php endif; ?>
    </div>
</div>
