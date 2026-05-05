<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-graph-up text-primary me-2"></i>
        Raport prowizji <?= (int)$year ?><?= $month ? '-' . str_pad((string)$month, 2, '0', STR_PAD_LEFT) : '' ?>
    </h3>
    <div>
        <a href="<?= url('club/trainers/commissions') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Wróć
        </a>
        <a href="<?= url('club/trainers/commissions/report?format=csv&year=' . (int)$year . ($month ? '&month=' . (int)$month : '')) ?>"
           class="btn btn-success">
            <i class="bi bi-download"></i> Eksport CSV
        </a>
    </div>
</div>

<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-auto">
        <label class="form-label small mb-0">Rok</label>
        <input type="number" name="year" class="form-control form-control-sm" value="<?= (int)$year ?>" min="2020" max="2099">
    </div>
    <div class="col-auto">
        <label class="form-label small mb-0">Miesiąc <small class="text-muted">(puste = cały rok)</small></label>
        <select name="month" class="form-select form-select-sm">
            <option value="">— cały rok —</option>
            <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $m === (int)$month ? 'selected' : '' ?>>
                    <?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="col-auto">
        <button class="btn btn-sm btn-primary">Filtruj</button>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th>Trener</th>
                    <th class="text-end">Wpisów</th>
                    <th class="text-end">Suma</th>
                    <th class="text-end">Naliczone</th>
                    <th class="text-end">Wypłacone</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $totalSum     = 0.0;
                $totalAccrued = 0.0;
                $totalPaidOut = 0.0;
                ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Brak danych za wybrany okres.</td></tr>
                <?php else: foreach ($rows as $r):
                    $totalSum     += (float)$r['total'];
                    $totalAccrued += (float)$r['accrued'];
                    $totalPaidOut += (float)$r['paid_out'];
                ?>
                    <tr>
                        <td>
                            <strong><?= View::e($r['full_name'] ?? $r['username']) ?></strong>
                            <small class="text-muted">@<?= View::e($r['username']) ?></small>
                        </td>
                        <td class="text-end"><?= (int)$r['items'] ?></td>
                        <td class="text-end font-monospace fw-bold"><?= format_money($r['total']) ?></td>
                        <td class="text-end text-warning"><?= format_money($r['accrued']) ?></td>
                        <td class="text-end text-success"><?= format_money($r['paid_out']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <?php if (!empty($rows)): ?>
                <tfoot class="table-light">
                    <tr>
                        <th>Razem (<?= count($rows) ?> trenerów)</th>
                        <th></th>
                        <th class="text-end font-monospace fw-bold"><?= format_money($totalSum) ?></th>
                        <th class="text-end text-warning"><?= format_money($totalAccrued) ?></th>
                        <th class="text-end text-success"><?= format_money($totalPaidOut) ?></th>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
