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

<div class="mt-3">
    <a href="<?= url('admin/clubs/create-full') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nowy klub (pełny formularz)
    </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function() {
    const revData = <?= json_encode(array_values($revenueTrend)) ?>;
    const clubData = <?= json_encode(array_values($clubsGrowth)) ?>;
    const memData = <?= json_encode(array_values($membersGrowth)) ?>;

    function makeChart(id, labels, data, label, color) {
        new Chart(document.getElementById(id), {
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
        'Przychód', '#198754');
    makeChart('clubsChart',
        clubData.map(r => r.month), clubData.map(r => parseInt(r.total)),
        'Kluby', '#0d6efd');
    makeChart('membersChart',
        memData.map(r => r.month), memData.map(r => parseInt(r.total)),
        'Zawodnicy', '#6f42c1');
})();
</script>
