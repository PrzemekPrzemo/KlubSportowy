<?php
use App\Helpers\View;
use App\Sports\Taekwondo\Models\TaekwondoBeltModel;
use App\Sports\Taekwondo\Models\TaekwondoResultModel;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-shield-fill text-primary me-2"></i>Taekwondo</h3>
        <p class="text-muted mb-0">Mój pas i historia osiągnięć</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<!-- Current belt -->
<?php if ($currentBelt):
    $bi = TaekwondoBeltModel::$BELTS[$currentBelt['belt']] ?? ['label' => $currentBelt['belt'], 'color' => '#aaa'];
    $isDan = str_contains((string)$currentBelt['belt'], 'dan');
?>
    <div class="card shadow-sm mb-4" style="border-color:<?= $bi['color'] ?>;border-width:3px;">
        <div class="card-body text-center">
            <div class="text-muted small mb-2">Aktualny stopień</div>
            <div class="mx-auto mb-3" style="width:120px;height:25px;background:<?= $bi['color'] ?>;border:2px solid #333;border-radius:4px;"></div>
            <h2 class="mb-1 <?= $isDan ? 'text-dark' : '' ?>"><?= View::e($bi['label']) ?></h2>
            <div class="text-muted small">
                Data egzaminu: <?= View::e($currentBelt['granted_date']) ?>
                <?php if ($currentBelt['examiner']): ?>
                    <br>Egzaminator: <?= View::e($currentBelt['examiner']) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">Brak przyznanego pasa. Rozpocznij egzamin na 10 gup (biały).</div>
<?php endif; ?>

<!-- Belt history -->
<div class="card shadow-sm mb-4">
    <div class="card-header"><i class="bi bi-clock-history me-1"></i> Historia awansów</div>
    <div class="card-body p-0">
        <?php if (empty($beltHistory)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak awansów.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Data</th><th>Stopień</th><th>Egzaminator</th></tr></thead>
                    <tbody>
                        <?php foreach ($beltHistory as $b):
                            $bi = TaekwondoBeltModel::$BELTS[$b['belt']] ?? ['label' => $b['belt'], 'color' => '#aaa'];
                        ?>
                            <tr>
                                <td class="small"><?= View::e($b['granted_date']) ?></td>
                                <td>
                                    <span style="display:inline-block;width:30px;height:10px;background:<?= $bi['color'] ?>;border:1px solid #333;vertical-align:middle;"></span>
                                    <?= View::e($bi['label']) ?>
                                </td>
                                <td class="small text-muted"><?= View::e($b['examiner'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Results -->
<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-trophy me-1"></i> Wyniki zawodów</div>
    <div class="card-body p-0">
        <?php if (empty($myResults)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak wyników.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Zawody</th><th>Kategoria</th><th>Waga</th><th>Miejsce</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myResults as $r): ?>
                            <tr>
                                <td class="small"><?= View::e($r['competition_date']) ?></td>
                                <td><strong><?= View::e($r['competition_name']) ?></strong></td>
                                <td><small><?= View::e(TaekwondoResultModel::$CATEGORIES[$r['category']] ?? $r['category'] ?? '—') ?></small></td>
                                <td><small class="text-muted"><?= View::e($r['weight_class'] ?? '—') ?></small></td>
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
