<?php
/**
 * Portal: pelna kartoteka bokserska zawodnika.
 *
 * Wlaczany przez `app/Views/portal/sport_boxing.php` (include) jezeli
 * `$boxingCard` jest ustawione, lub renderowany bezposrednio przez
 * widok prefiksowany — patrz dispatcher w MemberPortalController.
 *
 * Spodziewane zmienne:
 *   - $boxingCard       — sport_boxing_member_record (lub null)
 *   - $licenseLevels    — BoxingRecordModel::$LICENSE_LEVELS
 *   - $weightHistory    — ostatnie pomiary (sport_boxing_weight_history)
 */
use App\Helpers\View;

if (!isset($boxingCard))    $boxingCard    = null;
if (!isset($licenseLevels)) $licenseLevels = [];
if (!isset($weightHistory)) $weightHistory = [];
?>

<?php if ($boxingCard):
    $lvl = $licenseLevels[$boxingCard['license_level']] ?? ['label' => '—', 'class' => 'secondary'];
?>
<div class="card shadow-sm mb-4 border-primary">
    <div class="card-header bg-primary text-white">
        <i class="bi bi-card-text me-1"></i> Moja kartoteka bokserska
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3 col-6">
                <div class="text-muted small">Poziom licencji</div>
                <span class="badge bg-<?= $lvl['class'] ?> fs-6"><?= View::e($lvl['label']) ?></span>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted small">Numer licencji</div>
                <div class="fw-bold font-monospace"><?= View::e($boxingCard['license_number'] ?? '—') ?></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted small">Wazna do</div>
                <div class="fw-bold"><?= View::e($boxingCard['license_expires'] ?? '—') ?></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted small">Stance</div>
                <div class="fw-bold text-capitalize"><?= View::e($boxingCard['stance'] ?? '—') ?></div>
            </div>

            <div class="col-md-3 col-6">
                <div class="text-muted small">KO / TKO</div>
                <div class="fw-bold font-monospace">
                    <?= (int)$boxingCard['ko_wins'] ?> / <?= (int)$boxingCard['tko_wins'] ?>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted small">Akt. waga</div>
                <div class="fw-bold">
                    <?= $boxingCard['current_weight_kg'] !== null
                        ? number_format((float)$boxingCard['current_weight_kg'], 2, ',', ' ') . ' kg'
                        : '—' ?>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted small">Kat. wagowa</div>
                <div class="fw-bold"><?= View::e($boxingCard['current_weight_class'] ?? '—') ?></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-muted small">Reach (cm)</div>
                <div class="fw-bold"><?= View::e($boxingCard['reach_cm'] ?? '—') ?></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($weightHistory)): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header"><i class="bi bi-graph-up me-1"></i> Moje ostatnie pomiary wagi</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Data</th><th>Waga</th><th>Kat.</th><th>Notatki</th></tr></thead>
            <tbody>
            <?php foreach ($weightHistory as $h): ?>
                <tr>
                    <td class="small"><?= View::e($h['measured_at']) ?></td>
                    <td class="font-monospace"><?= View::e(number_format((float)$h['weight_kg'], 2, ',', ' ')) ?> kg</td>
                    <td class="small"><?= View::e($h['weight_class'] ?? '—') ?></td>
                    <td class="small text-muted"><?= View::e($h['notes'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
