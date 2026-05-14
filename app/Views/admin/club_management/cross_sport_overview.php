<?php
/** @var array $topActive */
/** @var array $perSport */
/** @var array $registrationTrend */

$totalMembers = array_sum(array_column($perSport, 'members_count'));
$palette = ['#0d6efd','#198754','#ffc107','#dc3545','#6f42c1','#fd7e14','#20c997','#0dcaf0','#d63384','#6610f2'];
?>
<div class="container-fluid py-3">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0">Cross-sport overview</h1>
            <small class="text-muted">Multi-sport dashboard zarzadu klubu</small>
        </div>
        <a href="<?= url('club/settings') ?>" class="btn btn-sm btn-outline-secondary">&larr; Ustawienia klubu</a>
    </div>

    <!-- KPI -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4">
            <div class="card text-bg-primary h-100">
                <div class="card-body">
                    <div class="small text-uppercase opacity-75">Dyscypliny w klubie</div>
                    <div class="display-6 fw-bold"><?= count($perSport) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card text-bg-success h-100">
                <div class="card-body">
                    <div class="small text-uppercase opacity-75">Lacznie czlonkow (per sport)</div>
                    <div class="display-6 fw-bold"><?= (int)$totalMembers ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card text-bg-info h-100">
                <div class="card-body">
                    <div class="small text-uppercase opacity-75">Najaktywniejszy czlonek</div>
                    <?php $first = $topActive[0] ?? null; ?>
                    <?php if ($first): ?>
                        <div class="h4 fw-bold mb-0">
                            <?= htmlspecialchars($first['first_name'].' '.$first['last_name'], ENT_QUOTES) ?>
                        </div>
                        <div class="small opacity-75">
                            <?= (int)$first['total_activity'] ?> aktywnosci /
                            <?= (int)$first['sports_count'] ?> sportow
                        </div>
                    <?php else: ?>
                        <div class="text-muted">Brak danych</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <!-- Per sport (doughnut) -->
        <div class="col-12 col-lg-5">
            <div class="card h-100">
                <div class="card-header"><strong>Czlonkowie per sport</strong></div>
                <div class="card-body">
                    <?php if (empty($perSport)): ?>
                        <p class="text-muted mb-0">Brak danych.</p>
                    <?php else: ?>
                        <canvas id="chartPerSport" height="180"></canvas>
                        <ul class="list-group list-group-flush mt-3">
                            <?php foreach ($perSport as $i => $s): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>
                                        <span class="d-inline-block me-2" style="width:10px;height:10px;border-radius:50%;background:<?= htmlspecialchars($s['color'] ?: $palette[$i % count($palette)], ENT_QUOTES) ?>"></span>
                                        <?= htmlspecialchars($s['sport_label'], ENT_QUOTES) ?>
                                    </span>
                                    <span class="badge bg-secondary"><?= (int)$s['members_count'] ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Registration trend (line) -->
        <div class="col-12 col-lg-7">
            <div class="card h-100">
                <div class="card-header"><strong>Trend rejestracji (12 mies.)</strong></div>
                <div class="card-body">
                    <?php if (empty($registrationTrend['data']) || array_sum($registrationTrend['data']) === 0): ?>
                        <p class="text-muted mb-0">Brak danych do wykresu.</p>
                    <?php else: ?>
                        <canvas id="chartTrend" height="180"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top active members -->
    <div class="card mb-3">
        <div class="card-header"><strong>Top 10 najaktywniejszych czlonkow (cross-sport)</strong></div>
        <div class="card-body p-0">
            <?php if (empty($topActive)): ?>
                <p class="text-muted m-3">Brak aktywnosci do wyswietlenia.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Czlonek</th>
                                <th class="text-center">Sporty</th>
                                <th class="text-center">Treningi</th>
                                <th class="text-center">Wydarzenia</th>
                                <th class="text-center">Turnieje</th>
                                <th class="text-center">Razem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topActive as $i => $m): ?>
                                <tr>
                                    <td><strong><?= $i + 1 ?></strong></td>
                                    <td>
                                        <?= htmlspecialchars($m['first_name'].' '.$m['last_name'], ENT_QUOTES) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary"><?= (int)$m['sports_count'] ?></span>
                                    </td>
                                    <td class="text-center"><?= (int)$m['total_trainings'] ?></td>
                                    <td class="text-center"><?= (int)$m['total_events'] ?></td>
                                    <td class="text-center"><?= (int)$m['total_tournaments'] ?></td>
                                    <td class="text-center"><strong><?= (int)$m['total_activity'] ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php if (!empty($perSport) || !empty($registrationTrend['data'])): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function () {
    <?php if (!empty($perSport)): ?>
    var psCtx = document.getElementById('chartPerSport');
    if (psCtx) {
        var labels = <?= json_encode(array_column($perSport, 'sport_label')) ?>;
        var data   = <?= json_encode(array_map(fn($s) => (int)$s['members_count'], $perSport)) ?>;
        var colors = <?= json_encode(array_map(fn($i, $s) => $s['color'] ?: $palette[$i % count($palette)], array_keys($perSport), $perSport)) ?>;
        new Chart(psCtx, {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: data, backgroundColor: colors }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }
    <?php endif; ?>

    <?php if (!empty($registrationTrend['data']) && array_sum($registrationTrend['data']) > 0): ?>
    var trCtx = document.getElementById('chartTrend');
    if (trCtx) {
        new Chart(trCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($registrationTrend['labels']) ?>,
                datasets: [{
                    label: 'Nowi czlonkowie',
                    data: <?= json_encode($registrationTrend['data']) ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
    <?php endif; ?>
})();
</script>
<?php endif; ?>
