<?php
/**
 * Preview wgranego pliku importu + mapowanie kolumn.
 *
 * @var array  $headers
 * @var array  $previewRows
 * @var int    $totalRows
 * @var array  $suggestedMapping
 * @var array  $dbColumns
 * @var string $originalFilename
 */
use App\Helpers\View;

$labels = [
    'first_name'     => 'Imię',
    'last_name'      => 'Nazwisko',
    'email'          => 'E-mail',
    'pesel'          => 'PESEL',
    'birth_date'     => 'Data urodzenia',
    'phone'          => 'Telefon',
    'gender'         => 'Płeć',
    'address_street' => 'Ulica',
    'address_city'   => 'Miasto',
    'address_postal' => 'Kod pocztowy',
    'member_number'  => 'Nr członkowski',
    'join_date'      => 'Data dołączenia',
    'status'         => 'Status',
    'notes'          => 'Notatki',
];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-0"><i class="bi bi-table"></i> Podgląd importu</h1>
        <small class="text-muted">
            Plik: <code><?= View::e((string)($originalFilename ?? '')) ?></code>
            — znaleziono <strong><?= (int)($totalRows ?? 0) ?></strong> wierszy.
        </small>
    </div>
    <a href="<?= url('club/members/import') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Inny plik
    </a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h6 class="card-title"><i class="bi bi-eye"></i> Pierwsze <?= min(50, (int)($totalRows ?? 0)) ?> wierszy</h6>
        <div class="table-responsive" style="max-height: 420px;">
            <table class="table table-sm table-bordered mb-0" style="font-size: .8rem;">
                <thead class="table-light sticky-top">
                    <tr>
                        <th class="text-muted">#</th>
                        <?php foreach ($headers as $h): ?>
                            <th><?= View::e((string)$h) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($previewRows as $i => $row): ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <?php foreach ($headers as $j => $h): ?>
                                <td><?= View::e((string)($row[$j] ?? '')) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form method="POST" action="<?= url('club/members/import/execute') ?>">
    <?= csrf_field() ?>

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="card-title"><i class="bi bi-arrows-angle-contract"></i> Mapowanie kolumn</h6>
            <p class="text-muted small mb-3">
                Wybierz, do którego pola w bazie ma trafić każda kolumna z pliku.
                Kolumny oznaczone „— pomiń —" zostaną zignorowane podczas importu.
            </p>
            <div class="row g-3">
                <?php foreach ($headers as $idx => $header): ?>
                    <?php $suggested = $suggestedMapping[$header] ?? null; ?>
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label small fw-bold"><?= View::e((string)$header) ?></label>
                        <select name="mapping[<?= (int)$idx ?>]" class="form-select form-select-sm">
                            <option value="--">-- pomiń --</option>
                            <?php foreach ($dbColumns as $col):
                                $label = $labels[$col] ?? $col;
                                $sel   = ($suggested === $col) ? 'selected' : '';
                            ?>
                                <option value="<?= View::e($col) ?>" <?= $sel ?>>
                                    <?= View::e($label) ?> (<?= View::e($col) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success">
            <i class="bi bi-check-circle"></i>
            Importuj <?= (int)($totalRows ?? 0) ?> wierszy
        </button>
        <a href="<?= url('club/members/import') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> Anuluj
        </a>
    </div>
</form>
