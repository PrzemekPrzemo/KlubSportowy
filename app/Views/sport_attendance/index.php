<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Frekwencja — <?= View::e($sportName) ?></h4>
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label class="form-label mb-0">Rok:</label>
        <select name="year" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <?php foreach ($years as $y): ?>
                <option value="<?= (int)$y ?>" <?= (int)$y === $year ? 'selected' : '' ?>><?= (int)$y ?></option>
            <?php endforeach; ?>
            <?php if (!in_array(date('Y'), $years)): ?>
                <option value="<?= date('Y') ?>" <?= (int)date('Y') === $year ? 'selected' : '' ?>><?= date('Y') ?></option>
            <?php endif; ?>
        </select>
    </form>
</div>

<?php
$monthNames = ['', 'Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 'Lip', 'Sie', 'Wrz', 'Paź', 'Lis', 'Gru'];
?>

<?php if (empty($months)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Brak danych treningowych dla <?= View::e($sportName) ?> w roku <?= (int)$year ?>.
    Upewnij się, że treningi mają przypisaną dyscyplinę sportową.
</div>
<?php else: ?>

<div class="card">
    <div class="card-header text-muted small">
        Frekwencja za rok <?= (int)$year ?> &mdash; <?= count($rows) ?> zawodników, <?= count($months) ?> miesięcy z treningami
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Zawodnik</th>
                    <th>Nr</th>
                    <?php foreach ($months as $m): ?>
                        <th class="text-center"><?= $monthNames[(int)$m] ?></th>
                    <?php endforeach; ?>
                    <th class="text-center">Razem</th>
                    <th class="text-center">%</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="<?= 3 + count($months) + 2 ?>" class="text-center text-muted py-4">
                        Brak danych frekwencji. Sprawdź, czy zawodnicy są zarejestrowani na treningach.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r):
                    $pct = $r['sum_total'] > 0 ? round($r['sum_attended'] / $r['sum_total'] * 100) : 0;
                    $pctClass = $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger');
                ?>
                <tr>
                    <td><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></td>
                    <td class="text-muted small"><?= View::e($r['member_number']) ?></td>
                    <?php foreach ($months as $m): ?>
                        <?php $cell = $r['months'][(int)$m] ?? null; ?>
                        <td class="text-center">
                            <?php if ($cell): ?>
                                <?php $cellPct = $cell['total'] > 0 ? round($cell['attended'] / $cell['total'] * 100) : 0; ?>
                                <span class="badge bg-<?= $cellPct >= 80 ? 'success' : ($cellPct >= 50 ? 'warning text-dark' : 'danger') ?>"
                                      title="<?= $cell['attended'] ?>/<?= $cell['total'] ?> treningów">
                                    <?= $cell['attended'] ?>/<?= $cell['total'] ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="text-center fw-bold"><?= $r['sum_attended'] ?>/<?= $r['sum_total'] ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $pctClass ?><?= $pct >= 50 && $pct < 80 ? ' text-dark' : '' ?>">
                            <?= $pct ?>%
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-2 text-muted small">
    <span class="badge bg-success me-1">≥80%</span> Wysoka frekwencja &nbsp;
    <span class="badge bg-warning text-dark me-1">50–79%</span> Średnia &nbsp;
    <span class="badge bg-danger me-1">&lt;50%</span> Niska frekwencja
</div>

<?php endif; ?>
