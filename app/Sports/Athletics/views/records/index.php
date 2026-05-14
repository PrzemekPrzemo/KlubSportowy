<?php use App\Helpers\View;
use App\Sports\Athletics\Models\AthleticsRecordModel;
?>
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <select name="discipline" class="form-select">
                <option value="">— wszystkie dyscypliny —</option>
                <?php foreach ($disciplines as $d): ?>
                    <option value="<?= (int)$d['id'] ?>" <?= (int)($discFilter ?? 0)===(int)$d['id']?'selected':'' ?>>
                        <?= View::e($d['name']) ?> (<?= View::e($d['short_code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search"></i></button></div>
        <div class="col-md-3"><a href="<?= url('athletics/records/create') ?>" class="btn btn-success w-100"><i class="bi bi-plus"></i> Nowy wynik</a></div>
    </form>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card p-3">
            <h5>Wyniki</h5>
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Zawodnik</th><th>Dyscyplina</th><th>Wynik</th><th>Data</th><th>Zawody</th><th>PB</th><th>KR</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($pagination['data'])): ?>
                    <tr><td colspan="8" class="text-center text-muted py-3">Brak wyników.</td></tr>
                <?php else: foreach ($pagination['data'] as $r): ?>
                    <tr>
                        <td><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></td>
                        <td><code><?= View::e($r['discipline_name'] ?? '') ?></code></td>
                        <td><strong><?= AthleticsRecordModel::formatResult((float)$r['result_value'], $r['result_unit']) ?></strong></td>
                        <td><small><?= format_date($r['record_date']) ?></small></td>
                        <td><small><?= View::e($r['competition_name'] ?? '') ?></small></td>
                        <td><?= $r['is_personal_best'] ? '<i class="bi bi-star-fill text-warning"></i>' : '' ?></td>
                        <td><?= $r['is_club_record'] ? '<i class="bi bi-trophy-fill text-primary"></i>' : '' ?></td>
                        <td class="text-end">
                            <form method="POST" action="<?= url('athletics/records/' . (int)$r['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')" class="d-inline">
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
        <?php if (!empty($clubRecords)): ?>
        <div class="card p-3 mb-3">
            <h5><i class="bi bi-trophy"></i> Rekordy klubu</h5>
            <?php foreach ($clubRecords as $r): ?>
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span><?= View::e($r['discipline_name'] ?? '') ?> — <?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></span>
                    <strong><?= AthleticsRecordModel::formatResult((float)$r['result_value'], $r['result_unit']) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="card p-3">
            <h5><i class="bi bi-star"></i> Ranking PB (Top 10)</h5>
            <?php if (empty($pbs)): ?><div class="text-muted small">Brak rekordów.</div>
            <?php else: $pos = 1; foreach ($pbs as $r): ?>
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span><strong>#<?= $pos++ ?></strong> <?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></span>
                    <span><?= View::e($r['discipline_name'] ?? '') ?> <strong><?= AthleticsRecordModel::formatResult((float)$r['result_value'], $r['result_unit']) ?></strong></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
