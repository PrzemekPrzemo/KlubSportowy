<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-music-note-beamed text-primary me-2"></i>Taniec — Katalog stylow</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#styleModal">
        <i class="bi bi-plus-circle"></i> Dodaj wlasny styl
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kod</th>
                    <th>Nazwa</th>
                    <th>Kategoria</th>
                    <th>Typ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($styles)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Brak stylow.</td></tr>
            <?php else: foreach ($styles as $s): ?>
                <tr>
                    <td class="font-monospace small"><?= View::e($s['style_code']) ?></td>
                    <td><strong><?= View::e($s['display_name']) ?></strong></td>
                    <td><span class="badge bg-secondary"><?= View::e($categories[$s['category']] ?? $s['category']) ?></span></td>
                    <td>
                        <?php if ($s['club_id'] === null): ?>
                            <span class="badge bg-info text-dark">Globalny</span>
                        <?php else: ?>
                            <span class="badge bg-primary">Klubowy</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s['club_id'] !== null): ?>
                            <form method="POST" action="<?= url('club/dance/styles/' . (int)$s['id'] . '/deactivate') ?>"
                                  onsubmit="return confirm('Dezaktywowac?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="styleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('club/dance/styles/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-music-note-beamed me-1"></i>Dodaj styl</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Nazwa stylu</label>
                            <input type="text" name="display_name" class="form-control" required maxlength="200">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Kod (a-z, 0-9, _)</label>
                            <input type="text" name="style_code" class="form-control font-monospace"
                                   required pattern="[a-z0-9_]{2,50}" placeholder="np. salsa_cubana">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Kategoria</label>
                            <select name="category" class="form-select">
                                <?php foreach ($categories as $k => $label): ?>
                                    <option value="<?= View::e($k) ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button class="btn btn-success"><i class="bi bi-plus-circle"></i> Dodaj</button>
                </div>
            </form>
        </div>
    </div>
</div>
