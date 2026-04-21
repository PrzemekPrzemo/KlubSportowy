<?php
use App\Helpers\View;
use App\Models\ClubEquipmentModel;
$si = ClubEquipmentModel::$STATES[$item['state']] ?? ['label' => $item['state'], 'class' => 'secondary'];
$activeAssignment = null;
foreach ($item['history'] as $h) {
    if (empty($h['returned_at'])) { $activeAssignment = $h; break; }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-box-seam text-primary me-2"></i><?= View::e($item['name']) ?></h4>
        <small class="text-muted">
            <span class="badge bg-light text-dark"><?= View::e($item['category']) ?></span>
            <?php if ($item['sport_key']): ?><span class="badge bg-secondary ms-1"><?= View::e($item['sport_key']) ?></span><?php endif; ?>
            <span class="badge bg-<?= $si['class'] ?> ms-1"><?= View::e($si['label']) ?></span>
        </small>
    </div>
    <a href="<?= url('equipment') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Lista
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header"><i class="bi bi-info-circle me-1"></i> Szczegóły</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Marka</dt><dd class="col-sm-8"><?= View::e($item['brand'] ?? '—') ?></dd>
                    <dt class="col-sm-4">Model</dt><dd class="col-sm-8"><?= View::e($item['model'] ?? '—') ?></dd>
                    <dt class="col-sm-4">Rozmiar</dt><dd class="col-sm-8"><?= View::e($item['size'] ?? '—') ?></dd>
                    <dt class="col-sm-4">Nr seryjny</dt><dd class="col-sm-8 font-monospace"><?= View::e($item['serial_number'] ?? '—') ?></dd>
                    <dt class="col-sm-4">Data zakupu</dt><dd class="col-sm-8"><?= View::e($item['purchase_date'] ?? '—') ?></dd>
                    <dt class="col-sm-4">Cena</dt><dd class="col-sm-8"><?= $item['purchase_price'] !== null ? number_format((float)$item['purchase_price'], 2) . ' PLN' : '—' ?></dd>
                    <dt class="col-sm-4">Lokalizacja</dt><dd class="col-sm-8"><?= View::e($item['location'] ?? '—') ?></dd>
                    <?php if ($item['notes']): ?>
                        <dt class="col-sm-4">Uwagi</dt><dd class="col-sm-8"><?= View::e($item['notes']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-person-badge me-1"></i> Aktualne przypisanie</div>
            <div class="card-body">
                <?php if ($activeAssignment): ?>
                    <h5 class="mb-2">
                        <?= View::e($activeAssignment['last_name'] . ' ' . $activeAssignment['first_name']) ?>
                        <small class="text-muted">#<?= View::e($activeAssignment['member_number']) ?></small>
                    </h5>
                    <p class="mb-2"><small class="text-muted">Wydano: <?= View::e($activeAssignment['issued_at']) ?></small></p>
                    <?php if ($activeAssignment['issue_notes']): ?>
                        <p class="small mb-3"><em><?= View::e($activeAssignment['issue_notes']) ?></em></p>
                    <?php endif; ?>
                    <form method="POST" action="<?= url('equipment/' . (int)$item['id'] . '/return/' . (int)$activeAssignment['id']) ?>">
                        <?= csrf_field() ?>
                        <div class="mb-2"><textarea name="return_notes" class="form-control form-control-sm" rows="2" placeholder="Notatki zwrotu..."></textarea></div>
                        <button class="btn btn-warning btn-sm w-100"><i class="bi bi-arrow-return-left"></i> Zwróć sprzęt</button>
                    </form>
                <?php else: ?>
                    <p class="text-success mb-3"><i class="bi bi-check-circle"></i> Sprzęt dostępny</p>
                    <form method="POST" action="<?= url('equipment/' . (int)$item['id'] . '/assign') ?>">
                        <?= csrf_field() ?>
                        <div class="mb-2">
                            <label class="form-label small">Wydaj zawodnikowi</label>
                            <select name="member_id" class="form-select form-select-sm" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2"><textarea name="issue_notes" class="form-control form-control-sm" rows="2" placeholder="Notatki wydania..."></textarea></div>
                        <button class="btn btn-success btn-sm w-100"><i class="bi bi-check-lg"></i> Wydaj</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Assignment history -->
<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-clock-history me-1"></i> Historia przypisań</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>Zawodnik</th><th>Wydano</th><th>Zwrócono</th><th>Czas</th><th>Notatki</th></tr>
            </thead>
            <tbody>
            <?php if (empty($item['history'])): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">Brak historii przypisań.</td></tr>
            <?php else: foreach ($item['history'] as $h):
                $dur = null;
                if ($h['returned_at']) {
                    $dur = (strtotime($h['returned_at']) - strtotime($h['issued_at']));
                    $dur = $dur >= 86400 ? floor($dur / 86400) . ' dni' : floor($dur / 3600) . ' h';
                }
            ?>
                <tr>
                    <td><?= View::e($h['last_name'] . ' ' . $h['first_name']) ?> <small class="text-muted">#<?= View::e($h['member_number']) ?></small></td>
                    <td class="small"><?= View::e($h['issued_at']) ?></td>
                    <td class="small"><?= View::e($h['returned_at'] ?? 'W użyciu') ?></td>
                    <td class="small"><?= $dur ?? '—' ?></td>
                    <td class="small"><?= View::e(trim(($h['issue_notes'] ?? '') . ' ' . ($h['return_notes'] ?? ''))) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
