<?php
use App\Helpers\Csrf;
use App\Helpers\View;
/** @var array $fees */
$periodOptions = [
    'monthly'   => 'Miesieczna',
    'quarterly' => 'Kwartalna',
    'yearly'    => 'Roczna',
    'one_time'  => 'Jednorazowa',
];
?>
<section class="py-5" style="background:#f6f7fb;min-height:80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <?php include __DIR__ . '/_progress.php'; ?>

                        <h2 class="mb-1">Skladki czlonkowskie</h2>
                        <p class="text-muted">Skonfiguruj <strong>1-5 stawek</strong>. Bedziesz mogl je edytowac pozniej.</p>

                        <?php if (!empty($flashError)): ?>
                            <div class="alert alert-danger"><?= View::e($flashError) ?></div>
                        <?php endif; ?>

                        <form method="post" action="<?= url('trial/fees') ?>" id="feesForm">
                            <?= Csrf::field() ?>

                            <div id="feesContainer">
                                <?php foreach ($fees as $i => $fee): ?>
                                    <div class="row g-2 align-items-end mb-2 fee-row">
                                        <div class="col-md-5">
                                            <label class="form-label small mb-0">Nazwa</label>
                                            <input type="text" name="fee_name[]" class="form-control"
                                                   value="<?= View::e($fee['name'] ?? '') ?>" required
                                                   maxlength="120" placeholder="np. Skladka miesieczna">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small mb-0">Kwota (PLN)</label>
                                            <input type="number" name="fee_amount[]" class="form-control"
                                                   value="<?= View::e($fee['amount'] ?? '') ?>"
                                                   step="0.01" min="0.01" required placeholder="100.00">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small mb-0">Okres</label>
                                            <select name="fee_period[]" class="form-select">
                                                <?php foreach ($periodOptions as $k => $label): ?>
                                                    <option value="<?= $k ?>" <?= (($fee['period'] ?? 'monthly') === $k) ? 'selected' : '' ?>><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-outline-danger btn-sm remove-fee" title="Usun"
                                                    <?= count($fees) <= 1 ? 'disabled' : '' ?>>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addFee">
                                <i class="bi bi-plus-lg"></i> Dodaj stawke
                            </button>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="<?= url('trial/sports') ?>" class="btn btn-link text-muted">
                                    <i class="bi bi-arrow-left"></i> Wstecz
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Dalej <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    var MAX = 5;
    var container = document.getElementById('feesContainer');
    var addBtn = document.getElementById('addFee');

    function refreshRemove() {
        var rows = container.querySelectorAll('.fee-row');
        rows.forEach(function(r){
            r.querySelector('.remove-fee').disabled = (rows.length <= 1);
        });
        addBtn.disabled = (rows.length >= MAX);
    }

    addBtn.addEventListener('click', function() {
        var rows = container.querySelectorAll('.fee-row');
        if (rows.length >= MAX) return;
        var first = rows[0];
        var clone = first.cloneNode(true);
        clone.querySelectorAll('input').forEach(function(i){ i.value = ''; });
        clone.querySelector('select').selectedIndex = 0;
        container.appendChild(clone);
        bindRow(clone);
        refreshRemove();
    });

    function bindRow(row) {
        row.querySelector('.remove-fee').addEventListener('click', function(){
            row.remove();
            refreshRemove();
        });
    }

    container.querySelectorAll('.fee-row').forEach(bindRow);
    refreshRemove();
})();
</script>
