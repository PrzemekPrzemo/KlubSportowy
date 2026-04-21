<?php use App\Helpers\View; ?>

<div class="container" style="max-width:640px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="bi bi-key-fill text-warning me-2"></i>Kody zapasowe 2FA</h3>
        <a href="<?= url('portal/profile') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Profil
        </a>
    </div>

    <?php if (!empty($codes)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>Zapisz te kody bezpiecznie teraz!</strong> Każdego kodu można użyć tylko raz.
            Po opuszczeniu tej strony nie będziesz mógł zobaczyć ich ponownie — ale możesz je regenerować.
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row">
                    <?php foreach ($codes as $c): ?>
                        <div class="col-6 col-md-4 mb-2">
                            <code class="d-block text-center py-2 bg-light rounded fs-5 font-monospace">
                                <?= View::e($c) ?>
                            </code>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex gap-2 mt-3">
                    <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
                        <i class="bi bi-printer"></i> Drukuj
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="copyAll()">
                        <i class="bi bi-clipboard"></i> Kopiuj
                    </button>
                </div>
            </div>
        </div>
        <script>
        function copyAll() {
            const codes = <?= json_encode($codes) ?>;
            navigator.clipboard.writeText(codes.join('\n')).then(() => alert('Skopiowano!'));
        }
        </script>
    <?php else: ?>
        <div class="alert alert-info">
            Kody zapasowe zostały wygenerowane. Jeśli je zgubiłeś, wygeneruj nowe poniżej.
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5>Regeneruj kody zapasowe</h5>
            <p class="small text-muted">Stare kody zostaną unieważnione. Generuj gdy zgubisz lub zużyjesz zapasowe.</p>
            <form method="POST" action="<?= url('portal/2fa/backup-codes/regenerate') ?>" onsubmit="return confirm('Unieważnić obecne kody i wygenerować nowe?');">
                <?= csrf_field() ?>
                <button class="btn btn-warning">
                    <i class="bi bi-arrow-clockwise"></i> Wygeneruj nowe kody
                </button>
            </form>
        </div>
    </div>
</div>
