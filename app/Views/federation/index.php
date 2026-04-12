<?php use App\Helpers\View; ?>
<p class="text-muted">Integracje z polskimi związkami sportowymi. Poziom wsparcia zależy od dostępności API/portalu federacji.</p>

<div class="mb-3 text-end">
    <a href="<?= url('federation/configure') ?>" class="btn btn-outline-primary">
        <i class="bi bi-gear"></i> Konfiguracja credentiali
    </a>
</div>

<div class="row g-3">
    <?php foreach ($integrations as $code => $info):
        $level_badge = match($info['level']) {
            'scraping' => '<span class="badge bg-warning">scraping</span>',
            'api_ready' => '<span class="badge bg-success">API ready</span>',
            default => '<span class="badge bg-secondary">manual</span>',
        };
        $conf = $configured[$code] ?? [];
        $is_configured = !empty($conf['has_login']) || !empty($conf['has_api_key']);
    ?>
        <div class="col-md-4">
            <div class="card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="mb-1"><?= View::e($code) ?></h5>
                    <?= $level_badge ?>
                </div>
                <div class="small text-muted"><?= View::e($info['name']) ?></div>
                <div class="mt-2 small">
                    <strong>Funkcje:</strong>
                    <?php foreach ($info['features'] as $f): ?>
                        <span class="badge bg-light text-dark"><?= View::e($f) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="mt-2">
                    <?php if ($is_configured): ?>
                        <span class="badge bg-success"><i class="bi bi-check"></i> Skonfigurowany</span>
                    <?php else: ?>
                        <span class="badge bg-secondary"><i class="bi bi-x"></i> Brak konfiguracji</span>
                    <?php endif; ?>
                </div>
                <?php
                $portal = \App\Helpers\FederationClient::getFederationPortalUrl($code);
                if ($portal): ?>
                    <div class="mt-auto pt-2">
                        <a href="<?= View::e($portal) ?>" target="_blank" class="btn btn-sm btn-outline-info w-100">
                            <i class="bi bi-box-arrow-up-right"></i> Portal federacji
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
