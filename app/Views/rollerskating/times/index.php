<?php use App\Helpers\View;
use App\Sports\Rollerskating\Models\RollerskatingTimeModel;
?>
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4"><input type="text" name="distance" value="<?= View::e($distanceFilter ?? '') ?>" class="form-control" placeholder="Dystans (np. 500m, 1000m)"></div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Filtruj</button></div>
        <div class="col-md-3"><a href="<?= url('rollerskating/times/create') ?>" class="btn btn-success w-100"><i class="bi bi-plus"></i> Nowy pomiar</a></div>
    </form>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card p-3">
            <h5>Wszystkie pomiary</h5>
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Zawodnik</th><th>Dystans</th><th>Czas</th><th>Data</th><th>PB</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($pagination['data'])): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Brak.</td></tr>
                <?php else: foreach ($pagination['data'] as $r): ?>
                    <tr>
                        <td><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></td>
                        <td><code><?= View::e($r['distance'] ?? '') ?></code></td>
                        <td><strong><?= RollerskatingTimeModel::formatTime((int)$r['time_ms']) ?></strong></td>
                        <td><small><?= format_date($r['record_date']) ?></small></td>
                        <td><?= $r['is_personal_best'] ? '<i class="bi bi-star-fill text-warning"></i>' : '' ?></td>
                        <td class="text-end">
                            <form method="POST" action="<?= url('rollerskating/times/' . (int)$r['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')" class="d-inline">
                                <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card p-3">
            <h5><i class="bi bi-trophy"></i> Ranking PB</h5>
            <?php if (empty($rankings)): ?>
                <div class="text-muted small">Brak rekordów.</div>
            <?php else: $pos = 1; foreach ($rankings as $r): ?>
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span><strong>#<?= $pos++ ?></strong> <?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></span>
                    <span><code><?= View::e($r['distance'] ?? '') ?></code> <strong><?= RollerskatingTimeModel::formatTime((int)$r['time_ms']) ?></strong></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
