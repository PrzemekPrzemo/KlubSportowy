<?php
use App\Helpers\View;
use App\Models\BodyMetricsModel;

$latestBmi = $latest ? BodyMetricsModel::calcBmi(
    $latest['weight_kg'] !== null ? (float)$latest['weight_kg'] : null,
    $latest['height_cm'] !== null ? (int)$latest['height_cm'] : null
) : null;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-activity text-primary me-2"></i>Moje pomiary ciała</h3>
        <p class="text-muted mb-0">Historia wagi, wzrostu, BMI i innych parametrów</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<div class="alert alert-info small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    Pomiary mogą dodawać trener, lekarz klubowy lub Ty samodzielnie. Twoja aktualna waga jest automatycznie
    używana do obliczania Sinclair (podnoszenie ciężarów) i W/kg (kolarstwo).
</div>

<!-- Self-entry form -->
<details class="card shadow-sm mb-4" <?= !empty($errors ?? []) ? 'open' : '' ?>>
    <summary class="card-header" style="cursor:pointer; list-style:revert;">
        <i class="bi bi-plus-circle me-1"></i> Dodaj pomiar
    </summary>
    <div class="card-body">
        <?php if (!empty($errors ?? [])): ?>
            <div class="alert alert-danger py-2 mb-3 small">
                <?php foreach ($errors as $err): ?>
                    <div><i class="bi bi-exclamation-triangle me-1"></i><?= View::e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php $oldInput = $oldInput ?? []; ?>
        <form method="POST" action="<?= url('portal/body-metrics') ?>" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-md-3">
                <label class="form-label small">Data pomiaru</label>
                <input type="date" name="measured_at" class="form-control form-control-sm"
                       value="<?= View::e($oldInput['measured_at'] ?? date('Y-m-d')) ?>"
                       max="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label small">Waga (kg)</label>
                <input type="number" step="0.1" min="20" max="250" name="weight_kg"
                       class="form-control form-control-sm <?= isset($errors['weight_kg']) ? 'is-invalid' : '' ?>"
                       value="<?= View::e($oldInput['weight_kg'] ?? '') ?>" placeholder="np. 75.5">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label small">Wzrost (cm)</label>
                <input type="number" min="100" max="250" name="height_cm"
                       class="form-control form-control-sm <?= isset($errors['height_cm']) ? 'is-invalid' : '' ?>"
                       value="<?= View::e($oldInput['height_cm'] ?? '') ?>" placeholder="np. 178">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label small">% tkanki tluszczowej</label>
                <input type="number" step="0.1" min="0" max="70" name="body_fat_pct"
                       class="form-control form-control-sm <?= isset($errors['body_fat_pct']) ? 'is-invalid' : '' ?>"
                       value="<?= View::e($oldInput['body_fat_pct'] ?? '') ?>" placeholder="np. 18.5">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label small">Tetno spoczynkowe (bpm)</label>
                <input type="number" min="30" max="200" name="resting_hr"
                       class="form-control form-control-sm <?= isset($errors['resting_hr']) ? 'is-invalid' : '' ?>"
                       value="<?= View::e($oldInput['resting_hr'] ?? '') ?>" placeholder="np. 60">
            </div>
            <div class="col-md-3 col-6">
                <label class="form-label small">Rozpietosc ramion (cm)</label>
                <input type="number" min="100" max="260" name="wingspan_cm"
                       class="form-control form-control-sm <?= isset($errors['wingspan_cm']) ? 'is-invalid' : '' ?>"
                       value="<?= View::e($oldInput['wingspan_cm'] ?? '') ?>" placeholder="np. 182">
            </div>
            <div class="col-12">
                <label class="form-label small">Notatki (opcjonalnie)</label>
                <input type="text" name="notes" class="form-control form-control-sm"
                       maxlength="255" value="<?= View::e($oldInput['notes'] ?? '') ?>"
                       placeholder="kontekst pomiaru, np. 'po treningu'">
            </div>
            <div class="col-12 text-end">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-check2"></i> Zapisz pomiar
                </button>
            </div>
        </form>
    </div>
</details>

<!-- Current -->
<?php if ($latest): ?>
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small">Aktualna waga</div>
                <h2 class="mb-0"><?= $latest['weight_kg'] !== null ? number_format((float)$latest['weight_kg'], 1) . ' kg' : '—' ?></h2>
                <small class="text-muted">pomiar: <?= View::e($latest['measured_at']) ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small">Wzrost</div>
                <h2 class="mb-0"><?= $latest['height_cm'] ? (int)$latest['height_cm'] . ' cm' : '—' ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small">BMI</div>
                <h2 class="mb-0"><?= $latestBmi !== null ? $latestBmi : '—' ?></h2>
                <?php if ($latestBmi !== null): ?>
                    <span class="badge bg-<?= $latestBmi >= 18.5 && $latestBmi < 25 ? 'success' : 'warning' ?>">
                        <?= View::e(BodyMetricsModel::bmiCategory($latestBmi)) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small">% Tłuszczu</div>
                <h2 class="mb-0"><?= $latest['body_fat_pct'] !== null ? number_format((float)$latest['body_fat_pct'], 1) . '%' : '—' ?></h2>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="alert alert-warning">Brak pomiarów w systemie. Poproś trenera o wykonanie pomiaru.</div>
<?php endif; ?>

<!-- Weight trend (simple text-based chart) -->
<?php if (!empty($history)): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header"><i class="bi bi-graph-up me-1"></i> Trend wagi (ostatnie 12 miesięcy)</div>
    <div class="card-body">
        <?php
        $weights = array_column($history, 'weight_kg');
        $min = min($weights); $max = max($weights);
        $range = max(0.1, $max - $min);
        ?>
        <div class="d-flex justify-content-between small text-muted mb-1">
            <span>Min: <?= number_format($min, 1) ?> kg</span>
            <span>Max: <?= number_format($max, 1) ?> kg</span>
        </div>
        <div class="d-flex gap-1 align-items-end" style="height:80px;">
            <?php foreach ($history as $h):
                $val   = (float)$h['weight_kg'];
                $ratio = ($val - $min) / $range;
                $height = 10 + $ratio * 70;
            ?>
                <div class="bg-primary rounded-top" style="flex:1;height:<?= $height ?>px;"
                     title="<?= View::e($h['measured_at']) ?>: <?= number_format($val, 1) ?> kg"></div>
            <?php endforeach; ?>
        </div>
        <div class="d-flex justify-content-between small text-muted mt-1">
            <span><?= View::e($history[0]['measured_at']) ?></span>
            <span><?= View::e(end($history)['measured_at']) ?></span>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Full history -->
<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-clock-history me-1"></i> Historia pomiarów</div>
    <div class="card-body p-0">
        <?php if (empty($metrics)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak pomiarów.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Waga</th><th>Wzrost</th><th>BMI</th><th>% Tł.</th><th>HR</th><th>Rozp.</th><th>Uwagi</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($metrics as $m):
                        $bmi = BodyMetricsModel::calcBmi(
                            $m['weight_kg'] !== null ? (float)$m['weight_kg'] : null,
                            $m['height_cm'] !== null ? (int)$m['height_cm'] : null
                        );
                    ?>
                        <tr>
                            <td class="small"><?= View::e($m['measured_at']) ?></td>
                            <td class="font-monospace"><?= $m['weight_kg'] !== null ? number_format((float)$m['weight_kg'], 1) . ' kg' : '—' ?></td>
                            <td class="font-monospace"><?= $m['height_cm'] ? (int)$m['height_cm'] . ' cm' : '—' ?></td>
                            <td class="font-monospace"><?= $bmi !== null ? $bmi : '—' ?></td>
                            <td class="font-monospace"><?= $m['body_fat_pct'] !== null ? number_format((float)$m['body_fat_pct'], 1) . '%' : '—' ?></td>
                            <td class="font-monospace"><?= $m['resting_hr'] ? (int)$m['resting_hr'] : '—' ?></td>
                            <td class="font-monospace"><?= $m['wingspan_cm'] ? (int)$m['wingspan_cm'] . ' cm' : '—' ?></td>
                            <td class="small"><?= View::e($m['notes'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
