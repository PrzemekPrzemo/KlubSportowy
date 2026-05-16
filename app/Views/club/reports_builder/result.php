<?php
use App\Helpers\View;

$chart = $result['chart_data'] ?? null;
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-bar-chart text-primary"></i> <?= View::e($report['name']) ?></h1>
        <?php if (!empty($report['description'])): ?>
            <p class="text-muted small mb-0"><?= View::e($report['description']) ?></p>
        <?php endif; ?>
        <small class="text-muted">
            Zwrócono <strong><?= (int)$result['total'] ?></strong> wierszy
            w <?= (int)$result['duration_ms'] ?> ms.
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('club/reports-builder/' . (int)$report['id'] . '/export.csv') ?>" class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv"></i> CSV
        </a>
        <a href="<?= url('club/reports-builder/' . (int)$report['id'] . '/export.pdf') ?>" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>
        <?php if ((int)($report['is_template'] ?? 0) === 0): ?>
            <a href="<?= url('club/reports-builder/' . (int)$report['id'] . '/edit') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil"></i> Edytuj
            </a>
        <?php endif; ?>
        <a href="<?= url('club/reports-builder') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Lista
        </a>
    </div>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= View::e($flashError) ?></div>
<?php endif; ?>

<?php if ($chart !== null): ?>
<div class="card mb-3">
    <div class="card-header bg-light"><strong>Wykres</strong></div>
    <div class="card-body">
        <canvas id="reportChart" height="100"></canvas>
    </div>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header bg-light d-flex justify-content-between">
        <strong>Wyniki</strong>
        <input type="text" id="tableSearch" class="form-control form-control-sm" style="max-width:250px;"
               placeholder="Szukaj w wynikach...">
    </div>
    <div class="card-body p-0">
        <?php if (empty($result['rows'])): ?>
            <div class="p-4 text-center text-muted">Brak wyników dla tego raportu.</div>
        <?php else: ?>
            <div class="table-responsive" style="max-height:600px;overflow:auto;">
                <table class="table table-sm table-hover mb-0" id="resultTable">
                    <thead class="table-light sticky-top">
                        <tr>
                            <?php foreach ($result['columns'] as $c): ?>
                                <th data-key="<?= View::e($c['key']) ?>" style="cursor:pointer;">
                                    <?= View::e($c['label']) ?>
                                    <small class="text-muted">(<?= View::e($c['type']) ?>)</small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['rows'] as $row): ?>
                            <tr>
                                <?php foreach ($result['columns'] as $c): ?>
                                    <td><?= View::e($row[$c['key']] ?? '') ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($chart !== null): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const data = <?= json_encode($chart, JSON_UNESCAPED_UNICODE) ?>;
    const ctx = document.getElementById('reportChart').getContext('2d');
    const palette = ['#0d6efd','#198754','#ffc107','#dc3545','#6f42c1','#0dcaf0','#fd7e14','#20c997','#6610f2','#d63384'];
    new Chart(ctx, {
        type: data.type,
        data: {
            labels: data.labels,
            datasets: [{
                label: data.y_label,
                data: data.values,
                backgroundColor: (data.type === 'pie' || data.type === 'doughnut')
                    ? data.labels.map((_, i) => palette[i % palette.length])
                    : '#0d6efd',
                borderColor: '#0d6efd',
                borderWidth: 1,
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });
})();
</script>
<?php endif; ?>

<script>
// Prosta wyszukiwarka po tabeli + sortowanie kolumn
(function () {
    const input = document.getElementById('tableSearch');
    const table = document.getElementById('resultTable');
    if (!input || !table) return;

    input.addEventListener('input', () => {
        const q = input.value.toLowerCase();
        Array.from(table.tBodies[0].rows).forEach(tr => {
            tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });

    // Kliknięcie nagłówka = sortuj
    Array.from(table.tHead.rows[0].cells).forEach((th, idx) => {
        let asc = true;
        th.addEventListener('click', () => {
            const rows = Array.from(table.tBodies[0].rows);
            rows.sort((a, b) => {
                const av = a.cells[idx].textContent.trim();
                const bv = b.cells[idx].textContent.trim();
                const an = parseFloat(av), bn = parseFloat(bv);
                if (!isNaN(an) && !isNaN(bn)) return asc ? an - bn : bn - an;
                return asc ? av.localeCompare(bv) : bv.localeCompare(av);
            });
            asc = !asc;
            rows.forEach(r => table.tBodies[0].appendChild(r));
        });
    });
})();
</script>
