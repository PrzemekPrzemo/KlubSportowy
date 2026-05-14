<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-toggles2"></i> Feature flags — katalog</h4>
        <div class="text-muted small">
            Master katalog feature flags (boolean włącz/wyłącz per plan). Override-y per-klub
            ustaw w widoku <em>Feature flags klubu</em> (link z listy klubów).
        </div>
    </div>
</div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle"></i>
    <strong>Czym to różni się od addons?</strong>
    Addons (np. <em>+10 zawodników</em>) to <strong>boostery limitów ilościowych</strong>.
    Feature flags to <strong>boolean włącz/wyłącz</strong> dla funkcjonalności
    (np. PDF export, SMS, whitelabel). Edycja katalogu (dodawanie nowych flag) odbywa się
    obecnie przez migracje SQL — UI do CRUD katalogu zostanie dodane w kolejnej iteracji.
</div>

<div class="card">
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th>Kod</th>
                <th>Nazwa</th>
                <th>Kategoria</th>
                <th>Default in plan</th>
                <th class="text-center">Aktywna</th>
                <th class="text-end">Akcje</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($flags as $f): ?>
            <tr>
                <td><code><?= View::e($f['code']) ?></code></td>
                <td>
                    <strong><?= View::e($f['name']) ?></strong>
                    <?php if (!empty($f['description'])): ?>
                        <div class="text-muted small"><?= View::e($f['description']) ?></div>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-secondary"><?= View::e($f['category']) ?></span></td>
                <td>
                    <?php foreach ($planCodes as $pc): ?>
                        <?php $on = !empty($f['default_in_plan_parsed'][$pc]); ?>
                        <span class="badge <?= $on ? 'bg-success' : 'bg-light text-muted border' ?> me-1"
                              title="<?= $on ? 'Włączone domyślnie w planie' : 'Wyłączone domyślnie w planie' ?>">
                            <?= View::e($pc) ?>
                        </span>
                    <?php endforeach; ?>
                </td>
                <td class="text-center">
                    <?php if ($f['is_active']): ?>
                        <span class="badge bg-success">tak</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">nie</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <a href="<?= url('admin/clubs') ?>" class="btn btn-sm btn-outline-primary"
                       title="Przejdź do listy klubów aby ustawić override">
                        <i class="bi bi-buildings"></i> Override per klub
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($flags)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak flag w katalogu — uruchom migrację 056.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-3 text-muted small">
    <strong>API w kodzie:</strong>
    <code>Feature::enabled('pdf_export')</code>,
    <code>Feature::requireEnabled('sms_notifications')</code>,
    <code>Feature::list($clubId)</code>.
</div>
