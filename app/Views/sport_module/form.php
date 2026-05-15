<?php
/**
 * Generic formularz dodawania / edycji rekordu per-sport (sport_module/form).
 *
 * Wymagane zmienne:
 *   $sportKey
 *   $resource    — wiersz z sport_module_resources
 *   $fields      — SportTableIntrospector::fields()
 *   $row         — istniejący rekord (edit) lub [] (create)
 *   $members     — lista członków klubu dla member_picker
 *   $fkOptions   — array<string, array<int,string>> dla fk_select
 *   $formAction  — URL POST
 *   $cancelUrl   — URL "Anuluj"
 *   $isEdit      — bool
 *   $title       — nagłówek
 */
use App\Helpers\View;

// Pomijamy w formularzu: id (PK), club_id (z context), timestamps, blob, generated
$formFields = array_values(array_filter($fields, fn($f) => !$f['is_hidden_in_form'] && $f['input_type'] !== 'blob_skip'));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?> text-primary me-2"></i>
        <?= View::e($title) ?>
    </h3>
    <a href="<?= View::e($cancelUrl) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Anuluj
    </a>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= View::e($flashError) ?></div>
<?php endif; ?>

<div class="card p-4">
    <form method="POST" action="<?= View::e($formAction) ?>" autocomplete="off">
        <?= csrf_field() ?>
        <div class="row g-3">
            <?php foreach ($formFields as $f):
                $name = $f['name'];
                $val  = $row[$name] ?? ($f['default'] ?? '');
                $req  = $f['required'] ? 'required' : '';
                $reqMark = $f['required'] ? ' <span class="text-danger">*</span>' : '';
                $labelHtml = View::e($f['label']) . $reqMark;
            ?>

                <?php if ($f['input_type'] === 'member_picker'): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?= $labelHtml ?></label>
                        <select name="<?= View::e($name) ?>" class="form-select" <?= $req ?>>
                            <option value="">— wybierz zawodnika —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"
                                        <?= (int)$val === (int)$m['id'] ? 'selected' : '' ?>>
                                    <?= View::e((string)($m['last_name'] ?? '')) ?>
                                    <?= View::e((string)($m['first_name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                <?php elseif ($f['input_type'] === 'fk_select'): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?= $labelHtml ?></label>
                        <select name="<?= View::e($name) ?>" class="form-select" <?= $req ?>>
                            <option value="">— wybierz —</option>
                            <?php foreach (($fkOptions[$name] ?? []) as $optId => $optLabel): ?>
                                <option value="<?= (int)$optId ?>"
                                        <?= (int)$val === (int)$optId ? 'selected' : '' ?>>
                                    <?= View::e($optLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($fkOptions[$name])): ?>
                            <small class="text-muted">
                                Brak rekordów w powiązanej tabeli (<code><?= View::e((string)$f['fk_table']) ?></code>).
                            </small>
                        <?php endif; ?>
                    </div>

                <?php elseif ($f['input_type'] === 'enum'): ?>
                    <div class="col-md-6">
                        <label class="form-label"><?= $labelHtml ?></label>
                        <select name="<?= View::e($name) ?>" class="form-select" <?= $req ?>>
                            <?php if (!$f['required']): ?><option value="">—</option><?php endif; ?>
                            <?php foreach (($f['options'] ?? []) as $optKey => $optLabel): ?>
                                <option value="<?= View::e((string)$optKey) ?>"
                                        <?= (string)$val === (string)$optKey ? 'selected' : '' ?>>
                                    <?= View::e($optLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                <?php elseif ($f['input_type'] === 'checkbox'): ?>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input type="hidden" name="<?= View::e($name) ?>" value="0">
                            <input type="checkbox" name="<?= View::e($name) ?>" value="1"
                                   id="cb_<?= View::e($name) ?>"
                                   class="form-check-input"
                                   <?= !empty($val) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="cb_<?= View::e($name) ?>">
                                <?= View::e($f['label']) ?>
                            </label>
                        </div>
                    </div>

                <?php elseif ($f['input_type'] === 'textarea'): ?>
                    <div class="col-12">
                        <label class="form-label"><?= $labelHtml ?></label>
                        <textarea name="<?= View::e($name) ?>" class="form-control" rows="3" <?= $req ?>><?= View::e((string)$val) ?></textarea>
                    </div>

                <?php elseif ($f['input_type'] === 'textarea_json'): ?>
                    <div class="col-12">
                        <label class="form-label"><?= $labelHtml ?></label>
                        <textarea name="<?= View::e($name) ?>" class="form-control font-monospace" rows="4"
                                  placeholder='{"klucz":"wartosc"}' <?= $req ?>><?= View::e(is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : (string)$val) ?></textarea>
                        <small class="text-muted">Wpisz poprawny JSON (np. <code>{"foo":1}</code>).</small>
                    </div>

                <?php else: ?>
                    <?php
                    $inputType = match ($f['input_type']) {
                        'date'           => 'date',
                        'datetime-local' => 'datetime-local',
                        'time'           => 'time',
                        'number'         => 'number',
                        'number_decimal' => 'number',
                        default          => 'text',
                    };
                    $step = $f['input_type'] === 'number_decimal' ? 'step="0.01"' : '';
                    $maxLen = $f['max_length'] ? "maxlength=\"{$f['max_length']}\"" : '';

                    // datetime-local oczekuje formatu YYYY-MM-DDTHH:MM
                    $displayVal = (string)$val;
                    if ($inputType === 'datetime-local' && $displayVal !== '') {
                        $displayVal = str_replace(' ', 'T', substr($displayVal, 0, 16));
                    }
                    ?>
                    <div class="col-md-6">
                        <label class="form-label"><?= $labelHtml ?></label>
                        <input type="<?= $inputType ?>" name="<?= View::e($name) ?>"
                               class="form-control"
                               value="<?= View::e($displayVal) ?>"
                               <?= $req ?> <?= $step ?> <?= $maxLen ?>>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= View::e($cancelUrl) ?>" class="btn btn-outline-secondary">Anuluj</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2 me-1"></i>
                <?= $isEdit ? 'Zapisz zmiany' : 'Dodaj' ?>
            </button>
        </div>
    </form>
</div>
