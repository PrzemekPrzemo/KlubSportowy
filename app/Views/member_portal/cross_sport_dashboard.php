<?php
/** @var array $stats */
/** @var array $member */
$totals     = $stats['totals']         ?? [];
$sports     = $stats['sports']         ?? [];
$recent     = $stats['recent_activity'] ?? [];
$highlights = $stats['highlights']     ?? [];
$chart      = $stats['monthly_chart']  ?? ['labels' => [], 'datasets' => []];

$typeBadge = function (string $type): string {
    return match ($type) {
        'training'   => '<span class="badge bg-primary">Trening</span>',
        'event'      => '<span class="badge bg-success">Wydarzenie</span>',
        'tournament' => '<span class="badge bg-warning text-dark">Turniej</span>',
        default      => '<span class="badge bg-secondary">' . htmlspecialchars($type, ENT_QUOTES) . '</span>',
    };
};
?>
<div class="container-fluid py-3">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0">Cross-sport stats</h1>
            <small class="text-muted">Twoja aktywnosc po wszystkich dyscyplinach</small>
        </div>
        <a href="<?= url('portal/dashboard') ?>" class="btn btn-sm btn-outline-secondary">&larr; Powrot na dashboard</a>
    </div>

    <!-- KPI cards -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card text-bg-primary h-100">
                <div class="card-body">
                    <div class="small text-uppercase opacity-75">Treningi</div>
                    <div class="display-6 fw-bold"><?= (int)($totals['total_trainings'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-bg-success h-100">
                <div class="card-body">
                    <div class="small text-uppercase opacity-75">Wydarzenia</div>
                    <div class="display-6 fw-bold"><?= (int)($totals['total_events'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-bg-warning h-100">
                <div class="card-body">
                    <div class="small text-uppercase opacity-75">Turnieje</div>
                    <div class="display-6 fw-bold"><?= (int)($totals['total_tournaments'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-bg-info h-100">
                <div class="card-body">
                    <div class="small text-uppercase opacity-75">Aktywne sezony</div>
                    <div class="display-6 fw-bold"><?= (int)($totals['active_seasons'] ?? 0) ?></div>
                    <div class="small opacity-75">w <?= (int)($totals['sports_count'] ?? 0) ?> dyscyplinach</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sports breakdown -->
    <div class="card mb-3">
        <div class="card-header">
            <strong>Moje dyscypliny (<?= count($sports) ?>)</strong>
        </div>
        <div class="card-body">
            <?php if (empty($sports)): ?>
                <p class="text-muted mb-0">Nie jestes jeszcze przypisany do zadnej dyscypliny.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($sports as $sp): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 border-start border-4" style="border-left-color: <?= htmlspecialchars($sp['color'] ?: '#0d6efd', ENT_QUOTES) ?> !important;">
                                <div class="card-body">
                                    <h5 class="card-title mb-2">
                                        <?php if (!empty($sp['icon'])): ?>
                                            <i class="<?= htmlspecialchars($sp['icon'], ENT_QUOTES) ?>"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($sp['sport_label'], ENT_QUOTES) ?>
                                    </h5>
                                    <div class="row text-center small">
                                        <div class="col">
                                            <div class="fw-bold fs-5"><?= (int)$sp['trainings_count'] ?></div>
                                            <div class="text-muted">treningi</div>
                                        </div>
                                        <div class="col">
                                            <div class="fw-bold fs-5"><?= (int)$sp['events_count'] ?></div>
                                            <div class="text-muted">wydarzenia</div>
                                        </div>
                                        <div class="col">
                                            <div class="fw-bold fs-5"><?= (int)$sp['tournaments_count'] ?></div>
                                            <div class="text-muted">turnieje</div>
                                        </div>
                                    </div>
                                    <?php if (!empty($sp['rankings'])): ?>
                                        <hr class="my-2">
                                        <div class="small">
                                            <strong>Rankingi:</strong>
                                            <?php foreach (array_slice($sp['rankings'], 0, 3) as $r): ?>
                                                <div>
                                                    Sezon <?= htmlspecialchars($r['season'], ENT_QUOTES) ?>:
                                                    <?= (int)$r['points'] ?> pkt
                                                    <?php if (!empty($r['position'])): ?>
                                                        <span class="badge bg-secondary">#<?= (int)$r['position'] ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chart -->
    <div class="card mb-3">
        <div class="card-header"><strong>Aktywnosc w ostatnich 12 miesiacach (cross-sport)</strong></div>
        <div class="card-body">
            <?php if (empty($chart['datasets'])): ?>
                <p class="text-muted mb-0">Brak danych do wykresu.</p>
            <?php else: ?>
                <canvas id="crossSportChart" height="100"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Highlights -->
    <div class="card mb-3">
        <div class="card-header"><strong>Top 5 highlights</strong></div>
        <div class="card-body">
            <?php if (empty($highlights)): ?>
                <p class="text-muted mb-0">Brak osiagniec do wyswietlenia.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($highlights as $h): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-trophy text-warning me-2"></i>
                                <?= htmlspecialchars($h['label'], ENT_QUOTES) ?>
                                <?php if (!empty($h['sport_key'])): ?>
                                    <span class="badge bg-light text-dark ms-1"><?= htmlspecialchars($h['sport_key'], ENT_QUOTES) ?></span>
                                <?php endif; ?>
                            </div>
                            <strong><?= htmlspecialchars($h['value'], ENT_QUOTES) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent activity timeline -->
    <div class="card mb-3">
        <div class="card-header"><strong>Ostatnie 10 aktywnosci</strong></div>
        <div class="card-body p-0">
            <?php if (empty($recent)): ?>
                <p class="text-muted m-3">Brak aktywnosci do wyswietlenia.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 110px;">Data</th>
                                <th style="width: 110px;">Typ</th>
                                <th>Wydarzenie</th>
                                <th style="width: 140px;">Sport</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent as $a): ?>
                                <tr>
                                    <td><small><?= htmlspecialchars(substr((string)$a['date'], 0, 16), ENT_QUOTES) ?></small></td>
                                    <td><?= $typeBadge($a['type']) ?></td>
                                    <td><?= htmlspecialchars($a['label'], ENT_QUOTES) ?></td>
                                    <td>
                                        <?php if (!empty($a['sport_label'])): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($a['sport_label'], ENT_QUOTES) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php if (!empty($chart['datasets'])): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function() {
    var ctx = document.getElementById('crossSportChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart['labels']) ?>,
            datasets: <?= json_encode($chart['datasets']) ?>
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
})();
</script>
<?php endif; ?>
