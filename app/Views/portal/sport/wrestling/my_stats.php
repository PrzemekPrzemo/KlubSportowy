<?php
/**
 * Portal: techniczne statystyki zapasnika.
 *
 * Zmienne:
 *   - $profile — sport_wrestling_member (lub null)
 *   - $stats   — agregaty z sport_wrestling_match_breakdown
 *   - $styles  — etykiety stylow
 */
use App\Helpers\View;
if (!isset($profile)) $profile = null;
if (!isset($stats))   $stats   = [];
if (!isset($styles))  $styles  = [];

$styleList = [];
if ($profile && !empty($profile['styles_list'] ?? [])) {
    foreach ($profile['styles_list'] as $s) {
        $styleList[] = $styles[$s] ?? $s;
    }
} elseif ($profile && !empty($profile['styles'])) {
    foreach (explode(',', $profile['styles']) as $s) {
        $s = trim($s);
        if ($s !== '') $styleList[] = $styles[$s] ?? $s;
    }
}
?>
<?php if ($profile): ?>
<div class="card shadow-sm mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-people-fill me-1"></i> Moj profil zapasnika
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small">Style</div>
                <?php if ($styleList): ?>
                    <?php foreach ($styleList as $s): ?>
                        <span class="badge bg-secondary me-1"><?= View::e($s) ?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted small">Akt. waga</div>
                <div class="fw-bold"><?= $profile['current_weight_kg'] !== null
                    ? number_format((float)$profile['current_weight_kg'], 2, ',', ' ') . ' kg'
                    : '—' ?></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted small">Punkty rankingu</div>
                <div class="fw-bold font-monospace"><?= (int)$profile['rank_points'] ?></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($stats)): ?>
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body">
        <div class="text-muted small">Takedowns</div>
        <h3 class="mb-0"><?= (int)($stats['takedowns'] ?? 0) ?></h3>
    </div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body">
        <div class="text-muted small">Exposures</div>
        <h3 class="mb-0"><?= (int)($stats['exposures'] ?? 0) ?></h3>
    </div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center"><div class="card-body">
        <div class="text-muted small">Escapes</div>
        <h3 class="mb-0"><?= (int)($stats['escapes'] ?? 0) ?></h3>
    </div></div></div>
    <div class="col-md-3 col-6"><div class="card shadow-sm text-center bg-dark text-white"><div class="card-body">
        <div class="small opacity-75">Tech. fall / Pin</div>
        <h3 class="mb-0 font-monospace"><?= (int)($stats['technical_falls'] ?? 0) ?> / <?= (int)($stats['pins'] ?? 0) ?></h3>
    </div></div></div>
</div>
<?php endif; ?>
