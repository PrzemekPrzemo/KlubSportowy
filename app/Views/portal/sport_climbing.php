<?php
use App\Helpers\View;
use App\Sports\Climbing\Models\ClimbingResultModel;
use App\Sports\Climbing\Models\ClimbingRouteModel;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-triangle-fill text-primary me-2"></i>Wspinaczka sportowa</h3>
        <p class="text-muted mb-0">Moje wyniki i aktualne drogi klubowe</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<!-- Results -->
<div class="card shadow-sm mb-4">
    <div class="card-header"><i class="bi bi-trophy me-1"></i> Moje wyniki zawodów</div>
    <div class="card-body p-0">
        <?php if (empty($myResults)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak wyników.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Zawody</th><th>Dyscyplina</th><th>Miejsce</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myResults as $r): ?>
                            <tr>
                                <td class="small"><?= View::e($r['competition_date']) ?></td>
                                <td><?= View::e($r['competition_name']) ?></td>
                                <td><small><?= View::e(ClimbingResultModel::$CATEGORIES[$r['category']] ?? $r['category'] ?? '—') ?></small></td>
                                <td>
                                    <?php if ($r['placement']): ?>
                                        <span class="badge bg-primary">#<?= (int)$r['placement'] ?></span>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Active routes in the club -->
<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-list-columns me-1"></i> Aktualne drogi w klubie</div>
    <div class="card-body p-0">
        <?php if (empty($activeRoutes)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak aktywnych dróg.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Nazwa</th><th>Typ</th><th>FR</th><th>V</th><th>Ściana</th><th>Kolor</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeRoutes as $r):
                            $ti = ClimbingRouteModel::$TYPES[$r['type']] ?? ['label' => $r['type'], 'class' => 'secondary'];
                        ?>
                            <tr>
                                <td><strong><?= View::e($r['name']) ?></strong></td>
                                <td><span class="badge bg-<?= $ti['class'] ?>"><?= View::e($ti['label']) ?></span></td>
                                <td class="font-monospace"><?= View::e($r['grade_french'] ?? '—') ?></td>
                                <td class="font-monospace"><?= View::e($r['grade_v'] ?? '—') ?></td>
                                <td class="small"><?= View::e($r['wall_name'] ?? '—') ?></td>
                                <td>
                                    <?php if ($r['color']): ?>
                                        <span class="d-inline-block" style="width:16px;height:16px;background:<?= View::e($r['color']) ?>;border:1px solid #333;vertical-align:middle;"></span>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
