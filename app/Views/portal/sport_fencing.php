<?php
use App\Helpers\View;
use App\Sports\Fencing\Models\FencingFencerModel;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-slash-lg text-primary me-2"></i>Szermierka</h3>
        <p class="text-muted mb-0">Mój profil i wyniki zawodów</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if ($myProfile):
    $w = FencingFencerModel::$WEAPONS[$myProfile['primary_weapon']] ?? ['label' => '—', 'color' => '#aaa'];
?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm text-center" style="border-color:<?= $w['color'] ?>;border-width:2px;">
                <div class="card-body">
                    <div class="text-muted small">Broń podstawowa</div>
                    <h2 class="mb-0" style="color:<?= $w['color'] ?>;"><?= View::e($w['label']) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <div class="text-muted small">Punkty rankingowe</div>
                    <h2 class="mb-0 text-primary"><?= (int)$myProfile['ranking_points'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <div class="text-muted small">FIE ID</div>
                    <h4 class="mb-0 font-monospace"><?= View::e($myProfile['fie_id'] ?? '—') ?></h4>
                    <small class="text-muted"><?= View::e(FencingFencerModel::$LATERALITIES[$myProfile['laterality']] ?? '') ?></small>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">Brak profilu szermierza. Skontaktuj się z klubem.</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-trophy me-1"></i> Moje wyniki zawodów</div>
    <div class="card-body p-0">
        <?php if (empty($myResults)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak wyników.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Zawody</th><th>Broń</th><th>Kategoria</th><th>Runda</th><th>Miejsce</th><th>Pkt.</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myResults as $r):
                            $wi = FencingFencerModel::$WEAPONS[$r['weapon']] ?? null;
                        ?>
                            <tr>
                                <td class="small"><?= View::e($r['competition_date']) ?></td>
                                <td><strong><?= View::e($r['competition_name']) ?></strong></td>
                                <td>
                                    <?php if ($wi): ?>
                                        <span class="badge" style="background:<?= $wi['color'] ?>;color:#fff;">
                                            <?= View::e($wi['label']) ?>
                                        </span>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= View::e($r['age_category'] ?? '—') ?></small></td>
                                <td><small><?= View::e($r['round_reached'] ?? '—') ?></small></td>
                                <td>
                                    <?php if ($r['placement']): ?>
                                        <span class="badge bg-primary">#<?= (int)$r['placement'] ?></span>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td><small><?= $r['ranking_points'] ? '+' . (int)$r['ranking_points'] : '—' ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
