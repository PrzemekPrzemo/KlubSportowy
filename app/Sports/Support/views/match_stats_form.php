<?php
use App\Helpers\View;

/**
 * Generic reusable formularz statystyk meczu druzynowego.
 * Wymagane zmienne w scope renderu:
 *   - $title       (str)
 *   - $match       (array, z home_score/away_score/match_date/...)
 *   - $stats       (array ['home' => row|null, 'away' => row|null])
 *   - $statsColumns(array<string>)
 *   - $submitUrl   (str) — url do POST
 *   - $backUrl     (str)
 *   - $sportKey    (str)
 */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-bar-chart-line text-primary me-2"></i><?= View::e($title) ?></h4>
    <a href="<?= url($backUrl) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Wróć</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <p class="mb-1"><strong>Data:</strong> <?= date('Y-m-d H:i', strtotime($match['match_date'])) ?></p>
        <p class="mb-1"><strong>Wynik:</strong> <span class="font-monospace fw-bold"><?= (int)($match['home_score'] ?? 0) ?> : <?= (int)($match['away_score'] ?? 0) ?></span></p>
        <?php if (!empty($match['location'])): ?>
            <p class="mb-0 small text-muted">Miejsce: <?= View::e($match['location']) ?></p>
        <?php endif; ?>
    </div>
</div>

<form method="POST" action="<?= url($submitUrl) ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <?php foreach (['home' => 'Gospodarze', 'away' => 'Goście'] as $side => $label): ?>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header"><strong><?= View::e($label) ?></strong></div>
                    <div class="card-body">
                        <?php foreach ($statsColumns as $col):
                            $val = (int)($stats[$side][$col] ?? 0);
                        ?>
                            <div class="row mb-2 align-items-center">
                                <label class="col-7 col-form-label"><?= View::e(ucfirst(str_replace('_', ' ', $col))) ?></label>
                                <div class="col-5">
                                    <input type="number" name="<?= $side ?>[<?= View::e($col) ?>]" value="<?= $val ?>" min="0" max="999" class="form-control form-control-sm">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="d-flex justify-content-end mt-3 gap-2">
        <a href="<?= url($backUrl) ?>" class="btn btn-secondary">Anuluj</a>
        <button class="btn btn-success"><i class="bi bi-save"></i> Zapisz statystyki</button>
    </div>
</form>
