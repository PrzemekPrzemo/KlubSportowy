<?php use App\Helpers\View; ?>
<div class="row g-3 mb-4">
    <div class="col-sm-4 col-md-2">
        <div class="card p-3 text-center">
            <div class="text-muted small">Kluby aktywne</div>
            <div class="display-6"><?= (int)$metrics['clubs_active'] ?></div>
            <div class="text-muted small">z <?= (int)$metrics['clubs'] ?></div>
            <a href="<?= url('admin/clubs') ?>" class="stretched-link small">Zarządzaj</a>
        </div>
    </div>
    <div class="col-sm-4 col-md-2">
        <div class="card p-3 text-center">
            <div class="text-muted small">Użytkownicy</div>
            <div class="display-6"><?= (int)$metrics['users'] ?></div>
        </div>
    </div>
    <div class="col-sm-4 col-md-2">
        <div class="card p-3 text-center">
            <div class="text-muted small">Zawodnicy</div>
            <div class="display-6"><?= (int)$metrics['members'] ?></div>
        </div>
    </div>
    <div class="col-sm-4 col-md-2">
        <div class="card p-3 text-center">
            <div class="text-muted small">Sporty</div>
            <div class="display-6"><?= (int)$metrics['sports'] ?></div>
            <a href="<?= url('admin/sports') ?>" class="stretched-link small">Katalog</a>
        </div>
    </div>
    <div class="col-sm-4 col-md-2">
        <div class="card p-3 text-center">
            <div class="text-muted small">Sekcje</div>
            <div class="display-6"><?= (int)$metrics['club_sports'] ?></div>
        </div>
    </div>
    <div class="col-sm-4 col-md-2">
        <div class="card p-3 text-center">
            <div class="text-muted small">Przychód (rok)</div>
            <div class="h4"><?= number_format($revenueThisYear, 2, ',', ' ') ?> zł</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card p-3">
            <h6 class="card-title">Przychód (12 mies.)</h6>
            <canvas id="revenueChart" height="200"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-3">
            <h6 class="card-title">Nowe kluby (12 mies.)</h6>
            <canvas id="clubsChart" height="200"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-3">
            <h6 class="card-title">Nowi zawodnicy (12 mies.)</h6>
            <canvas id="membersChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Alerts: expiring subscriptions + failed invoices -->
<div class="row g-3 mb-4">
    <?php if (!empty($expiringSoon)): ?>
    <div class="col-md-6">
        <div class="alert alert-warning mb-0">
            <i class="bi bi-exclamation-triangle"></i>
            <strong><?= count($expiringSoon) ?></strong> subskrypcji wygasa w ciagu 7 dni
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md-6">
        <div class="alert alert-<?= $failedInvoices > 0 ? 'danger' : 'success' ?> mb-0">
            <i class="bi bi-receipt"></i>
            <?php if ($failedInvoices > 0): ?>
                <strong><?= $failedInvoices ?></strong> zaleglych faktur
            <?php else: ?>
                Brak zaleglych faktur
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Top 10 clubs by member count -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-trophy"></i> Top 10 klubow</h6></div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr><th>#</th><th>Nazwa</th><th>Plan</th><th>Zawodnicy</th><th>Miasto</th></tr>
            </thead>
            <tbody>
                <?php foreach ($topClubs as $i => $tc): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><a href="<?= url('admin/clubs/' . $tc['id'] . '/analytics') ?>"><?= e($tc['name']) ?></a></td>
                    <td><?= e($tc['plan_name']) ?></td>
                    <td><?= (int)$tc['members_count'] ?></td>
                    <td><?= e($tc['city'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($topClubs)): ?>
                <tr><td colspan="5" class="text-muted text-center">Brak danych</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent registrations -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history"></i> Ostatnie rejestracje</h6></div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr><th>Nazwa</th><th>Miasto</th><th>Data rejestracji</th></tr>
            </thead>
            <tbody>
                <?php foreach ($recentClubs as $rc): ?>
                <tr>
                    <td><a href="<?= url('admin/clubs/' . $rc['id'] . '/analytics') ?>"><?= e($rc['name']) ?></a></td>
                    <td><?= e($rc['city'] ?? '—') ?></td>
                    <td><?= format_datetime($rc['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recentClubs)): ?>
                <tr><td colspan="3" class="text-muted text-center">Brak danych</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 mb-4">
    <a href="<?= url('admin/clubs/create-full') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nowy klub (pelny formularz)
    </a>
</div>

<!-- Revenue from billing invoices (12 months) -->
<?php if (!empty($revenueMonthly)): ?>
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card p-3">
            <h6 class="card-title">Przychod z faktur (12 mies.)</h6>
            <canvas id="invoiceRevenueChart" height="200"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function() {
    const revData = <?= json_encode(array_values($revenueTrend)) ?>;
    const clubData = <?= json_encode(array_values($clubsGrowth)) ?>;
    const memData = <?= json_encode(array_values($membersGrowth)) ?>;
    const invRevData = <?= json_encode(array_values($revenueMonthly)) ?>;

    function makeChart(id, labels, data, label, color) {
        const el = document.getElementById(id);
        if (!el) return;
        new Chart(el, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: data,
                    borderColor: color,
                    backgroundColor: color + '33',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    makeChart('revenueChart',
        revData.map(r => r.month), revData.map(r => parseFloat(r.total)),
        'Przychod', '#198754');
    makeChart('clubsChart',
        clubData.map(r => r.month), clubData.map(r => parseInt(r.total)),
        'Kluby', '#0d6efd');
    makeChart('membersChart',
        memData.map(r => r.month), memData.map(r => parseInt(r.total)),
        'Zawodnicy', '#6f42c1');

    if (invRevData.length > 0) {
        makeChart('invoiceRevenueChart',
            invRevData.map(r => r.month), invRevData.map(r => parseFloat(r.total)),
            'Przychod z faktur', '#fd7e14');
    }
})();
</script>
