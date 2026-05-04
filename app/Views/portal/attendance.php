<?php use App\Helpers\View; ?>

<?php
$polishMonths = ['','Sty','Lut','Mar','Kwi','Maj','Cze','Lip','Sie','Wrz','Paź','Lis','Gru'];
$currentYear  = (int)date('Y');
?>

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <form method="GET" class="d-flex align-items-center gap-2">
        <label class="form-label mb-0 small">Rok:</label>
        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto">
            <?php for ($y = $currentYear; $y >= $currentYear - 4; $y--): ?>
                <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>

<!-- Podsumowanie roczne per sport -->
<?php if (empty($summary)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>Brak danych o frekwencji w <?= $year ?> roku.
</div>
<?php else: ?>
<div class="card mb-4">
    <div class="card-header fw-semibold"><i class="bi bi-bar-chart me-2"></i>Miesięczna frekwencja <?= $year ?></div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
                <tr>
                    <th>Sekcja</th>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <th class="text-center small"><?= $polishMonths[$m] ?></th>
                    <?php endfor; ?>
                    <th class="text-center">Łącznie</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($summary as $sportName => $months):
                $totalAttended = 0; $totalTrainings = 0;
                foreach ($months as $data) { $totalAttended += $data['attended']; $totalTrainings += $data['total']; }
            ?>
                <tr>
                    <td class="fw-semibold small"><?= View::e($sportName) ?></td>
                    <?php for ($m = 1; $m <= 12; $m++):
                        $d = $months[$m] ?? null;
                    ?>
                        <td class="text-center small">
                            <?php if ($d): ?>
                                <?php $pct = $d['total'] > 0 ? round($d['attended']/$d['total']*100) : 0; ?>
                                <span class="badge bg-<?= $pct >= 75 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') ?> bg-opacity-75">
                                    <?= $d['attended'] ?>/<?= $d['total'] ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    <?php endfor; ?>
                    <td class="text-center fw-bold small">
                        <?php $pct = $totalTrainings > 0 ? round($totalAttended/$totalTrainings*100) : 0; ?>
                        <span class="badge bg-<?= $pct >= 75 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') ?>">
                            <?= $totalAttended ?>/<?= $totalTrainings ?> (<?= $pct ?>%)
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Ostatnie treningi -->
<?php if (!empty($recent)): ?>
<div class="card">
    <div class="card-header fw-semibold"><i class="bi bi-list-check me-2"></i>Ostatnie treningi</div>
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Data</th><th>Sekcja</th><th>Trening</th><th>Miejsce</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($recent as $r):
            $statusBadge = match($r['status']) {
                'obecny'     => ['success', 'Obecny'],
                'spozniony'  => ['warning', 'Spóźniony'],
                'nieobecny'  => ['danger',  'Nieobecny'],
                'zapisany'   => ['primary', 'Zapisany'],
                default      => ['secondary', $r['status']],
            };
        ?>
            <tr>
                <td class="small"><?= date('d.m.Y H:i', strtotime($r['start_time'])) ?></td>
                <td><span class="badge bg-secondary"><?= View::e($r['sport_name']) ?></span></td>
                <td class="small"><?= View::e($r['training_name'] ?? '—') ?></td>
                <td class="small"><?= View::e($r['location'] ?? '—') ?></td>
                <td><span class="badge bg-<?= $statusBadge[0] ?>"><?= $statusBadge[1] ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
