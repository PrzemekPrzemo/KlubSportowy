<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <form method="GET" action="<?= url('analytics') ?>" class="d-flex align-items-center gap-2">
        <label class="form-label mb-0">Rok:</label>
        <select name="year" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 5; $y--): ?>
                <option value="<?= $y ?>" <?= ($year ?? date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>

<?php $a = $analytics ?? []; ?>

<!-- Summary cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card text-center p-3">
            <div class="text-muted small">Łącznie zawodników</div>
            <h3 class="mb-0 text-primary"><?= (int)($a['totalMembers'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card text-center p-3">
            <div class="text-muted small">Przychód w <?= (int)($year ?? date('Y')) ?></div>
            <h3 class="mb-0 text-success"><?= format_money($a['totalRevenue'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card text-center p-3">
            <div class="text-muted small">Śr. frekwencja / trening</div>
            <h3 class="mb-0 text-info"><?= number_format($a['avgAttendance'] ?? 0, 1, ',', '') ?></h3>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card text-center p-3">
            <div class="text-muted small">Aktywne sekcje</div>
            <h3 class="mb-0 text-warning"><?= (int)($a['activeSports'] ?? 0) ?></h3>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card p-3">
            <h6 class="card-title"><i class="bi bi-graph-up"></i> Nowi zawodnicy / miesiąc</h6>
            <canvas id="chartMembers" height="200"></canvas>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-3">
            <h6 class="card-title"><i class="bi bi-cash-coin"></i> Płatności / miesiąc</h6>
            <canvas id="chartPayments" height="200"></canvas>
        </div>
    </div>
</div>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card p-3">
            <h6 class="card-title"><i class="bi bi-pie-chart"></i> Zawodnicy wg sekcji</h6>
            <canvas id="chartSports" height="200"></canvas>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card p-3">
            <h6 class="card-title"><i class="bi bi-bar-chart-line"></i> Frekwencja na treningach</h6>
            <canvas id="chartAttendance" height="200"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function(){
    var months = <?= json_encode($a['months'] ?? []) ?>;
    var labels = months.map(function(m){ var p=m.split('-'); return p[1]+'/'+p[0]; });

    // Members growth (line)
    new Chart(document.getElementById('chartMembers'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Nowi zawodnicy',
                data: <?= json_encode($a['membersGrowth'] ?? []) ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: { responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } }
    });

    // Payments (bar)
    new Chart(document.getElementById('chartPayments'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Kwota (zł)',
                data: <?= json_encode($a['paymentsBars'] ?? []) ?>,
                backgroundColor: 'rgba(25,135,84,.7)'
            }]
        },
        options: { responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true } } }
    });

    // Members per sport (doughnut)
    var sportData = <?= json_encode($a['membersPerSport'] ?? (object)[]) ?>;
    var sportLabels = Object.keys(sportData);
    var sportValues = Object.values(sportData);
    var sportColors = ['#0d6efd','#198754','#ffc107','#dc3545','#6f42c1','#fd7e14','#20c997','#0dcaf0'];
    new Chart(document.getElementById('chartSports'), {
        type: 'doughnut',
        data: {
            labels: sportLabels,
            datasets: [{
                data: sportValues,
                backgroundColor: sportColors.slice(0, sportLabels.length)
            }]
        },
        options: { responsive:true }
    });

    // Attendance (bar)
    new Chart(document.getElementById('chartAttendance'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Obecności',
                data: <?= json_encode($a['attendanceBars'] ?? []) ?>,
                backgroundColor: 'rgba(13,202,240,.7)'
            }]
        },
        options: { responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } }
    });
})();
</script>
