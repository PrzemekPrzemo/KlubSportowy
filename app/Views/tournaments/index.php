<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-trophy me-2"></i>Turnieje</h4>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/tournaments/pending') ?>" class="btn btn-outline-warning">
            <i class="bi bi-clipboard-check"></i> Oczekujące wyniki
        </a>
        <a href="<?= url('tournaments/create') ?>" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Nowy turniej
        </a>
    </div>
</div>

<!-- Filtr sportu -->
<form method="GET" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="sport" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">— wszystkie sporty —</option>
            <?php foreach ($sports as $key => $s): ?>
                <option value="<?= View::e($key) ?>" <?= ($filterSport ?? '') === $key ? 'selected' : '' ?>>
                    <?= View::e($s['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if (!empty($filterSport)): ?>
        <div class="col-auto">
            <a href="<?= url('tournaments') ?>" class="btn btn-sm btn-outline-secondary">Wyczyść</a>
        </div>
    <?php endif; ?>
</form>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Nazwa</th>
                <th>Sport</th>
                <th>Format</th>
                <th>Data start</th>
                <th>Status</th>
                <th>Uczest.</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($tournaments)): ?>
            <tr>
                <td colspan="7" class="text-center text-muted py-4">Brak turniejów. <a href="<?= url('tournaments/create') ?>">Utwórz pierwszy turniej.</a></td>
            </tr>
        <?php else: ?>
            <?php foreach ($tournaments as $t): ?>
                <?php
                    $formatLabels = [
                        'single_elimination' => 'Puchar (drabinka)',
                        'double_elimination' => 'Podwójna eliminacja',
                        'round_robin'        => 'Każdy z każdym',
                    ];
                    $statusLabels = [
                        'draft'    => ['label' => 'Szkic',   'class' => 'bg-secondary'],
                        'active'   => ['label' => 'Aktywny', 'class' => 'bg-success'],
                        'finished' => ['label' => 'Zakończ.','class' => 'bg-primary'],
                    ];
                    $sportName = $sports[$t['sport_key']]['name'] ?? $t['sport_key'];
                    $st = $statusLabels[$t['status']] ?? ['label' => View::e($t['status']), 'class' => 'bg-secondary'];
                ?>
                <tr>
                    <td><a href="<?= url('tournaments/' . (int)$t['id']) ?>"><?= View::e($t['name']) ?></a></td>
                    <td><?= View::e($sportName) ?></td>
                    <td><?= View::e($formatLabels[$t['format']] ?? $t['format']) ?></td>
                    <td><?= format_date($t['date_start']) ?></td>
                    <td><span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span></td>
                    <td><span class="badge bg-secondary"><?= (int)$t['participant_count'] ?></span></td>
                    <td class="text-end d-flex gap-1 justify-content-end">
                        <a href="<?= url('tournaments/' . (int)$t['id']) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                        <form method="POST" action="<?= url('tournaments/' . (int)$t['id'] . '/delete') ?>"
                              onsubmit="return confirm('Usunąć turniej?')" class="m-0">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
