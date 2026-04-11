<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('club/smtp/save') ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card p-3">
                <h5>SMTP</h5>
                <div class="mb-2 form-check">
                    <input type="checkbox" name="smtp_enabled" value="1" id="smtp_en" class="form-check-input" <?= !empty($smtp['enabled']) && $smtp['enabled'] === '1' ? 'checked' : '' ?>>
                    <label for="smtp_en" class="form-check-label">Włącz własny SMTP klubu (wyłączony = fallback do globalnego)</label>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Host SMTP</label>
                    <input type="text" name="smtp_host" value="<?= View::e($smtp['host']) ?>" class="form-control">
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">Port</label>
                        <input type="number" name="smtp_port" value="<?= View::e($smtp['port']) ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Szyfrowanie</label>
                        <select name="smtp_secure" class="form-select">
                            <?php foreach (['none','ssl','tls'] as $s): ?>
                                <option value="<?= $s ?>" <?= $smtp['secure'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Login</label>
                    <input type="text" name="smtp_user" value="<?= View::e($smtp['user']) ?>" class="form-control">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Hasło</label>
                    <input type="password" name="smtp_pass_enc" value="<?= View::e($smtp['pass']) ?>" class="form-control">
                </div>
                <div class="mb-2">
                    <label class="form-label small">E-mail nadawcy</label>
                    <input type="email" name="smtp_from_email" value="<?= View::e($smtp['from_email']) ?>" class="form-control">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Nazwa nadawcy</label>
                    <input type="text" name="smtp_from_name" value="<?= View::e($smtp['from_name']) ?>" class="form-control">
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3">
                <h5>SMS</h5>
                <div class="mb-2">
                    <label class="form-label small">Dostawca</label>
                    <select name="sms_provider" class="form-select">
                        <option value="log"    <?= $sms['provider']==='log'?'selected':'' ?>>log (tylko zapis do pliku — tryb dev)</option>
                        <option value="smsapi" <?= $sms['provider']==='smsapi'?'selected':'' ?>>SMSAPI.pl</option>
                        <option value="twilio" <?= $sms['provider']==='twilio'?'selected':'' ?>>Twilio</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Klucz API</label>
                    <input type="password" name="sms_api_key" value="<?= View::e($sms['api_key']) ?>" class="form-control">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Nadawca (sender)</label>
                    <input type="text" name="sms_from" value="<?= View::e($sms['from']) ?>" class="form-control" maxlength="11">
                    <small class="text-muted">SMSAPI: tekst ≤11 znaków. Twilio: numer w formacie +48...</small>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-4">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz konfigurację</button>
    </div>
</form>
