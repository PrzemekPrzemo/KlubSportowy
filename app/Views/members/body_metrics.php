<?php
use App\Helpers\View;
use App\Models\BodyMetricsModel;

$latestBmi = $latest ? BodyMetricsModel::calcBmi(
    $latest['weight_kg'] !== null ? (float)$latest['weight_kg'] : null,
    $latest['height_cm'] !== null ? (int)$latest['height_cm'] : null
) : null;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-activity text-primary me-2"></i>Pomiary ciała</h4>
        <small class="text-muted">
            <?= View::e($member['last_name'] . ' ' . $member['first_name']) ?>
            <span class="badge bg-light text-dark ms-1">#<?= View::e($member['member_number']) ?></span>
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('members/' . (int)$member['id']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Profil
        </a>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#metricModal">
            <i class="bi bi-plus-circle"></i> Nowy pomiar
        </button>
    </div>
</div>

<!-- Latest summary -->
<?php if ($latest): ?>
<div class="row g-3 mb-4">
    <div class="col-md-2 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body p-2">
                <div class="text-muted small">Waga</div>
                <h3 class="mb-0"><?= $latest['weight_kg'] !== null ? number_format((float)$latest['weight_kg'], 1) . ' kg' : '—' ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body p-2">
                <div class="text-muted small">Wzrost</div>
                <h3 class="mb-0"><?= $latest['height_cm'] ? (int)$latest['height_cm'] . ' cm' : '—' ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body p-2">
                <div class="text-muted small">BMI</div>
                <h3 class="mb-0"><?= $latestBmi !== null ? $latestBmi : '—' ?></h3>
                <?php if ($latestBmi !== null): ?>
                    <small class="text-muted"><?= View::e(BodyMetricsModel::bmiCategory($latestBmi)) ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body p-2">
                <div class="text-muted small">% Tłuszczu</div>
                <h3 class="mb-0"><?= $latest['body_fat_pct'] !== null ? number_format((float)$latest['body_fat_pct'], 1) . '%' : '—' ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body p-2">
                <div class="text-muted small">Tętno spocz.</div>
                <h3 class="mb-0"><?= $latest['resting_hr'] ? (int)$latest['resting_hr'] : '—' ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card shadow-sm text-center">
            <div class="card-body p-2">
                <div class="text-muted small">Rozpiętość</div>
                <h3 class="mb-0"><?= $latest['wingspan_cm'] ? (int)$latest['wingspan_cm'] . ' cm' : '—' ?></h3>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- History -->
<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-clock-history me-1"></i> Historia pomiarów</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th><th>Waga</th><th>Wzrost</th><th>BMI</th>
                    <th>% Tł.</th><th>HR</th><th>Rozp.</th><th>Zmierzone przez</th><th>Uwagi</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($metrics)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Brak pomiarów.</td></tr>
            <?php else: foreach ($metrics as $m):
                $bmi = BodyMetricsModel::calcBmi(
                    $m['weight_kg'] !== null ? (float)$m['weight_kg'] : null,
                    $m['height_cm'] !== null ? (int)$m['height_cm'] : null
                );
            ?>
                <tr>
                    <td class="small"><?= View::e($m['measured_at']) ?></td>
                    <td class="font-monospace"><?= $m['weight_kg'] !== null ? number_format((float)$m['weight_kg'], 1) : '—' ?></td>
                    <td class="font-monospace"><?= $m['height_cm'] ? (int)$m['height_cm'] : '—' ?></td>
                    <td class="font-monospace"><?= $bmi !== null ? $bmi : '—' ?></td>
                    <td class="font-monospace"><?= $m['body_fat_pct'] !== null ? number_format((float)$m['body_fat_pct'], 1) : '—' ?></td>
                    <td class="font-monospace"><?= $m['resting_hr'] ? (int)$m['resting_hr'] : '—' ?></td>
                    <td class="font-monospace"><?= $m['wingspan_cm'] ? (int)$m['wingspan_cm'] : '—' ?></td>
                    <td class="small text-muted"><?= View::e($m['measured_by'] ?? '—') ?></td>
                    <td class="small"><?= View::e($m['notes'] ?? '') ?></td>
                    <td>
                        <form method="POST" action="<?= url('members/' . (int)$member['id'] . '/metrics/' . (int)$m['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć pomiar?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="metricModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('members/' . (int)$member['id'] . '/metrics/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Nowy pomiar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Data pomiaru</label>
                            <input type="date" name="measured_at" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Zmierzone przez</label>
                            <input type="text" name="measured_by" class="form-control" placeholder="trener / lekarz / self">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Waga (kg)</label>
                            <input type="number" step="0.1" name="weight_kg" class="form-control" min="10" max="250">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Wzrost (cm)</label>
                            <input type="number" name="height_cm" class="form-control" min="50" max="250">
                        </div>
                        <div class="col-4">
                            <label class="form-label">% Tłuszczu</label>
                            <input type="number" step="0.1" name="body_fat_pct" class="form-control" min="3" max="60">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Tętno spoczynkowe (bpm)</label>
                            <input type="number" name="resting_hr" class="form-control" min="30" max="200">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Rozpiętość ramion (cm)</label>
                            <input type="number" name="wingspan_cm" class="form-control" min="50" max="250">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Uwagi</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">
                        <i class="bi bi-info-circle"></i>
                        Waga jest automatycznie używana do obliczania Sinclair (podnoszenie ciężarów) i W/kg (kolarstwo).
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
