<?php use App\Helpers\View; ?>

<div class="row g-4">
    <!-- Raport: Lista zawodnikow -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-people text-primary"></i> Lista zawodników</h5>
                <p class="card-text text-muted">Pełna lista członków klubu z przypisanymi sekcjami sportowymi i statusem.</p>
                <div class="d-flex gap-2">
                    <a href="<?= url('reports/members-pdf') ?>" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                    <a href="<?= url('reports/members-csv') ?>" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-filetype-csv"></i> CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Raport: Finanse -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-cash-coin text-success"></i> Raport finansowy</h5>
                <p class="card-text text-muted">Zestawienie wpłat i płatności za wybrany rok.</p>
                <form class="d-flex gap-2 align-items-end" id="financeReportForm">
                    <div>
                        <label class="form-label small mb-1">Rok</label>
                        <select name="year" class="form-select form-select-sm" style="width:100px;" id="financeYear">
                            <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 5; $y--): ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <a href="<?= url('reports/finances-pdf') ?>" class="btn btn-outline-danger btn-sm" id="financePdfLink">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                    <a href="<?= url('reports/finances-csv') ?>" class="btn btn-outline-success btn-sm" id="financeCsvLink">
                        <i class="bi bi-filetype-csv"></i> CSV
                    </a>
                </form>
            </div>
        </div>
    </div>

    <!-- Raport: Protokol wydarzenia -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-calendar-event text-info"></i> Protokol wydarzenia</h5>
                <p class="card-text text-muted">Protokol PDF dla wybranego wydarzenia. Przejdz do listy wydarzen i wybierz wydarzenie.</p>
                <a href="<?= url('events') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-right"></i> Lista wydarzen
                </a>
            </div>
        </div>
    </div>

    <!-- Raport: Karta czlonkowska -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-person-badge text-warning"></i> Karta członkowska</h5>
                <p class="card-text text-muted">Karta członkowska w formacie A5 z kodem QR. Przejdź do profilu zawodnika.</p>
                <a href="<?= url('members') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-right"></i> Lista zawodników
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var yearSelect = document.getElementById('financeYear');
    var pdfLink    = document.getElementById('financePdfLink');
    var csvLink    = document.getElementById('financeCsvLink');
    var basePdf    = '<?= url('reports/finances-pdf') ?>';
    var baseCsv    = '<?= url('reports/finances-csv') ?>';

    function updateLinks() {
        var y = yearSelect.value;
        pdfLink.href = basePdf + '?year=' + y;
        csvLink.href = baseCsv + '?year=' + y;
    }

    yearSelect.addEventListener('change', updateLinks);
    updateLinks();
});
</script>
