<?php
use App\Helpers\View;
use App\Sports\Boxing\Models\BoxingResultModel;
use App\Sports\Boxing\Models\BoxingMedicalModel;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-trophy text-warning me-2"></i>Boks</h3>
        <p class="text-muted mb-0">Mój rekord walk i status badań</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<!-- Record cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small">Zwycięstwa</div>
                <h2 class="mb-0 text-success"><?= (int)($record['win'] ?? 0) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small">Porażki</div>
                <h2 class="mb-0 text-danger"><?= (int)($record['loss'] ?? 0) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small">Remisy</div>
                <h2 class="mb-0"><?= (int)($record['draw'] ?? 0) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center bg-dark text-white">
            <div class="card-body">
                <div class="small opacity-75">Rekord</div>
                <h2 class="mb-0 font-monospace">
                    <?= (int)($record['win'] ?? 0) ?>-<?= (int)($record['loss'] ?? 0) ?>-<?= (int)($record['draw'] ?? 0) ?>
                </h2>
            </div>
        </div>
    </div>
</div>

<!-- Medical status -->
<div class="card shadow-sm mb-4">
    <div class="card-header"><i class="bi bi-heart-pulse me-1"></i> Status badań lekarskich</div>
    <div class="card-body">
        <?php if (!$medical): ?>
            <div class="alert alert-danger mb-0">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Brak badania lekarskiego!</strong> Zgłoś się do lekarza sportowego — bez aktualnego badania nie możesz startować.
            </div>
        <?php else:
            $days = (int)($medical['days_remaining'] ?? 0);
        ?>
            <?php if ($days < 0): ?>
                <div class="alert alert-danger mb-0">
                    <i class="bi bi-x-circle me-1"></i>
                    <strong>Badanie wygasło <?= abs($days) ?> dni temu.</strong> (ważne do: <?= View::e($medical['valid_until']) ?>)
                </div>
            <?php elseif ($days <= 30): ?>
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-clock me-1"></i>
                    <strong>Uwaga:</strong> badanie wygasa za <?= $days ?> dni (<?= View::e($medical['valid_until']) ?>). Zaplanuj wizytę u lekarza.
                </div>
            <?php else: ?>
                <div class="alert alert-success mb-0">
                    <i class="bi bi-check-circle me-1"></i>
                    <strong>Badanie aktywne</strong> — ważne jeszcze <?= $days ?> dni (do: <?= View::e($medical['valid_until']) ?>).
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Recent fights -->
<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-clock-history me-1"></i> Ostatnie walki</div>
    <div class="card-body p-0">
        <?php if (empty($recent)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak walk.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Przeciwnik</th><th>Wynik</th><th>Sposób</th><th>Waga</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $r):
                            $resInfo = BoxingResultModel::$RESULTS[$r['result']] ?? ['label' => '—', 'class' => 'secondary'];
                        ?>
                            <tr>
                                <td class="text-muted small"><?= View::e($r['competition_date']) ?></td>
                                <td><?= View::e($r['opponent_name'] ?? '—') ?></td>
                                <td><span class="badge bg-<?= $resInfo['class'] ?>"><?= View::e($resInfo['label']) ?></span></td>
                                <td class="small"><?= View::e(BoxingResultModel::$METHODS[$r['method']] ?? '—') ?></td>
                                <td class="small text-muted"><?= View::e($r['weight_class'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
