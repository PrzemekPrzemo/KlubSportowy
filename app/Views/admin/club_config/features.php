<?php use App\Helpers\View; ?>

<div class="mb-3">
    <a href="<?= url('admin/clubs') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Wróć do klubów</a>
    <a href="<?= url('admin/clubs/' . (int)$club['id'] . '/config') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-sliders"></i> Konfiguracja</a>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-toggles"></i> Feature flags: <?= View::e($club['name'] ?? '') ?></h5>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('admin/clubs/' . (int)$club['id'] . '/features/save') ?>">
            <?= csrf_field() ?>

            <p class="text-muted mb-4">Włącz lub wyłącz moduły dla tego klubu. Wyłączony moduł nie będzie widoczny w nawigacji ani dostępny dla użytkowników.</p>

            <?php foreach ($flags as $key => $flag): ?>
                <div class="form-check form-switch mb-3 p-3 border rounded">
                    <input class="form-check-input ms-0 me-2" type="checkbox"
                           name="module_<?= View::e($key) ?>" id="module_<?= View::e($key) ?>"
                           value="1" <?= $flag['enabled'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="module_<?= View::e($key) ?>">
                        <strong><?= View::e($flag['label']) ?></strong>
                        <div class="text-muted small"><?= View::e($flag['desc']) ?></div>
                    </label>
                </div>
            <?php endforeach; ?>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Zapisz feature flags</button>
            </div>
        </form>
    </div>
</div>
