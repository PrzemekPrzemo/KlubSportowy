<?php
use App\Helpers\View;
$action = $ad
    ? url('admin/ads/' . (int)$ad['id'] . '/update')
    : url('admin/ads/store');
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Tytul *</label>
            <input type="text" name="title" value="<?= View::e($ad['title'] ?? '') ?>" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Club ID (pusty = globalny)</label>
            <input type="number" name="club_id" value="<?= View::e($ad['club_id'] ?? '') ?>" class="form-control" min="1">
        </div>
        <div class="col-md-6">
            <label class="form-label">Sciezka obrazka</label>
            <input type="text" name="image_path" value="<?= View::e($ad['image_path'] ?? '') ?>" class="form-control" placeholder="/storage/ads/banner.png">
        </div>
        <div class="col-md-6">
            <label class="form-label">URL linku</label>
            <input type="url" name="link_url" value="<?= View::e($ad['link_url'] ?? '') ?>" class="form-control" placeholder="https://...">
        </div>
        <div class="col-md-4">
            <label class="form-label">Cel wyswietlania</label>
            <select name="target" class="form-select">
                <option value="club_panel" <?= ($ad['target'] ?? '') === 'club_panel' ? 'selected' : '' ?>>Panel klubu</option>
                <option value="member_portal" <?= ($ad['target'] ?? '') === 'member_portal' ? 'selected' : '' ?>>Portal zawodnika</option>
                <option value="public" <?= ($ad['target'] ?? '') === 'public' ? 'selected' : '' ?>>Strona publiczna</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Pozycja</label>
            <select name="position" class="form-select">
                <option value="top_banner" <?= ($ad['position'] ?? '') === 'top_banner' ? 'selected' : '' ?>>Gorny baner</option>
                <option value="sidebar" <?= ($ad['position'] ?? '') === 'sidebar' ? 'selected' : '' ?>>Sidebar</option>
                <option value="footer" <?= ($ad['position'] ?? '') === 'footer' ? 'selected' : '' ?>>Footer</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Minimalny plan</label>
            <input type="text" name="plan_min" value="<?= View::e($ad['plan_min'] ?? '') ?>" class="form-control" placeholder="np. basic">
        </div>
        <div class="col-md-4">
            <label class="form-label">Data poczatkowa</label>
            <input type="date" name="start_date" value="<?= View::e($ad['start_date'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Data koncowa</label>
            <input type="date" name="end_date" value="<?= View::e($ad['end_date'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
                <input type="checkbox" name="is_active" value="1" id="is_active" class="form-check-input"
                       <?= ($ad['is_active'] ?? 1) ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Aktywna</label>
            </div>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('admin/ads') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
