<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-people me-2"></i>Posiedzenia — Stowarzyszenie</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#meetingModal">
        <i class="bi bi-plus-circle"></i> Nowe posiedzenie
    </button>
</div>

<!-- Filters -->
<div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
    <a href="<?= url('association/meetings') ?>" class="btn btn-sm <?= $filterType === null ? 'btn-primary' : 'btn-outline-secondary' ?>">Wszystkie</a>
    <?php foreach ($meetingTypes as $key => $label): ?>
        <a href="<?= url('association/meetings?type=' . urlencode($key)) ?>"
           class="btn btn-sm <?= $filterType === $key ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <?= View::e($label) ?>
        </a>
    <?php endforeach; ?>
    <span class="ms-2 text-muted small">Rok:</span>
    <?php foreach ([date('Y'), date('Y')-1, date('Y')-2] as $y): ?>
        <a href="<?= url('association/meetings?year=' . $y . ($filterType ? '&type=' . urlencode($filterType) : '')) ?>"
           class="btn btn-sm <?= (int)$filterYear === $y ? 'btn-dark' : 'btn-outline-secondary' ?>"><?= $y ?></a>
    <?php endforeach; ?>
</div>

<?php
$typeBadge = [
    'walne'             => 'bg-danger',
    'zarząd'            => 'bg-primary',
    'komisja_rewizyjna' => 'bg-warning text-dark',
    'nadzwyczajne'      => 'bg-dark',
];
?>

<?php if (empty($meetings)): ?>
    <div class="alert alert-secondary">Brak posiedzeń.</div>
<?php else: ?>
    <div class="card">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Data</th><th>Typ</th><th>Miejsce</th><th>Kworum</th><th>Uchwały</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($meetings as $m): ?>
                <tr>
                    <td><strong><?= View::e($m['meeting_date']) ?></strong></td>
                    <td><span class="badge <?= $typeBadge[$m['meeting_type']] ?? 'bg-secondary' ?>"><?= View::e($meetingTypes[$m['meeting_type']] ?? $m['meeting_type']) ?></span></td>
                    <td class="text-muted"><?= View::e($m['location'] ?? '—') ?></td>
                    <td>
                        <?php if ($m['quorum_reached']): ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Tak</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Nie</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= (int)($m['vote_count'] ?? 0) ?></span></td>
                    <td>
                        <a href="<?= url('association/meetings/' . (int)$m['id']) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Szczegóły
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
    <nav class="mt-3">
        <ul class="pagination pagination-sm">
            <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                <li class="page-item <?= $p === $pagination['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('association/meetings?page=' . $p . ($filterType ? '&type=' . urlencode($filterType) : '') . ($filterYear ? '&year=' . $filterYear : '')) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
<?php endif; ?>

<!-- Modal: Nowe posiedzenie -->
<div class="modal fade" id="meetingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('association/meetings/create') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-people me-1"></i> Nowe posiedzenie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label class="form-label">Typ posiedzenia *</label>
                            <select name="meeting_type" class="form-select" required>
                                <?php foreach ($meetingTypes as $key => $label): ?>
                                    <option value="<?= View::e($key) ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Data *</label>
                            <input type="date" name="meeting_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Miejsce</label>
                        <input type="text" name="location" class="form-control" placeholder="np. Sala konferencyjna, ul. Sportowa 1">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="quorum_reached" id="quorumCheck" value="1">
                            <label class="form-check-label" for="quorumCheck">Kworum osiągnięte</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Porządek obrad</label>
                        <textarea name="agenda" class="form-control" rows="4" placeholder="1. Otwarcie zebrania&#10;2. Wybór przewodniczącego&#10;3. ..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Utwórz</button>
                </div>
            </form>
        </div>
    </div>
</div>
