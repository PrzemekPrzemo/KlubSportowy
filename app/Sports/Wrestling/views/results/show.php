<?php
use App\Helpers\View;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0">
            <i class="bi bi-people-fill text-primary me-2"></i>
            Wynik — <?= View::e($result['competition_name']) ?>
        </h3>
        <p class="text-muted small mb-0">
            <?= View::e($result['competition_date']) ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('wrestling/results') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Lista
        </a>
        <a href="<?= url('wrestling/results/' . (int)$result['id'] . '/edit') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil"></i> Edytuj
        </a>
    </div>
</div>

<div class="card p-3">
    <table class="table table-sm mb-0">
        <tr>
            <th class="text-muted" style="width:30%">Zawodnik</th>
            <td>
                <?php if (!empty($member)): ?>
                    <?= View::e($member['first_name'] . ' ' . $member['last_name']) ?>
                    <small class="text-muted">(<?= View::e($member['member_number'] ?? '?') ?>)</small>
                <?php else: ?>
                    <span class="text-muted">— brak —</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr><th class="text-muted">Zawody</th><td><?= View::e($result['competition_name']) ?></td></tr>
        <tr><th class="text-muted">Data</th><td><?= View::e($result['competition_date']) ?></td></tr>
        <tr>
            <th class="text-muted">Styl</th>
            <td><span class="badge bg-info"><?= View::e($styles[$result['style']] ?? $result['style']) ?></span></td>
        </tr>
        <tr>
            <th class="text-muted">Kategoria wagowa</th>
            <td><?= !empty($result['weight_class']) ? View::e($result['weight_class']) . ' kg' : '<span class="text-muted">open</span>' ?></td>
        </tr>
        <tr>
            <th class="text-muted">Kategoria wiekowa</th>
            <td><?= !empty($result['age_category']) ? View::e($result['age_category']) : '<span class="text-muted">—</span>' ?></td>
        </tr>
        <tr>
            <th class="text-muted">Miejsce</th>
            <td>
                <?php if (!empty($result['placement'])): ?>
                    <span class="badge bg-success"><?= (int)$result['placement'] ?></span>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php if (!empty($result['notes'])): ?>
        <tr>
            <th class="text-muted">Notatki</th>
            <td><?= nl2br(View::e($result['notes'])) ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>
