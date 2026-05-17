<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">
            <i class="bi bi-slash-lg text-primary me-2"></i>
            Profil szermierza — <?= View::e(($member['last_name'] ?? '') . ' ' . ($member['first_name'] ?? '')) ?>
        </h4>
        <div class="small text-muted">#<?= View::e($member['member_number'] ?? '—') ?></div>
    </div>
    <a href="<?= url('fencing/results') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Wyniki
    </a>
</div>

<?php $p = $profile ?? []; $selectedWeapons = $p['weapons_list'] ?? []; ?>
<form method="POST" action="<?= url('fencing/profile/' . (int)$member['id'] . '/update') ?>" class="card shadow-sm mb-4">
    <?= csrf_field() ?>
    <div class="card-header"><i class="bi bi-pencil-square me-1"></i> Edycja profilu</div>
    <div class="card-body row g-3">
        <div class="col-md-6">
            <label class="form-label">Bronie (multi-select)</label>
            <?php foreach ($weapons as $code => $info): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="w_<?= View::e($code) ?>"
                        name="weapons[]" value="<?= View::e($code) ?>"
                        <?= in_array($code, $selectedWeapons, true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="w_<?= View::e($code) ?>">
                        <span class="badge" style="background:<?= $info['color'] ?>;color:#fff;"><?= View::e($info['label']) ?></span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="col-md-3">
            <label class="form-label">FIE rank</label>
            <input type="number" min="1" class="form-control" name="fie_rank"
                value="<?= View::e($p['fie_rank'] ?? '') ?>" placeholder="np. 124">
        </div>
        <div class="col-md-3">
            <label class="form-label">Reka</label>
            <select name="hand" class="form-select">
                <?php foreach ($hands as $code => $label): ?>
                    <option value="<?= View::e($code) ?>" <?= ($p['hand'] ?? 'right') === $code ? 'selected' : '' ?>>
                        <?= View::e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="card-footer text-end">
        <button class="btn btn-success"><i class="bi bi-save me-1"></i> Zapisz profil</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-trophy me-1"></i> Wyniki zawodow</div>
    <div class="card-body p-0">
        <?php if (empty($results)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak wynikow.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light"><tr><th>Data</th><th>Zawody</th><th>Bron</th><th>Miejsce</th></tr></thead>
                    <tbody>
                    <?php foreach ($results as $r):
                        $wi = $weapons[$r['weapon'] ?? ''] ?? null;
                    ?>
                        <tr>
                            <td class="small"><?= View::e($r['competition_date']) ?></td>
                            <td><strong><?= View::e($r['competition_name']) ?></strong></td>
                            <td>
                                <?php if ($wi): ?>
                                    <span class="badge" style="background:<?= $wi['color'] ?>;color:#fff;"><?= View::e($wi['label']) ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><?php if ($r['placement']): ?><span class="badge bg-primary">#<?= (int)$r['placement'] ?></span><?php else: ?>—<?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
