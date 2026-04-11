<?php use App\Helpers\View; ?>
<div class="card p-4">
    <div class="alert alert-warning">
        <strong><i class="bi bi-exclamation-triangle"></i> WAŻNE:</strong>
        Zapisz te kody zapasowe w bezpiecznym miejscu. Każdy kod może być użyty tylko
        raz do zalogowania, jeśli nie masz dostępu do aplikacji 2FA.
    </div>
    <div class="row g-2">
        <?php foreach ($codes as $c): ?>
            <div class="col-md-6">
                <code class="d-block p-2 bg-light border rounded" style="font-size:1.1rem;"><?= View::e($c) ?></code>
            </div>
        <?php endforeach; ?>
    </div>
    <hr>
    <a href="<?= url('dashboard') ?>" class="btn btn-primary">
        <i class="bi bi-check2"></i> Zapisałem — przejdź do dashboardu
    </a>
</div>
