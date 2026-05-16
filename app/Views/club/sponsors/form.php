<?php
use App\Helpers\View;
$action = $sponsor
    ? url('club/sponsors/' . (int)$sponsor['id'] . '/update')
    : url('club/sponsors/store');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><?= View::e($title) ?></h2>
    <a href="<?= url('club/sponsors') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Powrót do listy
    </a>
</div>

<form method="POST" action="<?= $action ?>" enctype="multipart/form-data" class="card p-4">
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Nazwa sponsora *</label>
            <input type="text" name="name" required maxlength="200"
                   value="<?= View::e($sponsor['name'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Tier</label>
            <select name="tier" class="form-select">
                <?php foreach ($tiers as $k => $l): ?>
                    <option value="<?= View::e($k) ?>"
                            <?= ($sponsor['tier'] ?? 'partner') === $k ? 'selected' : '' ?>>
                        <?= View::e($l) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Osoba kontaktowa</label>
            <input type="text" name="contact_person" maxlength="200"
                   value="<?= View::e($sponsor['contact_person'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" maxlength="255"
                   value="<?= View::e($sponsor['email'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Telefon</label>
            <input type="text" name="phone" maxlength="50"
                   value="<?= View::e($sponsor['phone'] ?? '') ?>" class="form-control">
        </div>

        <div class="col-md-12">
            <label class="form-label">Strona WWW</label>
            <input type="url" name="website" maxlength="255" placeholder="https://..."
                   value="<?= View::e($sponsor['website'] ?? '') ?>" class="form-control">
        </div>

        <!-- Kontrakt -->
        <div class="col-12">
            <div class="border rounded p-3 bg-light">
                <h6 class="mb-3"><i class="bi bi-file-earmark-text me-1"></i> Kontrakt</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Wartość (PLN)</label>
                        <input type="number" step="0.01" min="0" name="contract_value"
                               value="<?= View::e($sponsor['contract_value'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Data startu</label>
                        <input type="date" name="contract_start"
                               value="<?= View::e($sponsor['contract_start'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Data końca</label>
                        <input type="date" name="contract_end"
                               value="<?= View::e($sponsor['contract_end'] ?? '') ?>" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <!-- Logo -->
        <div class="col-md-8">
            <label class="form-label">Logo (PNG/JPG/SVG, max 2 MB)</label>
            <input type="file" name="logo" accept=".png,.jpg,.jpeg,.webp,.svg" class="form-control">
            <?php if (!empty($sponsor['logo_path'])): ?>
                <div class="form-text">
                    Aktualne logo: <a href="<?= url($sponsor['logo_path']) ?>" target="_blank"><?= View::e($sponsor['logo_path']) ?></a>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <?php if (!empty($sponsor['logo_path'])): ?>
                <div class="text-center">
                    <img src="<?= url($sponsor['logo_path']) ?>" alt="logo"
                         style="max-height:80px;max-width:200px;background:#f5f5f7;padding:8px;border-radius:4px;">
                </div>
            <?php endif; ?>
        </div>

        <!-- Display flags -->
        <div class="col-12">
            <div class="border rounded p-3 bg-light">
                <h6 class="mb-3"><i class="bi bi-eye me-1"></i> Widoczność</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" id="dp" name="display_in_portal" value="1" class="form-check-input"
                                   <?= ($sponsor['display_in_portal'] ?? 1) ? 'checked' : '' ?>>
                            <label for="dp" class="form-check-label">W portalu zawodnika</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" id="de" name="display_in_emails" value="1" class="form-check-input"
                                   <?= ($sponsor['display_in_emails'] ?? 1) ? 'checked' : '' ?>>
                            <label for="de" class="form-check-label">W emailach</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Display weight <small>(mniejszy = wyżej)</small></label>
                        <input type="number" min="0" name="display_weight"
                               value="<?= (int)($sponsor['display_weight'] ?? 100) ?>" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" id="active" name="active" value="1" class="form-check-input"
                                   <?= ($sponsor['active'] ?? 1) ? 'checked' : '' ?>>
                            <label for="active" class="form-check-label">Aktywny</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label">Notatki wewnętrzne</label>
            <textarea name="notes" rows="3" class="form-control"><?= View::e($sponsor['notes'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('club/sponsors') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
