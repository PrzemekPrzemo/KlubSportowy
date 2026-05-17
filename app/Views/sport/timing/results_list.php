<?php
use App\Helpers\View;
use App\Sports\Support\SportTimingResultModel;
/** @var string $sportKey */
/** @var array $manifest */
/** @var array $pagination */
/** @var array $events */
/** @var ?string $eventFilter */
$rows = $pagination['data'] ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-stopwatch text-primary me-2"></i>
        Wyniki — <?= View::e($manifest['name'] ?? $sportKey) ?>
    </h3>
    <a href="<?= url('club/sport/' . $sportKey . '/result/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle"></i> Nowy wynik
    </a>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= View::e($flashError) ?></div>
<?php endif; ?>

<form method="GET" class="row g-2 mb-3">
    <div class="col-md-4">
        <select name="event" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">— wszystkie konkurencje —</option>
            <?php foreach ($events as $ev): ?>
                <option value="<?= View::e($ev) ?>" <?= $eventFilter === $ev ? 'selected' : '' ?>>
                    <?= View::e($ev) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if (!empty($eventFilter)): ?>
    <div class="col-md-2">
        <a href="<?= url('club/sport/' . $sportKey . '/results') ?>" class="btn btn-outline-secondary btn-sm">Wyczyść</a>
    </div>
    <?php endif; ?>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Zawodnik</th>
                    <th>Konkurencja</th>
                    <th>Dystans</th>
                    <th>Czas</th>
                    <th>Kary</th>
                    <th>Miejsce</th>
                    <th>Kategoria</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="10" class="text-muted text-center py-4">Brak wyników w tej kategorii.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= View::e($r['recorded_at']) ?></td>
                    <td><strong><?= View::e(($r['last_name'] ?? '') . ' ' . ($r['first_name'] ?? '')) ?></strong></td>
                    <td><?= View::e($r['event_name']) ?></td>
                    <td><?= (int)$r['distance_m'] ?> m</td>
                    <td><code><?= SportTimingResultModel::formatTime((int)$r['finish_time_ms']) ?></code></td>
                    <td><?= number_format((float)$r['penalties_seconds'], 2) ?>s</td>
                    <td><?= $r['rank'] ? (int)$r['rank'] . '.' : '—' ?></td>
                    <td><?= View::e($r['category'] ?? '—') ?></td>
                    <td>
                        <?php if ((int)$r['verified'] === 1): ?>
                            <span class="badge bg-success"><i class="bi bi-check2"></i> Zweryfikowany</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Oczekuje</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if ((int)$r['verified'] === 0): ?>
                            <form method="POST" action="<?= url('club/sport/' . $sportKey . '/result/' . (int)$r['id'] . '/verify') ?>"
                                  class="d-inline" onsubmit="return confirm('Potwierdzić wynik?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-check2-circle"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="<?= url('club/sport/' . $sportKey . '/result/' . (int)$r['id'] . '/delete') ?>"
                              class="d-inline" onsubmit="return confirm('Usunąć wynik?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($pagination['last_page'] ?? 1) > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm">
        <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
            <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $p ?><?= $eventFilter ? '&event=' . urlencode($eventFilter) : '' ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
