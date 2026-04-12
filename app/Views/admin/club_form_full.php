<?php use App\Helpers\View; ?>
<?php
$action = $club
    ? url('admin/clubs/' . (int)$club['id'] . '/edit-full')
    : url('admin/clubs/create-full');
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>

    <!-- Section: Dane klubu -->
    <h5 class="mb-3"><i class="bi bi-building"></i> Dane klubu</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <label class="form-label">Nazwa klubu *</label>
            <input type="text" name="name" value="<?= View::e($club['name'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Skrót</label>
            <input type="text" name="short_name" value="<?= View::e($club['short_name'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Miasto</label>
            <input type="text" name="city" value="<?= View::e($club['city'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">NIP</label>
            <input type="text" name="nip" value="<?= View::e($club['nip'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <?php if ($club): ?>
            <div class="form-check">
                <input type="checkbox" name="is_active" value="1" id="active" class="form-check-input"
                       <?= ($club['is_active'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="active">Aktywny</label>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <label class="form-label">E-mail klubu</label>
            <input type="email" name="club_email" value="<?= View::e($club['email'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Telefon klubu</label>
            <input type="text" name="club_phone" value="<?= View::e($club['phone'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Adres</label>
            <textarea name="address" class="form-control" rows="2"><?= View::e($club['address'] ?? '') ?></textarea>
        </div>
    </div>

    <hr>

    <!-- Section: Sporty -->
    <h5 class="mb-3"><i class="bi bi-trophy"></i> Sporty</h5>
    <div class="row g-2 mb-4">
        <?php foreach ($sports as $sport): ?>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <div class="form-check">
                <input type="checkbox" name="sport_ids[]" value="<?= (int)$sport['id'] ?>"
                       id="sport_<?= (int)$sport['id'] ?>" class="form-check-input"
                       <?= in_array((int)$sport['id'], $clubSportIds) ? 'checked' : '' ?>>
                <label class="form-check-label" for="sport_<?= (int)$sport['id'] ?>">
                    <?php if (!empty($sport['icon'])): ?>
                        <i class="bi bi-<?= View::e($sport['icon']) ?>"></i>
                    <?php endif; ?>
                    <?= View::e($sport['name']) ?>
                </label>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <hr>

    <!-- Section: Plan subskrypcyjny -->
    <h5 class="mb-3"><i class="bi bi-credit-card"></i> Plan subskrypcyjny</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Plan</label>
            <select name="plan_id" class="form-select">
                <option value="">-- wybierz --</option>
                <?php foreach ($plans as $plan): ?>
                <option value="<?= (int)$plan['id'] ?>"
                    <?= (int)($subscription['plan_id'] ?? 0) === (int)$plan['id'] ? 'selected' : '' ?>>
                    <?= View::e($plan['name']) ?> (<?= number_format((float)$plan['price_monthly'], 2, ',', ' ') ?> zł/mies.)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Override: max zawodników</label>
            <input type="number" name="max_members_override" class="form-control" min="0"
                   value="<?= View::e($subscription['max_members_override'] ?? '') ?>">
            <small class="text-muted">Puste = limit z planu</small>
        </div>
        <div class="col-md-4">
            <label class="form-label">Override: max sportów</label>
            <input type="number" name="max_sports_override" class="form-control" min="0"
                   value="<?= View::e($subscription['max_sports_override'] ?? '') ?>">
            <small class="text-muted">Puste = limit z planu</small>
        </div>
        <div class="col-md-6">
            <label class="form-label">Custom features (JSON)</label>
            <textarea name="custom_features" class="form-control" rows="2"
                      placeholder='{"feature": true}'><?= View::e($subscription['custom_features'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Notatki admina</label>
            <textarea name="admin_notes" class="form-control" rows="2"><?= View::e($subscription['admin_notes'] ?? '') ?></textarea>
        </div>
    </div>

    <hr>

    <!-- Section: Administrator klubu -->
    <h5 class="mb-3"><i class="bi bi-person-badge"></i> Administrator klubu</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">E-mail administratora</label>
            <input type="email" name="admin_email" class="form-control" placeholder="admin@klub.pl">
        </div>
        <div class="col-md-4">
            <label class="form-label">Imię i nazwisko</label>
            <input type="text" name="admin_name" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Hasło</label>
            <input type="password" name="admin_password" class="form-control" minlength="8">
            <small class="text-muted">Min. 8 znaków<?= $club ? ' (puste = bez zmian)' : '' ?></small>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('admin/clubs') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
