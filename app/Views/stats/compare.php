<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <label class="form-label small">Zawodnik A</label>
            <select name="m1" class="form-select">
                <option value="">— wybierz —</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>" <?= $id1 === (int)$m['id'] ? 'selected' : '' ?>>
                        <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small">Zawodnik B</label>
            <select name="m2" class="form-select">
                <option value="">— wybierz —</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>" <?= $id2 === (int)$m['id'] ? 'selected' : '' ?>>
                        <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button class="btn btn-primary w-100"><i class="bi bi-arrow-left-right"></i> Porównaj</button>
        </div>
    </form>
</div>

<?php if ($stats1 && $stats2): ?>
<div class="row g-3">
    <?php foreach ([$stats1, $stats2] as $i => $s):
        $m = $s['member'];
        $label = $i === 0 ? 'A' : 'B';
    ?>
    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="text-center">
                <?= View::e($m['first_name']) ?> <?= View::e($m['last_name']) ?>
                <small class="text-muted d-block">#<?= View::e($m['member_number']) ?></small>
            </h5>
            <table class="table table-sm mb-0">
                <tbody>
                    <tr><td>Status</td><td class="text-end"><span class="badge bg-<?= $m['status']==='aktywny'?'success':'secondary' ?>"><?= View::e($m['status']) ?></span></td></tr>
                    <tr><td>Członek od</td><td class="text-end"><?= format_date($s['join_date']) ?></td></tr>
                    <tr><td>Sekcje sportowe</td><td class="text-end"><strong><?= (int)$s['sports_count'] ?></strong></td></tr>
                    <tr><td>Treningi (obecność)</td><td class="text-end"><strong><?= (int)$s['trainings'] ?></strong></td></tr>
                    <tr><td>Udział w wydarzeniach</td><td class="text-end"><strong><?= (int)$s['events'] ?></strong></td></tr>
                    <tr><td>Średni wynik</td><td class="text-end"><strong><?= $s['avg_score'] > 0 ? number_format($s['avg_score'], 2) : '—' ?></strong></td></tr>
                    <tr><td>Suma wpłat</td><td class="text-end"><strong><?= format_money($s['total_paid']) ?></strong></td></tr>
                </tbody>
            </table>
            <?php foreach (($m['sports'] ?? []) as $sp): ?>
                <span class="sport-badge mt-1" style="background:<?= View::e($sp['color']) ?>"><?= View::e($sp['sport_name']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Visual comparison chart -->
<div class="card p-3 mt-3">
    <h5 class="text-center">Porównanie</h5>
    <canvas id="compareChart" height="200"></canvas>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
new Chart(document.getElementById('compareChart'), {
    type: 'radar',
    data: {
        labels: ['Treningi', 'Wydarzenia', 'Śr. wynik', 'Sekcje', 'Wpłaty (tys.)'],
        datasets: [
            {
                label: '<?= View::e($stats1['member']['last_name']) ?>',
                data: [<?= (int)$stats1['trainings'] ?>, <?= (int)$stats1['events'] ?>, <?= $stats1['avg_score'] ?>, <?= (int)$stats1['sports_count'] ?>, <?= round($stats1['total_paid']/1000, 1) ?>],
                borderColor: 'rgba(54,162,235,1)', backgroundColor: 'rgba(54,162,235,0.2)'
            },
            {
                label: '<?= View::e($stats2['member']['last_name']) ?>',
                data: [<?= (int)$stats2['trainings'] ?>, <?= (int)$stats2['events'] ?>, <?= $stats2['avg_score'] ?>, <?= (int)$stats2['sports_count'] ?>, <?= round($stats2['total_paid']/1000, 1) ?>],
                borderColor: 'rgba(255,99,132,1)', backgroundColor: 'rgba(255,99,132,0.2)'
            }
        ]
    },
    options: { scales: { r: { beginAtZero: true } }, plugins: { legend: { position: 'bottom' } } }
});
</script>
<?php elseif ($id1 || $id2): ?>
    <div class="alert alert-info">Wybierz dwóch zawodników do porównania.</div>
<?php endif; ?>
