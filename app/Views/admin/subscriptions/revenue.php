<?php use App\Helpers\View; ?>

<div class="mb-3">
    <a href="<?= url('admin/subscriptions') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Wróć do subskrypcji</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center p-3">
            <div class="text-muted small">MRR (miesięczny)</div>
            <div class="fs-4 fw-bold text-primary"><?= number_format($mrr ?? 0, 2, ',', ' ') ?> zł</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <div class="text-muted small">ARR (roczny)</div>
            <div class="fs-4 fw-bold text-success"><?= number_format($arr ?? 0, 2, ',', ' ') ?> zł</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <div class="text-muted small">Aktywne kluby</div>
            <div class="fs-4 fw-bold text-info"><?= (int)($activeClubs ?? 0) ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center p-3">
            <div class="text-muted small">Churn rate</div>
            <div class="fs-4 fw-bold text-danger"><?= $churnRate ?? 0 ?>%</div>
        </div>
    </div>
</div>

<div class="card p-3">
    <h5 class="mb-3">Przychody miesięczne (ostatnie 12 mies.)</h5>
    <canvas id="revenueChart" height="100"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var rawData = <?= json_encode($revenuePerMonth ?? []) ?>;
    var labels = rawData.map(function(r) { return r.month; });
    var values = rawData.map(function(r) { return parseFloat(r.total) || 0; });

    new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Przychód (zł)',
                data: values,
                backgroundColor: 'rgba(13, 110, 253, 0.6)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { callback: function(v) { return v + ' zł'; } } }
            }
        }
    });
});
</script>
