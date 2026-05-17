<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">
            <i class="bi bi-grid-3x3 text-primary me-2"></i>
            Pools — turniej szermierczy
        </h4>
        <?php if (!empty($tournament)): ?>
            <div class="small text-muted">
                <?= View::e($tournament['name']) ?> · <?= View::e($tournament['date_start']) ?>
            </div>
        <?php endif; ?>
    </div>
    <a href="<?= url('fencing/results') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Wyniki
    </a>
</div>

<form method="POST" action="<?= url('fencing/pools/' . (int)($tournament['id'] ?? 0) . '/store') ?>" class="card shadow-sm mb-4">
    <?= csrf_field() ?>
    <div class="card-header"><i class="bi bi-plus-circle me-1"></i> Dodaj pool (group stage)</div>
    <div class="card-body row g-3">
        <div class="col-md-3"><label class="form-label">Numer pool</label>
            <input type="number" min="1" class="form-control" name="pool_number" required>
        </div>
        <div class="col-md-4"><label class="form-label">Bron</label>
            <select name="weapon" class="form-select">
                <?php foreach ($weapons as $code => $info): ?>
                    <option value="<?= View::e($code) ?>"><?= View::e($info['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5 d-flex align-items-end">
            <button class="btn btn-success"><i class="bi bi-save me-1"></i> Utworz pool</button>
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-list-ol me-1"></i> Pools tego turnieju</div>
    <div class="card-body p-0">
        <?php if (empty($pools)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak pools.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Bron</th><th>Utworzony</th></tr></thead>
                    <tbody>
                    <?php foreach ($pools as $p):
                        $wi = $weapons[$p['weapon']] ?? null;
                    ?>
                        <tr>
                            <td><strong>Pool <?= (int)$p['pool_number'] ?></strong></td>
                            <td>
                                <?php if ($wi): ?>
                                    <span class="badge" style="background:<?= $wi['color'] ?>;color:#fff;"><?= View::e($wi['label']) ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= View::e($p['created_at'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
