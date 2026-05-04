<?php
use App\Helpers\View;
use App\Sports\Triathlon\Models\TriathlonResultModel;
?>

<h4 class="mb-3">Zawodnicy — Triathlon</h4>

<?php if (empty($athletes)): ?>
<div class="alert alert-info">Brak zawodników z wynikami triatlonowymi.</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($athletes as $a): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <strong><?= View::e($a['last_name']) ?> <?= View::e($a['first_name']) ?></strong>
                <small class="text-muted ms-2"><?= View::e($a['member_number'] ?? '') ?></small>
            </div>
            <div class="card-body p-2">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Dystans</th><th>Total PB</th><th>Pływanie</th><th>Rower</th><th>Bieg</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($distances as $dist): ?>
                        <?php $pb = $a['pbs'][$dist] ?? null; ?>
                        <tr class="<?= !$pb ? 'text-muted' : '' ?>">
                            <td><?= strtoupper($dist) ?></td>
                            <td class="fw-bold"><?= TriathlonResultModel::formatTime($pb ? (int)$pb['total_time'] : null) ?></td>
                            <td class="small"><?= TriathlonResultModel::formatTime($pb ? (int)$pb['swim_time'] : null) ?></td>
                            <td class="small"><?= TriathlonResultModel::formatTime($pb ? (int)$pb['bike_time'] : null) ?></td>
                            <td class="small"><?= TriathlonResultModel::formatTime($pb ? (int)$pb['run_time'] : null) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
