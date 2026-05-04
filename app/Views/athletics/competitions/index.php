<?php use App\Helpers\View; ?>

<div class="row g-2 mb-3">
    <?php foreach (['zaplanowane' => 'primary', 'w_trakcie' => 'warning', 'zakonczone' => 'success', 'odwolane' => 'secondary'] as $st => $cls): ?>
        <div class="col-md-3">
            <div class="card p-3 text-center">
                <div class="text-muted small text-capitalize"><?= str_replace('_', ' ', $st) ?></div>
                <div class="h4 mb-0 text-<?= $cls ?>"><?= (int)($counts[$st] ?? 0) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small text-muted mb-1">Status</label>
            <select name="status" class="form-select">
                <option value="">— wszystkie —</option>
                <?php foreach (['zaplanowane','w_trakcie','zakonczone','odwolane'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($statusFilter ?? '') === $s ? 'selected' : '' ?>>
                        <?= str_replace('_',' ',$s) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Filtruj</button></div>
        <div class="col-md-3 ms-auto">
            <a href="<?= url('athletics/competitions/create') ?>" class="btn btn-success w-100"><i class="bi bi-plus"></i> Nowe zawody</a>
        </div>
    </form>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Data</th><th>Nazwa</th><th>Lokalizacja</th><th>Typ</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($pagination['data'])): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak zawodów.</td></tr>
        <?php else: foreach ($pagination['data'] as $c):
            $status_cls = match($c['status']) {
                'zaplanowane' => 'primary',
                'w_trakcie'   => 'warning',
                'zakonczone'  => 'success',
                'odwolane'    => 'secondary',
                default       => 'secondary',
            };
        ?>
            <tr>
                <td>
                    <?= format_date($c['date_from']) ?>
                    <?php if (!empty($c['date_to']) && $c['date_to'] !== $c['date_from']): ?>
                        – <?= format_date($c['date_to']) ?>
                    <?php endif; ?>
                </td>
                <td><a href="<?= url('athletics/competitions/' . (int)$c['id']) ?>"><?= View::e($c['name']) ?></a></td>
                <td><?= View::e($c['location'] ?? '—') ?></td>
                <td><span class="badge bg-light text-dark"><?= View::e($c['type']) ?></span></td>
                <td><span class="badge bg-<?= $status_cls ?>"><?= str_replace('_', ' ', View::e($c['status'])) ?></span></td>
                <td class="text-end">
                    <a href="<?= url('athletics/competitions/' . (int)$c['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="<?= url('athletics/competitions/' . (int)$c['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć zawody?')" class="d-inline">
                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
