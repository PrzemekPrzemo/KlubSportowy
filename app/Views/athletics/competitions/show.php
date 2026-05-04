<?php use App\Helpers\View; $c = $competition; ?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h3 class="mb-1"><?= View::e($c['name']) ?></h3>
        <div class="text-muted small">
            <?= format_date($c['date_from']) ?>
            <?php if (!empty($c['date_to']) && $c['date_to'] !== $c['date_from']): ?>
                – <?= format_date($c['date_to']) ?>
            <?php endif; ?>
            <?php if (!empty($c['location'])): ?> · <?= View::e($c['location']) ?><?php endif; ?>
        </div>
    </div>
    <div>
        <a href="<?= url('athletics/competitions/' . (int)$c['id'] . '/edit') ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i> Edytuj</a>
        <a href="<?= url('athletics/competitions') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card p-3"><div class="text-muted small">Typ</div><div class="fw-bold"><?= View::e($c['type']) ?></div></div>
    </div>
    <div class="col-md-3">
        <div class="card p-3"><div class="text-muted small">Status</div><div class="fw-bold"><?= str_replace('_',' ',View::e($c['status'])) ?></div></div>
    </div>
    <div class="col-md-3">
        <div class="card p-3"><div class="text-muted small">Wyniki</div><div class="fw-bold"><?= count($c['results'] ?? []) ?></div></div>
    </div>
</div>

<?php if (!empty($c['notes'])): ?>
    <div class="card p-3 mb-3">
        <div class="text-muted small mb-1">Notatki</div>
        <?= nl2br(View::e($c['notes'])) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header bg-light"><strong>Wyniki</strong></div>
    <table class="table mb-0">
        <thead class="table-light">
            <tr><th>Zawodnik</th><th>Dyscyplina</th><th>Wynik</th><th>Data</th><th>PB</th><th>KR</th></tr>
        </thead>
        <tbody>
        <?php if (empty($c['results'])): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">
                Brak wyników. Dodaj wynik z poziomu
                <a href="<?= url('athletics/records/create') ?>">listy rekordów</a>
                wskazując te zawody.
            </td></tr>
        <?php else: foreach ($c['results'] as $r): ?>
            <tr>
                <td><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></td>
                <td><?= View::e($r['discipline_name'] ?? '—') ?></td>
                <td><strong><?= View::e($r['result_value']) ?></strong> <?= View::e($r['result_unit']) ?></td>
                <td><?= format_date($r['record_date']) ?></td>
                <td><?php if ((int)$r['is_personal_best']) echo '<span class="badge bg-success">PB</span>'; ?></td>
                <td><?php if ((int)$r['is_club_record']) echo '<span class="badge bg-warning text-dark">KR</span>'; ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
