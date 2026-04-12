<?php use App\Helpers\View; ?>
<?php if ($currentSub): ?>
    <div class="alert alert-info">
        Aktualny plan: <strong><?= View::e($currentSub['plan_name']) ?></strong>
        (<?= View::e($currentSub['billing_cycle']) ?>)
        • ważny do: <?= format_date($currentSub['valid_until']) ?>
        • status: <span class="badge bg-<?= $currentSub['status'] === 'active' ? 'success' : 'warning' ?>"><?= View::e($currentSub['status']) ?></span>
    </div>
<?php endif; ?>

<div class="row g-3">
    <?php foreach ($plans as $p):
        $isCurrent = $currentSub && (int)$currentSub['plan_id'] === (int)$p['id'];
    ?>
        <div class="col-md-3">
            <div class="card p-3 h-100 <?= $isCurrent ? 'border-primary border-2' : '' ?>">
                <h5><?= View::e($p['name']) ?></h5>
                <?php if ($isCurrent): ?><span class="badge bg-primary mb-2">aktualny</span><?php endif; ?>
                <div class="display-6"><?= format_money($p['price_monthly']) ?></div>
                <div class="small text-muted">/miesiąc</div>
                <div class="small"><?= format_money($p['price_yearly']) ?> /rok</div>
                <hr>
                <ul class="small list-unstyled">
                    <li>Zawodnicy: <?= $p['max_members'] !== null ? (int)$p['max_members'] : '∞' ?></li>
                    <li>Sekcje: <?= $p['max_sports'] !== null ? (int)$p['max_sports'] : '∞' ?></li>
                </ul>
                <?php if (!$isCurrent): ?>
                    <form method="POST" action="<?= url('billing/upgrade') ?>" class="mt-auto">
                        <?= csrf_field() ?>
                        <input type="hidden" name="plan_id" value="<?= (int)$p['id'] ?>">
                        <div class="mb-2">
                            <select name="billing_cycle" class="form-select form-select-sm">
                                <option value="monthly">miesięcznie</option>
                                <option value="yearly">rocznie (2 mc. gratis)</option>
                            </select>
                        </div>
                        <button class="btn btn-primary w-100 btn-sm">Wybierz</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
