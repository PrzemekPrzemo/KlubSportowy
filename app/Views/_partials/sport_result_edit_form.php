<?php
/**
 * Generic edit form dla `<key>_results` table — uzywany przez stub-sporty
 * (AlpineSki, Badminton, Biathlon, FigureSkating, Rowing, SkiJump, Snowboard,
 * TableTennis, XcSki) ktore nie maja sport-specific UX.
 *
 * Wymagane zmienne:
 *   $fields    — z SportResultIntrospector::fields($table)
 *   $row       — wiersz z DB (member_id, competition_name, ...)
 *   $members   — lista czlonkow klubu do dropdown'a
 *   $formAction — URL do POST
 *   $cancelUrl  — URL "Anuluj" (zwykle .../results)
 *   $title     — tytul nagłówka
 *   $extraSelects — opcjonalna mapa: column_name => ['label'=>..., 'options'=>['key'=>'label']]
 *                   pozwala nadpisac generic input dla kolumn ze sport-specific
 *                   ENUM values (np. swimming.stroke -> $STROKES)
 */
use App\Helpers\View;

$extraSelects = $extraSelects ?? [];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-pencil-square text-primary me-2"></i>
        <?= View::e($title ?? 'Edytuj wpis') ?>
    </h3>
    <a href="<?= View::e($cancelUrl) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Anuluj
    </a>
</div>

<div class="card p-4">
    <form method="POST" action="<?= View::e($formAction) ?>">
        <?= csrf_field() ?>
        <div class="row g-3">

            <!-- member_id always first — drop-down z aktywnymi czlonkami -->
            <div class="col-md-6">
                <label class="form-label">Zawodnik *</label>
                <select name="member_id" class="form-select" required>
                    <option value="">— wybierz —</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int)$m['id'] ?>" <?= (int)($row['member_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>>
                            <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php foreach ($fields as $f):
                $name = $f['name'];
                if ($name === 'member_id') continue; // already rendered above
                $val  = $row[$name] ?? '';
                $req  = $f['required'] ? 'required' : '';
                $req_label = $f['required'] ? ' *' : '';

                // Override: sport-specific select (np. swimming.stroke)
                if (isset($extraSelects[$name])): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?= View::e($extraSelects[$name]['label']) ?><?= $req_label ?></label>
                        <select name="<?= View::e($name) ?>" class="form-select" <?= $req ?>>
                            <?php if (!$f['required']): ?><option value="">—</option><?php endif; ?>
                            <?php foreach ($extraSelects[$name]['options'] as $optKey => $optLabel): ?>
                                <option value="<?= View::e((string)$optKey) ?>" <?= (string)$val === (string)$optKey ? 'selected' : '' ?>>
                                    <?= View::e($optLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php continue; endif; ?>

                <?php if ($f['input_type'] === 'enum'): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?= View::e($f['label']) ?><?= $req_label ?></label>
                        <select name="<?= View::e($name) ?>" class="form-select" <?= $req ?>>
                            <?php if (!$f['required']): ?><option value="">—</option><?php endif; ?>
                            <?php foreach ($f['options'] as $optKey => $optLabel): ?>
                                <option value="<?= View::e((string)$optKey) ?>" <?= (string)$val === (string)$optKey ? 'selected' : '' ?>>
                                    <?= View::e($optLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php elseif ($f['input_type'] === 'textarea'): ?>
                    <div class="col-12">
                        <label class="form-label"><?= View::e($f['label']) ?><?= $req_label ?></label>
                        <textarea name="<?= View::e($name) ?>" class="form-control" rows="2" <?= $req ?>><?= View::e((string)$val) ?></textarea>
                    </div>
                <?php elseif ($f['input_type'] === 'checkbox'): ?>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="<?= View::e($name) ?>" value="0">
                            <input type="checkbox" name="<?= View::e($name) ?>" value="1" class="form-check-input"
                                   id="cb_<?= View::e($name) ?>" <?= !empty($val) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="cb_<?= View::e($name) ?>">
                                <?= View::e($f['label']) ?>
                            </label>
                        </div>
                    </div>
                <?php else: ?>
                    <?php
                    $inputType = match ($f['input_type']) {
                        'date'           => 'date',
                        'datetime-local' => 'datetime-local',
                        'number'         => 'number',
                        'number_decimal' => 'number',
                        default          => 'text',
                    };
                    $stepAttr = $f['input_type'] === 'number_decimal' ? 'step="0.01"' : '';
                    $maxLen   = $f['max_length'] ? "maxlength=\"{$f['max_length']}\"" : '';
                    ?>
                    <div class="col-md-6">
                        <label class="form-label"><?= View::e($f['label']) ?><?= $req_label ?></label>
                        <input type="<?= $inputType ?>" name="<?= View::e($name) ?>" class="form-control"
                               value="<?= View::e((string)$val) ?>" <?= $req ?> <?= $stepAttr ?> <?= $maxLen ?>>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= View::e($cancelUrl) ?>" class="btn btn-outline-secondary">Anuluj</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2 me-1"></i> Zapisz zmiany
            </button>
        </div>
    </form>
</div>
