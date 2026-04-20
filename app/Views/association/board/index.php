<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-person-badge me-2"></i>Skład Zarządu</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBoardModal">
        <i class="bi bi-person-plus"></i> Dodaj do zarządu
    </button>
</div>

<?php
$active   = array_filter($boardMembers, fn($bm) => $bm['active']);
$inactive = array_filter($boardMembers, fn($bm) => !$bm['active']);
$boardSection = [
    'Zarząd'            => array_filter($active, fn($bm) => in_array($bm['role'], ['prezes','wiceprezes','sekretarz','skarbnik','członek_zarządu'])),
    'Komisja Rewizyjna' => array_filter($active, fn($bm) => in_array($bm['role'], ['komisja_rewizyjna','przewodniczący_kr'])),
];
?>

<?php foreach ($boardSection as $sectionName => $members): ?>
    <div class="card mb-3">
        <div class="card-header fw-semibold"><?= View::e($sectionName) ?></div>
        <?php if (empty($members)): ?>
            <div class="p-3 text-muted small">Brak członków.</div>
        <?php else: ?>
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Imię i nazwisko</th><th>Rola</th><th>Od</th><th>Do</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($members as $bm): ?>
                    <tr>
                        <td>
                            <strong><?= View::e($bm['last_name']) ?> <?= View::e($bm['first_name']) ?></strong>
                            <div class="small text-muted">#<?= View::e($bm['member_number']) ?></div>
                        </td>
                        <td><span class="badge bg-primary"><?= View::e($boardRoles[$bm['role']] ?? $bm['role']) ?></span></td>
                        <td><?= View::e($bm['term_start']) ?></td>
                        <td><?= $bm['term_end'] ? View::e($bm['term_end']) : '<span class="text-muted">bezterminowo</span>' ?></td>
                        <td>
                            <form method="POST" action="<?= url('association/board/update') ?>"
                                  onsubmit="return confirm('Zakończyć kadencję tej osoby?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="deactivate">
                                <input type="hidden" name="board_member_id" value="<?= (int)$bm['id'] ?>">
                                <button class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-person-x"></i> Zakończ
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php if (!empty($inactive)): ?>
<div class="card mb-3">
    <div class="card-header fw-semibold text-muted">
        <i class="bi bi-archive me-1"></i>Poprzednie kadencje (<?= count($inactive) ?>)
    </div>
    <table class="table table-sm mb-0">
        <thead class="table-light">
            <tr><th>Imię i nazwisko</th><th>Rola</th><th>Okres</th></tr>
        </thead>
        <tbody>
        <?php foreach ($inactive as $bm): ?>
            <tr class="text-muted">
                <td><?= View::e($bm['last_name']) ?> <?= View::e($bm['first_name']) ?></td>
                <td><?= View::e($boardRoles[$bm['role']] ?? $bm['role']) ?></td>
                <td><?= View::e($bm['term_start']) ?> – <?= View::e($bm['term_end'] ?? '...') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Modal: Dodaj do zarządu -->
<div class="modal fade" id="addBoardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('association/board/update') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-1"></i> Dodaj do zarządu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawodnik / Członek *</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>">
                                    <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rola *</label>
                        <select name="role" class="form-select" required>
                            <?php foreach ($boardRoles as $key => $label): ?>
                                <option value="<?= View::e($key) ?>"><?= View::e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Początek kadencji *</label>
                            <input type="date" name="term_start" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Koniec kadencji</label>
                            <input type="date" name="term_end" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Dodaj</button>
                </div>
            </form>
        </div>
    </div>
</div>
