<?php
use App\Helpers\View;

/** @var array $event */
/** @var array $entries */
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-clipboard-data me-2"></i>Wyniki wydarzenia</h4>
        <div class="text-muted small">
            <?= View::e($event['name']) ?>
            <?php if (!empty($event['sport_name'])): ?>
                · Sport: <?= View::e($event['sport_name']) ?>
            <?php endif; ?>
            · <?= format_date($event['event_date']) ?>
        </div>
    </div>
    <a href="<?= url('events') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<?php if (empty($entries)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i>
        Brak zapisanych zawodników do tego wydarzenia. Najpierw dodaj zgłoszenia (event_entries).
    </div>
<?php else: ?>
<form method="POST" action="<?= url('events/' . (int)$event['id'] . '/results/save') ?>">
    <?= csrf_field() ?>

    <div class="card mb-3">
        <div class="card-header"><strong>Lista uczestników</strong></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Zawodnik</th>
                        <th style="width:120px">Score</th>
                        <th style="width:100px">Miejsce</th>
                        <th style="width:120px">Czas (s)</th>
                        <th>Uwagi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $i => $e):
                    $mid = (int)$e['member_id'];
                    $extra = !empty($e['extra']) ? json_decode((string)$e['extra'], true) : [];
                    $time  = is_array($extra) && isset($extra['time']) ? $extra['time'] : '';
                ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <?= View::e($e['last_name']) ?> <?= View::e($e['first_name']) ?>
                            <span class="text-muted small d-block"><?= View::e($e['member_number'] ?? '') ?></span>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="results[<?= $mid ?>][score]"
                                   class="form-control form-control-sm"
                                   value="<?= $e['score'] !== null ? View::e($e['score']) : '' ?>">
                        </td>
                        <td>
                            <input type="number" min="1" step="1" name="results[<?= $mid ?>][place]"
                                   class="form-control form-control-sm"
                                   value="<?= $e['place'] !== null ? (int)$e['place'] : '' ?>">
                        </td>
                        <td>
                            <input type="number" step="0.001" name="results[<?= $mid ?>][time]"
                                   class="form-control form-control-sm"
                                   value="<?= $time !== '' ? View::e($time) : '' ?>">
                        </td>
                        <td>
                            <input type="text" name="results[<?= $mid ?>][notes]"
                                   class="form-control form-control-sm" maxlength="200">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-success">
            <i class="bi bi-save me-1"></i> Zapisz wyniki i przelicz ranking
        </button>
    </div>
</form>
<?php endif; ?>
