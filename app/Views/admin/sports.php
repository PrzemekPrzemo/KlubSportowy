<?php use App\Helpers\View; ?>
<p class="text-muted">Katalog wszystkich sportów obsługiwanych przez platformę. Kluby aktywują sekcje z tego katalogu w sekcji <code>Sekcje sportowe</code>.</p>

<div class="row g-3">
    <?php foreach ($sports as $s): ?>
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <h5 style="color: <?= View::e($s['color']) ?>">
                    <i class="bi <?= View::e($s['icon']) ?>"></i>
                    <?= View::e($s['name']) ?>
                </h5>
                <div class="text-muted small">key: <code><?= View::e($s['key']) ?></code></div>
                <?php if (!empty($s['federation_code'])): ?>
                    <div class="small"><strong>Federacja:</strong> <?= View::e($s['federation_code']) ?> – <?= View::e($s['federation_name']) ?></div>
                <?php endif; ?>
                <div class="small">
                    <strong>Typ:</strong> <?= $s['team_sport'] ? 'drużynowy' : 'indywidualny' ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
