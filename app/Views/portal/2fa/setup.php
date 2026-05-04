<?php use App\Helpers\View; ?>

<div class="container" style="max-width:720px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="bi bi-shield-lock text-success me-2"></i>Włącz uwierzytelnianie dwuetapowe (2FA)</h3>
        <a href="<?= url('portal/profile') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Profil
        </a>
    </div>

    <div class="alert alert-info small">
        <i class="bi bi-info-circle me-1"></i>
        Twoje konto zawiera dane medyczne i wrażliwe. Rekomendujemy włączenie 2FA.
        Po włączeniu logowanie wymagać będzie kodu z aplikacji (Google Authenticator, Authy, Microsoft Authenticator).
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="mb-3">Krok 1: Zeskanuj QR w aplikacji autentyfikatora</h5>

            <div class="text-center mb-3">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?= urlencode($qrData) ?>"
                     alt="QR code" class="border p-2 bg-white">
            </div>

            <div class="text-center small text-muted mb-4">
                Lub wpisz sekret ręcznie:
                <code class="d-block mt-1 fs-6"><?= View::e(chunk_split($secret, 4, ' ')) ?></code>
            </div>

            <hr>

            <h5 class="mb-3">Krok 2: Wpisz 6-cyfrowy kod z aplikacji</h5>
            <form method="POST" action="<?= url('portal/2fa/confirm') ?>">
                <?= csrf_field() ?>
                <div class="input-group input-group-lg mb-3">
                    <input type="text" name="code" class="form-control text-center font-monospace"
                           placeholder="000 000" maxlength="6" pattern="\d{6}"
                           autocomplete="off" autofocus required>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg"></i> Potwierdź
                    </button>
                </div>
                <p class="text-muted small mb-0">
                    Po potwierdzeniu kodu otrzymasz 10 kodów zapasowych — zapisz je bezpiecznie na wypadek utraty telefonu.
                </p>
            </form>
        </div>
    </div>
</div>
