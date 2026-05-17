<?php use App\Helpers\View; ?>
<div class="row">
<div class="col-lg-7">
<div class="card">
<table class="table table-hover mb-0">
    <thead class="table-light"><tr>
        <th>Scope</th><th>Plan</th><th>Klub</th>
        <th>%</th><th>Fixed</th><th>Min</th><th>Max</th>
        <th>Od</th><th>Do</th><th>Aktyw.</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($rules as $r): ?>
        <tr>
            <td><span class="badge bg-secondary"><?= View::e($r['scope']) ?></span></td>
            <td><?= View::e((string)($r['plan_code'] ?? '')) ?></td>
            <td><?= View::e((string)($r['club_name'] ?? '')) ?></td>
            <td><?= number_format((float)$r['fee_percent'], 2) ?>%</td>
            <td><?= number_format(((int)$r['fee_fixed_cents'])/100, 2) ?></td>
            <td><?= number_format(((int)$r['min_fee_cents'])/100, 2) ?></td>
            <td><?= $r['max_fee_cents'] !== null ? number_format(((int)$r['max_fee_cents'])/100, 2) : '∞' ?></td>
            <td class="small"><?= View::e((string)$r['effective_from']) ?></td>
            <td class="small"><?= View::e((string)($r['effective_until'] ?? '')) ?></td>
            <td><?= ((int)$r['active']) ? '<span class="badge bg-success">tak</span>' : '<span class="badge bg-secondary">nie</span>' ?></td>
            <td>
                <form method="POST" action="<?= url('admin/platform/payments/fee-rules/'.(int)$r['id'].'/delete') ?>" style="display:inline">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Usunąć regułę?')"><i class="bi bi-trash"></i></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>

<div class="col-lg-5">
<form method="POST" action="<?= url('admin/platform/payments/fee-rules/store') ?>" class="card p-3">
    <?= csrf_field() ?>
    <h5>Nowa reguła</h5>
    <div class="mb-2"><label class="form-label">Scope</label>
        <select name="scope" class="form-select">
            <option value="global">global</option>
            <option value="plan">plan</option>
            <option value="club_override">club_override</option>
        </select>
    </div>
    <div class="mb-2"><label class="form-label">Plan code (gdy scope=plan)</label>
        <select name="plan_code" class="form-select">
            <option value="">— wybierz —</option>
            <?php foreach ($plans as $p): ?>
                <option value="<?= View::e($p['code']) ?>"><?= View::e($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-2"><label class="form-label">Club ID (gdy scope=club_override)</label>
        <input type="number" name="club_id" class="form-control" placeholder="puste = nie dotyczy">
    </div>
    <div class="row g-2 mb-2">
        <div class="col-6"><label class="form-label">Fee %</label>
            <input type="number" step="0.01" name="fee_percent" value="2.00" class="form-control"></div>
        <div class="col-6"><label class="form-label">Fixed (grosze)</label>
            <input type="number" name="fee_fixed_cents" value="0" class="form-control"></div>
        <div class="col-6"><label class="form-label">Min fee (grosze)</label>
            <input type="number" name="min_fee_cents" value="0" class="form-control"></div>
        <div class="col-6"><label class="form-label">Max fee (grosze)</label>
            <input type="number" name="max_fee_cents" class="form-control" placeholder="∞"></div>
        <div class="col-6"><label class="form-label">Od</label>
            <input type="date" name="effective_from" value="<?= date('Y-m-d') ?>" class="form-control"></div>
        <div class="col-6"><label class="form-label">Do (puste = bezterm.)</label>
            <input type="date" name="effective_until" class="form-control"></div>
    </div>
    <div class="form-check mb-3">
        <input type="checkbox" name="active" value="1" class="form-check-input" id="active" checked>
        <label for="active" class="form-check-label">Aktywna</label>
    </div>
    <button class="btn btn-primary"><i class="bi bi-plus"></i> DODAJ REGUŁĘ</button>
</form>
</div>
</div>
