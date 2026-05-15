<?php
/**
 * Generic lista zasobu per-sport (sport_module/index).
 *
 * Wymagane zmienne:
 *   $sportKey    — np. 'judo'
 *   $resource    — array z sport_module_resources (resource_key, resource_label, table_name, icon)
 *   $fields      — z SportTableIntrospector::fields()
 *   $rows        — wiersze z DB (już scoped per club_id)
 *   $primaryKey  — nazwa PK (zwykle 'id')
 *   $fkLabels    — array<string, array<int,string>> (column_name → id → label)
 *   $urlCreate   — URL do dodawania
 *   $urlEdit     — closure(int $id): string
 *   $urlDelete   — closure(int $id): string
 */
use App\Helpers\View;

/** Pomocnik renderujący wartość komórki na bazie typu kolumny. */
$renderCell = function (array $field, $rawValue) use ($fkLabels): string {
    if ($rawValue === null || $rawValue === '') return '<span class="text-muted">—</span>';

    // FK label override
    if (isset($fkLabels[$field['name']][(int)$rawValue])) {
        return View::e($fkLabels[$field['name']][(int)$rawValue]);
    }

    switch ($field['input_type']) {
        case 'checkbox':
            return $rawValue ? '<span class="badge bg-success">tak</span>' : '<span class="badge bg-secondary">nie</span>';
        case 'enum':
            return '<span class="badge bg-light text-dark">' . View::e((string)$rawValue) . '</span>';
        case 'textarea':
        case 'textarea_json':
            $s = (string)$rawValue;
            return View::e(mb_strlen($s) > 80 ? mb_substr($s, 0, 80) . '…' : $s);
        case 'blob_skip':
            return '<span class="text-muted">[binarne]</span>';
        default:
            return View::e((string)$rawValue);
    }
};

// Wybierz kolumny do wyświetlenia w liście — pomiń niewizualne (blob, club_id, timestamps)
$visibleFields = array_values(array_filter($fields, function ($f) {
    if (in_array($f['name'], ['club_id', 'created_at', 'updated_at', 'created_by'], true)) return false;
    if ($f['input_type'] === 'blob_skip') return false;
    return true;
}));
// Ogranicz do max 7 kolumn (UI nie zniesie więcej)
$displayFields = array_slice($visibleFields, 0, 7);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi <?= View::e($resource['icon'] ?? 'bi-table') ?> text-primary me-2"></i>
        <?= View::e($resource['resource_label']) ?>
        <small class="text-muted ms-2"><?= View::e(ucfirst($sportKey)) ?></small>
    </h3>
    <a href="<?= View::e($urlCreate) ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Dodaj
    </a>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= View::e($flashError) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Brak wpisów. Kliknij <strong>Dodaj</strong> aby utworzyć pierwszy.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <?php foreach ($displayFields as $f): ?>
                                <th><?= View::e($f['label']) ?></th>
                            <?php endforeach; ?>
                            <th class="text-end" style="width: 140px;">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="text-muted">#<?= (int)($row[$primaryKey] ?? 0) ?></td>
                                <?php foreach ($displayFields as $f): ?>
                                    <td><?= $renderCell($f, $row[$f['name']] ?? null) ?></td>
                                <?php endforeach; ?>
                                <td class="text-end">
                                    <a href="<?= View::e($urlEdit((int)$row[$primaryKey])) ?>"
                                       class="btn btn-sm btn-outline-primary" title="Edytuj">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="<?= View::e($urlDelete((int)$row[$primaryKey])) ?>"
                                          class="d-inline"
                                          onsubmit="return confirm('Na pewno usunąć ten wpis?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (count($visibleFields) > count($displayFields)): ?>
    <div class="alert alert-info mt-3 small">
        <i class="bi bi-info-circle me-1"></i>
        Niektóre kolumny ukryte na liście (zbyt wiele). Pełny komplet danych
        widoczny w formularzu edycji.
    </div>
<?php endif; ?>
