<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-bar-chart"></i> Analityka: <?= View::e($club['name']) ?></h4>
    <a href="<?= url('admin/clubs') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Aktywni zawodnicy</div>
            <div class="display-6"><?= (int)$membersCount ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Suma wpłat</div>
            <div class="h4"><?= number_format($paymentsTotal, 2, ',', ' ') ?> zł</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Wydarzenia</div>
            <div class="display-6"><?= (int)$eventsCount ?></div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card p-3 text-center">
            <div class="text-muted small">Subskrypcja</div>
            <div class="h5"><?= View::e($subscription['plan_name'] ?? 'Brak') ?></div>
            <span class="badge bg-<?= ($subscription['status'] ?? '') === 'active' ? 'success' : 'warning' ?>">
                <?= View::e($subscription['status'] ?? 'brak') ?>
            </span>
        </div>
    </div>
</div>

<!-- Sports list -->
<div class="card p-3 mb-4">
    <h6>Sekcje sportowe</h6>
    <?php if (empty($sportsList)): ?>
        <p class="text-muted mb-0">Brak sekcji sportowych.</p>
    <?php else: ?>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($sportsList as $s): ?>
                <span class="badge bg-primary"><?= View::e($s['name']) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card p-3">
            <h6>Nowi zawodnicy (12 mies.)</h6>
            <canvas id="membersMonthChart" height="200"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-3">
            <h6>Wpłaty (12 mies.)</h6>
            <canvas id="paymentsMonthChart" height="200"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-3">
            <h6>Wydarzenia wg sportu</h6>
            <canvas id="eventsSportChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Limits form -->
<div class="card p-3">
    <h6>Limity i notatki admina</h6>
    <form method="POST" action="<?= url('admin/clubs/' . (int)$club['id'] . '/limits') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Max zawodników (override)</label>
                <input type="number" name="max_members_override" class="form-control" min="0"
                       value="<?= View::e($subscription['max_members_override'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Max sportów (override)</label>
                <input type="number" name="max_sports_override" class="form-control" min="0"
                       value="<?= View::e($subscription['max_sports_override'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Custom features (JSON)</label>
                <textarea name="custom_features" class="form-control" rows="1"><?= View::e($subscription['custom_features'] ?? '') ?></textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Notatki</label>
                <textarea name="admin_notes" class="form-control" rows="1"><?= View::e($subscription['admin_notes'] ?? '') ?></textarea>
            </div>
        </div>
        <button class="btn btn-primary btn-sm mt-2"><i class="bi bi-check2"></i> Zapisz limity</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function() {
    const memData = <?= json_encode(array_values($membersPerMonth)) ?>;
    const payData = <?= json_encode(array_values($paymentsPerMonth)) ?>;
    const evtData = <?= json_encode(array_values($eventsPerSport)) ?>;

    // Members per month
    new Chart(document.getElementById('membersMonthChart'), {
        type: 'bar',
        data: {
            labels: memData.map(r => r.month),
            datasets: [{
                label: 'Nowi zawodnicy',
                data: memData.map(r => parseInt(r.total)),
                backgroundColor: '#6f42c1'
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    // Payments per month
    new Chart(document.getElementById('paymentsMonthChart'), {
        type: 'line',
        data: {
            labels: payData.map(r => r.month),
            datasets: [{
                label: 'Wpłaty (zł)',
                data: payData.map(r => parseFloat(r.total)),
                borderColor: '#198754',
                backgroundColor: '#19875433',
                fill: true,
                tension: 0.3
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    // Events per sport (doughnut)
    const colors = ['#0d6efd','#198754','#dc3545','#ffc107','#6f42c1','#fd7e14','#20c997','#0dcaf0'];
    new Chart(document.getElementById('eventsSportChart'), {
        type: 'doughnut',
        data: {
            labels: evtData.map(r => r.sport_name),
            datasets: [{
                data: evtData.map(r => parseInt(r.total)),
                backgroundColor: evtData.map((_, i) => colors[i % colors.length])
            }]
        },
        options: { responsive: true }
    });
})();
</script>
