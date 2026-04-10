<?php use App\Helpers\View; ?>
<div class="row g-3">
    <?php foreach ($plans as $p): ?>
        <div class="col-md-3">
            <div class="card p-3 h-100">
                <h5><?= View::e($p['name']) ?></h5>
                <div class="text-muted small mb-2"><?= View::e($p['code']) ?></div>
                <div class="display-6"><?= format_money($p['price_monthly']) ?></div>
                <div class="small text-muted">/miesiąc</div>
                <hr>
                <ul class="small list-unstyled mb-0">
                    <li>Zawodnicy: <?= $p['max_members'] !== null ? (int)$p['max_members'] : '∞' ?></li>
                    <li>Sekcje sport.: <?= $p['max_sports'] !== null ? (int)$p['max_sports'] : '∞' ?></li>
                    <li>Rocznie: <?= format_money($p['price_yearly']) ?></li>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>
</div>
