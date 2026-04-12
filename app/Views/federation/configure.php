<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('federation/configure/save') ?>">
    <?= csrf_field() ?>
    <div class="alert alert-info small">
        Credentiale są przechowywane w <code>club_settings</code> per-klub.
        Hasła są przechowywane w bazie — w produkcji zalecane szyfrowanie.
        Puste pola nie nadpisują istniejącej konfiguracji.
    </div>

    <div class="row g-3">
        <?php foreach ($configs as $code => $cfg): $lcode = strtolower($code); ?>
            <div class="col-md-6">
                <div class="card p-3">
                    <h5><?= View::e($code) ?></h5>
                    <div class="mb-2">
                        <label class="form-label small">Login do portalu</label>
                        <input type="text" name="<?= $lcode ?>_login" value="<?= View::e($cfg['login']) ?>" class="form-control form-control-sm">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Hasło <small class="text-muted">(zostaw puste jeśli nie zmieniasz)</small></label>
                        <input type="password" name="<?= $lcode ?>_pass" class="form-control form-control-sm">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Klucz API</label>
                        <input type="text" name="<?= $lcode ?>_api_key" value="<?= View::e($cfg['api_key']) ?>" class="form-control form-control-sm">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small">Nr klubu w federacji</label>
                        <input type="text" name="<?= $lcode ?>_club_id" value="<?= View::e($cfg['club_id']) ?>" class="form-control form-control-sm">
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz konfigurację</button>
        <a href="<?= url('federation') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
