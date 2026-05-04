<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-shield-check me-2"></i>Audyt izolacji danych</h4>
        <small class="text-muted">Weryfikacja czy żadne dane nie wyciekają między klubami. Uruchomiono: <?= View::e($ranAt) ?></small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/audit/isolation') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-clockwise"></i> Uruchom ponownie
        </a>
        <form method="POST" action="<?= url('admin/audit/export') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-filetype-csv"></i> Eksportuj CSV</button>
        </form>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body py-2 text-center">
                <div class="h3 mb-0 text-success"><?= (int)$summary['pass'] ?></div>
                <div class="small text-muted">Zaliczone</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body py-2 text-center">
                <div class="h3 mb-0 text-danger"><?= (int)$summary['fail'] ?></div>
                <div class="small text-muted">Niezaliczone</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body py-2 text-center">
                <div class="h3 mb-0 text-warning"><?= (int)$summary['warning'] ?></div>
                <div class="small text-muted">Ostrzeżenia</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body py-2 text-center">
                <div class="h3 mb-0"><?= (int)$summary['total'] ?></div>
                <div class="small text-muted">Wszystkich sprawdzeń</div>
            </div>
        </div>
    </div>
</div>

<?php
$statusIcons = ['pass' => 'bi-check-circle-fill text-success', 'fail' => 'bi-x-circle-fill text-danger', 'warning' => 'bi-exclamation-triangle-fill text-warning'];
$statusBadges = ['pass' => 'success', 'fail' => 'danger', 'warning' => 'warning'];
?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:40px;"></th>
                    <th>Sprawdzenie</th>
                    <th style="width:100px;">Status</th>
                    <th style="width:120px;" class="text-end">Rekordów</th>
                    <th style="width:30%;">Przykłady</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $c):
                    $icon = $statusIcons[$c['status']] ?? $statusIcons['warning'];
                    $badge = $statusBadges[$c['status']] ?? 'secondary';
                ?>
                <tr>
                    <td><i class="bi <?= $icon ?>"></i></td>
                    <td>
                        <strong><?= View::e($c['name']) ?></strong>
                        <div class="small text-muted"><?= View::e($c['description'] ?? '') ?></div>
                    </td>
                    <td><span class="badge bg-<?= $badge ?>"><?= View::e($c['status']) ?></span></td>
                    <td class="text-end"><?= (int)($c['count'] ?? 0) ?></td>
                    <td>
                        <?php if (!empty($c['sample'])): ?>
                            <pre class="mb-0" style="font-size:0.75em; max-height:120px; overflow:auto;"><?= View::e(json_encode($c['sample'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
