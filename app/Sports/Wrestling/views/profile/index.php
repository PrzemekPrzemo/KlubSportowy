<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">
            <i class="bi bi-people-fill text-primary me-2"></i>
            Profil zapasnika — <?= View::e(($member['last_name'] ?? '') . ' ' . ($member['first_name'] ?? '')) ?>
        </h4>
        <div class="small text-muted">#<?= View::e($member['member_number'] ?? '—') ?></div>
    </div>
    <a href="<?= url('wrestling/results') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Wyniki
    </a>
</div>

<?php $p = $profile ?? []; $selectedStyles = $p['styles_list'] ?? []; ?>
<form method="POST" action="<?= url('wrestling/profile/' . (int)$member['id'] . '/update') ?>" class="card shadow-sm mb-4">
    <?= csrf_field() ?>
    <div class="card-header"><i class="bi bi-pencil-square me-1"></i> Edycja profilu</div>
    <div class="card-body row g-3">
        <div class="col-md-6">
            <label class="form-label">Style (multi-select)</label>
            <?php foreach ($styles as $code => $label): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="style_<?= View::e($code) ?>"
                        name="styles[]" value="<?= View::e($code) ?>"
                        <?= in_array($code, $selectedStyles, true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="style_<?= View::e($code) ?>"><?= View::e($label) ?></label>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="col-md-3">
            <label class="form-label">Aktualna waga (kg)</label>
            <input type="number" min="0" step="0.01" class="form-control" name="current_weight_kg"
                value="<?= View::e($p['current_weight_kg'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Kategoria wagowa</label>
            <input type="text" maxlength="50" class="form-control" name="current_weight_class"
                value="<?= View::e($p['current_weight_class'] ?? '') ?>" placeholder="np. -74">
        </div>
        <div class="col-md-3">
            <label class="form-label">Punkty rankingowe</label>
            <input type="number" min="0" class="form-control" name="rank_points"
                value="<?= (int)($p['rank_points'] ?? 0) ?>">
        </div>
    </div>
    <div class="card-footer text-end">
        <button class="btn btn-success"><i class="bi bi-save me-1"></i> Zapisz profil</button>
    </div>
</form>

<!-- Statystyki techniczne -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body">
        <div class="text-muted small">Takedowns</div><h3 class="mb-0"><?= (int)($stats['takedowns'] ?? 0) ?></h3>
    </div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body">
        <div class="text-muted small">Exposures</div><h3 class="mb-0"><?= (int)($stats['exposures'] ?? 0) ?></h3>
    </div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body">
        <div class="text-muted small">Escapes</div><h3 class="mb-0"><?= (int)($stats['escapes'] ?? 0) ?></h3>
    </div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center bg-dark text-white"><div class="card-body">
        <div class="small opacity-75">Tech. fall / Pin</div>
        <h3 class="mb-0 font-monospace"><?= (int)($stats['technical_falls'] ?? 0) ?> / <?= (int)($stats['pins'] ?? 0) ?></h3>
    </div></div></div>
</div>

<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-trophy me-1"></i> Wyniki zawodow</div>
    <div class="card-body p-0">
        <?php if (empty($results)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak wynikow.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>Data</th><th>Zawody</th><th>Styl</th><th>Kat. waga</th><th>Miejsce</th></tr></thead>
                    <tbody>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td class="small"><?= View::e($r['competition_date']) ?></td>
                            <td><strong><?= View::e($r['competition_name']) ?></strong></td>
                            <td class="small"><?= View::e($styles[$r['style']] ?? $r['style']) ?></td>
                            <td class="small"><?= View::e($r['weight_class'] ?? '—') ?></td>
                            <td><?php if ($r['placement']): ?><span class="badge bg-primary">#<?= (int)$r['placement'] ?></span><?php else: ?>—<?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
