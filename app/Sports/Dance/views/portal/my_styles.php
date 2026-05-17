<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-music-note-beamed text-primary me-2"></i>Moje style tanca</h4>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#myStyleModal">
        <i class="bi bi-plus-circle"></i> Dodaj/edytuj styl
    </button>
</div>

<?php if (empty($mine)): ?>
    <div class="alert alert-info">Nie masz jeszcze przypisanych stylow. Kliknij <strong>Dodaj/edytuj styl</strong>.</div>
<?php else: ?>
    <div class="card shadow-sm mb-4">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Styl</th>
                        <th>Kategoria</th>
                        <th>Poziom</th>
                        <th>Partner</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($mine as $r): ?>
                    <tr>
                        <td><strong><?= View::e($r['style_name'] ?? $r['style_code']) ?></strong></td>
                        <td class="small text-muted"><?= View::e($r['style_category'] ?? '') ?></td>
                        <td><span class="badge bg-info text-dark"><?= View::e($levels[$r['level']] ?? $r['level']) ?></span></td>
                        <td>
                            <?= !empty($r['partner_last']) ? View::e($r['partner_last'] . ' ' . $r['partner_first']) : '<span class="text-muted small">—</span>' ?>
                        </td>
                        <td>
                            <form method="POST" action="<?= url('portal/dance/styles/remove') ?>"
                                  onsubmit="return confirm('Usunac styl z profilu?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="style_code" value="<?= View::e($r['style_code']) ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<h5 class="mb-2"><i class="bi bi-trophy me-1"></i>Moje wystepy</h5>
<?php if (empty($performances)): ?>
    <div class="text-muted small">Brak wystepow w turniejach.</div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Turniej</th>
                        <th>Data</th>
                        <th>Styl</th>
                        <th class="text-end">Wynik</th>
                        <th class="text-center">Pozycja</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($performances as $p): ?>
                    <tr>
                        <td><?= View::e($p['tournament_name']) ?></td>
                        <td class="small text-muted"><?= View::e($p['tournament_date']) ?></td>
                        <td><?= View::e($p['style_name'] ?? $p['style_code']) ?></td>
                        <td class="text-end font-monospace"><?= $p['total_score'] !== null ? number_format((float)$p['total_score'], 2) : '—' ?></td>
                        <td class="text-center">
                            <?php if ((int)$p['rank'] === 1): ?>
                                <i class="bi bi-trophy-fill text-warning"></i> 1
                            <?php elseif ($p['rank'] !== null): ?>
                                <?= (int)$p['rank'] ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="modal fade" id="myStyleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('portal/dance/styles/save') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Dodaj/edytuj styl</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Styl</label>
                        <select name="style_code" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($styles as $s): ?>
                                <option value="<?= View::e($s['style_code']) ?>"><?= View::e($s['display_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Poziom</label>
                        <select name="level" class="form-select">
                            <?php foreach ($levels as $k => $label): ?>
                                <option value="<?= View::e($k) ?>"><?= View::e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button class="btn btn-success">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
