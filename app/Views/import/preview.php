<?php use App\Helpers\View; ?>
<div class="card mb-3">
    <div class="card-body">
        <h5 class="card-title"><i class="bi bi-table"></i> Podgląd danych</h5>
        <p class="text-muted mb-2">
            Znaleziono <strong><?= (int)($totalRows ?? 0) ?></strong> wierszy.
            Poniżej pierwsze <?= min(10, (int)($totalRows ?? 0)) ?> wierszy:
        </p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0" style="font-size: .85rem;">
                <thead class="table-light">
                    <tr>
                        <th class="text-muted">#</th>
                        <?php foreach ($headers as $h): ?>
                            <th><?= View::e($h) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($previewRows as $i => $row): ?>
                        <tr>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <?php foreach ($headers as $j => $h): ?>
                                <td><?= View::e($row[$j] ?? '') ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form method="POST" action="<?= url('import/execute') ?>">
    <?= csrf_field() ?>

    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-arrows-angle-contract"></i> Mapowanie kolumn</h5>
            <p class="text-muted mb-3">
                Przypisz kolumny z pliku CSV do pól bazy danych. Kolumny oznaczone jako „--" zostaną pominięte.
            </p>
            <div class="row g-3">
                <?php foreach ($headers as $idx => $header): ?>
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label small fw-bold"><?= View::e($header) ?></label>
                        <select name="mapping[<?= (int)$idx ?>]" class="form-select form-select-sm">
                            <option value="--">-- pomiń --</option>
                            <?php foreach ($dbColumns as $col): ?>
                                <?php
                                $suggested = $suggestedMapping[$header] ?? null;
                                $selected  = ($suggested === $col) ? 'selected' : '';
                                $labels    = [
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
                                $label = $labels[$col] ?? $col;
                                ?>
                                <option value="<?= View::e($col) ?>" <?= $selected ?>><?= View::e($label) ?> (<?= View::e($col) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success">
            <i class="bi bi-check-circle"></i> Importuj <?= (int)($totalRows ?? 0) ?> wierszy
        </button>
        <a href="<?= url('import') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Wróć i prześlij inny plik
        </a>
    </div>
</form>
