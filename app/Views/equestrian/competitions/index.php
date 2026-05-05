<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-trophy me-2"></i>Zawody jeździeckie</h3>
    <button class="btn btn-sm btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#compForm">
        <i class="bi bi-plus-circle me-1"></i> Nowe zawody
    </button>
</div>

<div class="card p-2 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">— wszystkie —</option>
                <?php foreach ($statuses as $k => $label): ?>
                    <option value="<?= $k ?>" <?= ($statusFilter ?? '') === $k ? 'selected' : '' ?>>
                        <?= View::e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Filtruj</button>
        </div>
    </form>
</div>

<div id="compForm" class="collapse mb-3">
    <div class="card p-3">
        <form method="POST" action="<?= url('equestrian/competitions/store') ?>" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-md-6">
                <label class="form-label">Nazwa zawodów *</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Poziom</label>
                <select name="level" class="form-select">
                    <?php foreach ($levels as $k => $label): ?>
                        <option value="<?= $k ?>"><?= View::e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <?php foreach ($statuses as $k => $label): ?>
                        <option value="<?= $k ?>"><?= View::e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Data rozpoczęcia *</label>
                <input type="date" name="date_from" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Data zakończenia</label>
                <input type="date" name="date_to" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Lokalizacja</label>
                <input type="text" name="location" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Klub-organizator</label>
                <input type="text" name="host_club" class="form-control" placeholder="gdy zawody nie nasze">
            </div>
            <div class="col-12">
                <label class="form-label">Notatki</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-12 text-end">
                <button class="btn btn-primary"><i class="bi bi-check2"></i> Utwórz zawody</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Data</th>
                <th>Nazwa</th>
                <th>Poziom</th>
                <th>Lokalizacja</th>
                <th>Klasy</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($competitions)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Brak zawodów.</td></tr>
        <?php else: foreach ($competitions as $c):
            $statusBadge = match ($c['status']) {
                'zaplanowane' => 'primary',
                'w_trakcie'   => 'warning',
                'zakonczone'  => 'success',
                'odwolane'    => 'secondary',
                default       => 'secondary',
            };
        ?>
            <tr>
                <td class="small">
                    <?= View::e($c['date_from']) ?>
                    <?php if (!empty($c['date_to']) && $c['date_to'] !== $c['date_from']): ?>
                        – <?= View::e($c['date_to']) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= url('equestrian/competitions/' . (int)$c['id']) ?>">
                        <strong><?= View::e($c['name']) ?></strong>
                    </a>
                    <?php if (!empty($c['host_club'])): ?>
                        <small class="text-muted d-block">org: <?= View::e($c['host_club']) ?></small>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-info"><?= View::e($c['level']) ?></span></td>
                <td class="small"><?= View::e($c['location'] ?? '—') ?></td>
                <td><span class="badge bg-light text-dark"><?= (int)($c['class_count'] ?? 0) ?></span></td>
                <td>
                    <span class="badge bg-<?= $statusBadge ?>"><?= View::e($statuses[$c['status']] ?? $c['status']) ?></span>
                </td>
                <td class="text-end">
                    <a href="<?= url('equestrian/competitions/' . (int)$c['id']) ?>"
                       class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                    <form method="POST" action="<?= url('equestrian/competitions/' . (int)$c['id'] . '/delete') ?>"
                          onsubmit="return confirm('Usunąć zawody?')" class="d-inline">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
