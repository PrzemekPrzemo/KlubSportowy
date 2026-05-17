<?php
use App\Helpers\View;
use App\Sports\Support\SportTimingResultModel;
/** @var string $sportKey */
/** @var array $manifest */
/** @var array $pagination */
/** @var array $pbs */
/** @var array $history */
/** @var array $events */
/** @var ?string $eventFilter */
$rows = $pagination['data'] ?? [];

// Dane do progress chart
$labels = [];
$series = [];
foreach ($history as $h) {
    $labels[] = $h['recorded_at'];
    $series[] = round(((int)$h['finish_time_ms']) / 1000, 2);
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1"><i class="bi bi-stopwatch text-primary me-2"></i><?= View::e($manifest['name'] ?? $sportKey) ?> — moje wyniki</h3>
        <p class="text-muted mb-0">Personal bests, historia i progres czasów.</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-trophy-fill me-1"></i> Personal bests
    </div>
    <div class="card-body">
        <?php if (!$pbs): ?>
            <p class="text-muted mb-0">Brak zapisanych wyników w tej dyscyplinie.</p>
        <?php else: ?>
            <div class="row g-2">
                <?php foreach ($pbs as $pb): ?>
                    <div class="col-md-4">
                        <div class="border rounded p-2">
                            <strong><?= View::e($pb['event_name']) ?></strong>
                            <span class="text-muted small">(<?= (int)$pb['distance_m'] ?> m)</span>
                            <div class="display-6">
                                <code><?= SportTimingResultModel::formatTime((int)$pb['best_time_ms']) ?></code>
                            </div>
                            <small class="text-muted">Ostatnio: <?= View::e($pb['last_at']) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <i class="bi bi-graph-up me-1"></i> Postęp w czasie
        <?php if (!empty($eventFilter)): ?>
            — konkurencja: <strong><?= View::e($eventFilter) ?></strong>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-5">
                <select name="event" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— wszystkie konkurencje —</option>
                    <?php foreach ($events as $ev): ?>
                        <option value="<?= View::e($ev) ?>" <?= $eventFilter === $ev ? 'selected' : '' ?>>
                            <?= View::e($ev) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        <?php if (!$labels): ?>
            <p class="text-muted mb-0">Brak danych do wykresu.</p>
        <?php else: ?>
            <canvas id="timingProgressChart" height="120"></canvas>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <i class="bi bi-list-ul me-1"></i> Moje wyniki (ostatnie)
    </div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Konkurencja</th>
                    <th>Dystans</th>
                    <th>Czas</th>
                    <th>Miejsce</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= View::e($r['recorded_at']) ?></td>
                    <td><?= View::e($r['event_name']) ?></td>
                    <td><?= (int)$r['distance_m'] ?> m</td>
                    <td><code><?= SportTimingResultModel::formatTime((int)$r['finish_time_ms']) ?></code></td>
                    <td><?= $r['rank'] ? (int)$r['rank'] . '.' : '—' ?></td>
                    <td>
                        <?php if ((int)$r['verified'] === 1): ?>
                            <span class="badge bg-success">OK</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">Brak wyników do wyświetlenia.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($labels): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    var ctx = document.getElementById('timingProgressChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Czas [s]',
                data: <?= json_encode($series) ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, .15)',
                tension: 0.25,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: false } }
        }
    });
})();
</script>
<?php endif; ?>
