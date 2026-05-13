<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-toggles2"></i> Feature flags klubu: <?= View::e($club['name']) ?></h4>
        <div class="text-muted small">
            Plan klubu: <strong><?= View::e($planName ?: '—') ?></strong>
            <?php if ($planCode !== ''): ?><code>(<?= View::e($planCode) ?>)</code><?php endif; ?>
            · Override-y mają pierwszeństwo nad domyślną wartością z planu.
        </div>
    </div>
    <a href="<?= url('admin/platform/feature-flags') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Wszystkie flagi
    </a>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= View::e($flashError) ?></div>
<?php endif; ?>
<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
<?php endif; ?>

<div class="card">
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th>Flaga</th>
                <th class="text-center">Stan</th>
                <th class="text-center">Źródło</th>
                <th>Override</th>
                <th class="text-end">Akcje</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($flagsState as $f): ?>
            <?php $ov = $overrides[$f['code']] ?? null; ?>
            <tr>
                <td>
                    <strong><?= View::e($f['name']) ?></strong>
                    <code class="text-muted small d-block"><?= View::e($f['code']) ?></code>
                    <?php if (!empty($f['description'])): ?>
                        <div class="text-muted small mt-1"><?= View::e($f['description']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if ($f['enabled']): ?>
                        <span class="badge bg-success"><i class="bi bi-check2"></i> ON</span>
                    <?php else: ?>
                        <span class="badge bg-secondary"><i class="bi bi-x"></i> OFF</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if ($f['source'] === 'override'): ?>
                        <span class="badge bg-warning text-dark" title="Override per-klub aktywny">override</span>
                    <?php else: ?>
                        <span class="badge bg-light text-muted border">plan</span>
                    <?php endif; ?>
                </td>
                <td class="small">
                    <?php if ($ov !== null): ?>
                        <?php if (!empty($ov['reason'])): ?>
                            <div><em><?= View::e($ov['reason']) ?></em></div>
                        <?php endif; ?>
                        <?php if (!empty($ov['expires_at'])): ?>
                            <div class="text-muted">Wygasa: <?= View::e($ov['expires_at']) ?></div>
                        <?php else: ?>
                            <div class="text-muted">Trwale</div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-end" style="min-width:280px">
                    <button type="button" class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="collapse" data-bs-target="#ovForm_<?= View::e($f['code']) ?>">
                        <i class="bi bi-pencil"></i> Ustaw override
                    </button>
                    <?php if ($ov !== null): ?>
                        <form method="POST" action="<?= url('admin/platform/feature-flags/clear') ?>"
                              class="d-inline" onsubmit="return confirm('Usunąć override?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="club_id" value="<?= (int)$club['id'] ?>">
                            <input type="hidden" name="feature_code" value="<?= View::e($f['code']) ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                <i class="bi bi-x-circle"></i> Usuń
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <tr class="collapse" id="ovForm_<?= View::e($f['code']) ?>">
                <td colspan="5" class="bg-light">
                    <form method="POST" action="<?= url('admin/platform/feature-flags/override') ?>"
                          class="row g-2 align-items-end p-2 m-0">
                        <?= csrf_field() ?>
                        <input type="hidden" name="club_id" value="<?= (int)$club['id'] ?>">
                        <input type="hidden" name="feature_code" value="<?= View::e($f['code']) ?>">
                        <div class="col-md-2">
                            <label class="form-label small mb-0">Stan</label>
                            <select name="enabled" class="form-select form-select-sm">
                                <option value="1" <?= ($ov && $ov['enabled']) ? 'selected' : '' ?>>Włącz (ON)</option>
                                <option value="0" <?= ($ov && !$ov['enabled']) ? 'selected' : '' ?>>Wyłącz (OFF)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-0">Powód (opcjonalnie)</label>
                            <input type="text" name="reason" class="form-control form-control-sm"
                                   maxlength="255" placeholder="np. trial Pro, promocja Q1"
                                   value="<?= View::e($ov['reason'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-0">Wygasa (opcjonalnie)</label>
                            <input type="date" name="expires_at" class="form-control form-control-sm"
                                   value="<?= View::e(!empty($ov['expires_at']) ? substr((string)$ov['expires_at'], 0, 10) : '') ?>">
                        </div>
                        <div class="col-md-3 text-end">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-check2"></i> ZAPISZ OVERRIDE
                            </button>
                        </div>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($flagsState)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Brak flag w katalogu — uruchom migrację 056.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-3 text-muted small">
    <strong>Legenda:</strong>
    <span class="badge bg-warning text-dark">override</span> — wartość ustawiona ręcznie dla tego klubu ·
    <span class="badge bg-light text-muted border">plan</span> — wartość domyślna z planu klubu.
</div>
