<?php use App\Helpers\View; $c = $competition; ?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h3 class="mb-1"><i class="bi bi-trophy me-2"></i><?= View::e($c['name']) ?></h3>
        <div class="text-muted small">
            <?= View::e($c['date_from']) ?>
            <?php if (!empty($c['date_to']) && $c['date_to'] !== $c['date_from']): ?>
                – <?= View::e($c['date_to']) ?>
            <?php endif; ?>
            <?php if (!empty($c['location'])): ?> · <?= View::e($c['location']) ?><?php endif; ?>
            · <span class="badge bg-info"><?= View::e($c['level']) ?></span>
            · <span class="badge bg-secondary"><?= View::e($statuses[$c['status']] ?? $c['status']) ?></span>
        </div>
    </div>
    <a href="<?= url('equestrian/competitions') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Lista
    </a>
</div>

<?php if (!empty($c['notes'])): ?>
<div class="card p-3 mb-3 small">
    <strong>Notatki:</strong> <?= nl2br(View::e($c['notes'])) ?>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h5 class="mb-0">Klasy zawodów</h5>
    <button class="btn btn-sm btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#classForm">
        <i class="bi bi-plus-circle me-1"></i> Dodaj klasę
    </button>
</div>

<div id="classForm" class="collapse mb-3">
    <div class="card p-3">
        <form method="POST" action="<?= url('equestrian/competitions/' . (int)$c['id'] . '/class') ?>" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-md-1">
                <label class="form-label">Nr</label>
                <input type="number" name="class_no" class="form-control" min="1">
            </div>
            <div class="col-md-5">
                <label class="form-label">Nazwa klasy *</label>
                <input type="text" name="name" class="form-control" required placeholder="np. Klasa LL — 110cm">
            </div>
            <div class="col-md-3">
                <label class="form-label">Dyscyplina *</label>
                <select name="discipline" class="form-select" required>
                    <?php foreach ($disciplines as $k => $label): ?>
                        <option value="<?= $k ?>"><?= View::e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Poziom</label>
                <select name="class_level" class="form-select">
                    <option value="">—</option>
                    <?php foreach ($classLevels as $k => $label): ?>
                        <option value="<?= $k ?>"><?= View::e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Wysokość przeszkód (cm)</label>
                <input type="number" name="fence_height_cm" min="40" max="200" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Norma czasu (s)</label>
                <input type="number" name="time_allowed_s" min="20" max="600" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Max startujących</label>
                <input type="number" name="max_starters" min="1" max="200" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Pula nagród (zł)</label>
                <input type="number" step="0.01" name="prize_pool" class="form-control">
            </div>
            <div class="col-12 text-end">
                <button class="btn btn-primary"><i class="bi bi-check2"></i> Dodaj klasę</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Nr</th>
                <th>Nazwa</th>
                <th>Dyscyplina</th>
                <th>Poziom</th>
                <th>Wysokość</th>
                <th>Czas</th>
                <th>Pula</th>
                <th>Starty</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($c['classes'])): ?>
                <tr><td colspan="9" class="text-center text-muted py-3">
                    Brak klas. Dodaj pierwszą klikając przycisk powyżej.
                </td></tr>
            <?php else: foreach ($c['classes'] as $cls): ?>
                <tr>
                    <td class="text-muted">#<?= (int)($cls['class_no'] ?? '?') ?></td>
                    <td><strong><?= View::e($cls['name']) ?></strong></td>
                    <td><span class="badge bg-info"><?= View::e($disciplines[$cls['discipline']] ?? $cls['discipline']) ?></span></td>
                    <td><?= View::e($cls['class_level'] ?? '—') ?></td>
                    <td><?= !empty($cls['fence_height_cm']) ? (int)$cls['fence_height_cm'] . ' cm' : '—' ?></td>
                    <td><?= !empty($cls['time_allowed_s']) ? (int)$cls['time_allowed_s'] . ' s' : '—' ?></td>
                    <td><?= !empty($cls['prize_pool']) ? number_format((float)$cls['prize_pool'], 2, ',', ' ') . ' zł' : '—' ?></td>
                    <td><span class="badge bg-light text-dark"><?= (int)($cls['start_count'] ?? 0) ?></span></td>
                    <td class="text-end">
                        <form method="POST" action="<?= url('equestrian/classes/' . (int)$cls['id'] . '/delete') ?>"
                              onsubmit="return confirm('Usunąć klasę?')" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
