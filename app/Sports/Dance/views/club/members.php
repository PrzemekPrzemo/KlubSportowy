<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-people text-primary me-2"></i>Taniec — Zawodnicy</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#assignModal">
        <i class="bi bi-plus-circle"></i> Przypisz styl zawodnikowi
    </button>
</div>

<form method="GET" class="mb-3 d-flex gap-2 align-items-center">
    <label class="small text-muted mb-0">Filtr po stylu:</label>
    <select name="style" class="form-select form-select-sm" style="width:280px" onchange="this.form.submit()">
        <option value="">— wszystkie —</option>
        <?php foreach ($styles as $s): ?>
            <option value="<?= View::e($s['style_code']) ?>"
                <?= ($currentStyle ?? null) === $s['style_code'] ? 'selected' : '' ?>>
                <?= View::e($s['display_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Zawodnik</th>
                    <th>Styl</th>
                    <th>Poziom</th>
                    <th>Partner</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Brak przypisan.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td>
                        <strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong>
                        <small class="text-muted">#<?= View::e($r['member_number'] ?? '') ?></small>
                    </td>
                    <td><?= View::e($r['style_name'] ?? $r['style_code']) ?></td>
                    <td><span class="badge bg-info text-dark"><?= View::e($levels[$r['level']] ?? $r['level']) ?></span></td>
                    <td><?= !empty($r['partner_last']) ? View::e($r['partner_last'] . ' ' . $r['partner_first']) : '<span class="text-muted small">—</span>' ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('club/dance/members/assign') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-1"></i>Przypisz styl</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Zawodnik</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Styl</label>
                            <select name="style_code" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($styles as $s): ?>
                                    <option value="<?= View::e($s['style_code']) ?>"><?= View::e($s['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Poziom</label>
                            <select name="level" class="form-select">
                                <?php foreach ($levels as $k => $label): ?>
                                    <option value="<?= View::e($k) ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Partner (opcjonalnie)</label>
                            <select name="partner_member_id" class="form-select">
                                <option value="">— brak —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button class="btn btn-success"><i class="bi bi-check-circle"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
