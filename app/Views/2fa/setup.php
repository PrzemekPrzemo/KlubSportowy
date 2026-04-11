<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-md-6">
        <div class="card p-4">
            <h5>1. Zeskanuj kod QR</h5>
            <p class="small text-muted">Użyj aplikacji Google Authenticator, Authy lub 1Password.</p>
            <div id="qrcode" class="text-center mb-3"></div>
            <p class="small text-muted">Lub wpisz ręcznie sekret: <code><?= View::e($secret) ?></code></p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-4">
            <h5>2. Potwierdź kod z aplikacji</h5>
            <form method="POST" action="<?= url('2fa/confirm') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">6-cyfrowy kod</label>
                    <input type="text" name="code" class="form-control form-control-lg text-center" maxlength="6" pattern="[0-9]{6}" required autofocus>
                </div>
                <button class="btn btn-primary w-100"><i class="bi bi-shield-check"></i> Włącz 2FA</button>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
QRCode.toCanvas(document.getElementById('qrcode'), <?= json_encode($otpUrl) ?>, { width: 220 });
</script>
