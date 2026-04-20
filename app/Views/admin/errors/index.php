<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-bug-fill me-2"></i>Dziennik błędów</h4>
        <small class="text-muted">Zdarzenia zapisane w bazie przez ErrorMonitor.</small>
    </div>
    <form method="POST" action="<?= url('admin/errors/purge') ?>" class="d-flex gap-2 align-items-center" onsubmit="return confirm('Usunąć wpisy starsze niż podana liczba dni?');">
        <?= csrf_field() ?>
        <input type="number" name="days" value="90" min="1" max="3650" class="form-control form-control-sm" style="width:90px;">
        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Wyczyść stare</button>
    </form>
</div>

<div class="row g-2 mb-3">
    <?php
    $rangeLabels = ['last_24_hour' => 'Ostatnie 24h', 'last_7_day' => 'Ostatnie 7 dni', 'last_30_day' => 'Ostatnie 30 dni'];
    $levelColors = ['debug' => 'secondary', 'info' => 'info', 'warning' => 'warning', 'error' => 'danger', 'critical' => 'dark'];
    foreach ($rangeLabels as $key => $label):
        $counts = $stats[$key] ?? [];
        $sum = array_sum($counts);
    ?>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body py-2">
                <div class="small text-muted"><?= View::e($label) ?></div>
                <div class="h5 mb-1"><?= (int)$sum ?> <small class="text-muted">zdarzeń</small></div>
                <?php foreach ($levelColors as $lvl => $color): ?>
                    <?php if (!empty($counts[$lvl])): ?>
                        <span class="badge bg-<?= $color ?> me-1"><?= ucfirst($lvl) ?>: <?= (int)$counts[$lvl] ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">Poziom</label>
            <select name="level" class="form-select form-select-sm">
                <option value="">— Wszystkie —</option>
                <?php foreach (array_keys($levelColors) as $lvl): ?>
                    <option value="<?= $lvl ?>" <?= ($filter['level'] ?? '') === $lvl ? 'selected' : '' ?>><?= ucfirst($lvl) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Od</label>
            <input type="date" name="from" value="<?= View::e($filter['from'] ?? '') ?>" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Do</label>
            <input type="date" name="to" value="<?= View::e($filter['to'] ?? '') ?>" class="form-control form-control-sm">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Filtruj</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Czas</th><th>Poziom</th><th>Wiadomość</th><th>Plik:linia</th>
                    <th>Użytkownik</th><th>Klub</th><th>IP</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pagination['data'])): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Brak błędów w wybranym zakresie.</td></tr>
                <?php else: ?>
                    <?php foreach ($pagination['data'] as $r):
                        $color = $levelColors[$r['level']] ?? 'secondary';
                    ?>
                    <tr>
                        <td><small><?= format_datetime($r['created_at']) ?></small></td>
                        <td><span class="badge bg-<?= $color ?>"><?= View::e($r['level']) ?></span></td>
                        <td class="text-truncate" style="max-width:360px;"><small><?= View::e(mb_substr($r['message'], 0, 200)) ?></small></td>
                        <td><small class="text-muted"><?= View::e(basename($r['file'] ?? '')) ?><?= $r['line'] ? ':' . (int)$r['line'] : '' ?></small></td>
                        <td><small><?= View::e($r['full_name'] ?? $r['username'] ?? '—') ?></small></td>
                        <td><small><?= View::e($r['club_name'] ?? '—') ?></small></td>
                        <td><small><?= View::e($r['ip_address'] ?? '') ?></small></td>
                        <td><a href="<?= url('admin/errors/' . (int)$r['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($pagination['last_page'] ?? 1) > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php
        $qs = http_build_query(array_filter([
            'level' => $filter['level'] ?? '',
            'from'  => $filter['from'] ?? '',
            'to'    => $filter['to'] ?? '',
        ]));
        for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?><?= $qs ? '&' . $qs : '' ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
