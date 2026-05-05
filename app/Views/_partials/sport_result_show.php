<?php
/**
 * Generic show (read-only) view dla `<key>_results` table.
 *
 * Wymagane zmienne:
 *   $fields    — z SportResultIntrospector::fields($table)
 *   $row       — wiersz z DB
 *   $member    — array{first_name, last_name, member_number} lub null
 *   $editUrl   — link do strony edit
 *   $listUrl   — link "powrot do listy"
 *   $title     — tytul nagłówka
 *   $extraLabels — opcjonalna mapa: column_name => ['label'=>..., 'options'=>['key'=>'label']]
 *                  pozwala translate ENUM key na human-readable label w show view
 */
use App\Helpers\View;

$extraLabels = $extraLabels ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-info-circle text-primary me-2"></i>
        <?= View::e($title ?? 'Szczegóły') ?>
    </h3>
    <div class="d-flex gap-2">
        <a href="<?= View::e($listUrl) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Lista
        </a>
        <a href="<?= View::e($editUrl) ?>" class="btn btn-primary btn-sm">
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
                    <strong><?= View::e(($member['last_name'] ?? '') . ' ' . ($member['first_name'] ?? '')) ?></strong>
                    <?php if (!empty($member['member_number'])): ?>
                        <small class="text-muted">(<?= View::e($member['member_number']) ?>)</small>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted">— brak —</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php foreach ($fields as $f):
            $name = $f['name'];
            if ($name === 'member_id') continue;
            $val = $row[$name] ?? null;
        ?>
            <tr>
                <th class="text-muted"><?= View::e($f['label']) ?></th>
                <td>
                    <?php if ($val === null || $val === ''): ?>
                        <span class="text-muted">—</span>
                    <?php elseif (isset($extraLabels[$name]['options'][$val])): ?>
                        <?= View::e($extraLabels[$name]['options'][$val]) ?>
                    <?php elseif ($f['input_type'] === 'enum' && isset($f['options'][$val])): ?>
                        <span class="badge bg-info"><?= View::e($f['options'][$val]) ?></span>
                    <?php elseif ($f['input_type'] === 'checkbox'): ?>
                        <?= !empty($val) ? '<i class="bi bi-check2 text-success"></i> Tak' : '<span class="text-muted">Nie</span>' ?>
                    <?php elseif ($f['input_type'] === 'textarea'): ?>
                        <?= nl2br(View::e((string)$val)) ?>
                    <?php else: ?>
                        <?= View::e((string)$val) ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
