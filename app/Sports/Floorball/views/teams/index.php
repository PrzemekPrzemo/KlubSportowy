<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Drużyny — Floorball</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#teamModal">
        <i class="bi bi-plus-circle"></i> Nowa drużyna
    </button>
</div>

<?php if (empty($teams)): ?>
<div class="alert alert-info">Brak drużyn. Dodaj pierwszą drużynę.</div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($teams as $t): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-people-fill me-1"></i><?= View::e($t['name']) ?>
                    <?php if ($t['age_group']): ?><small class="text-muted">(<?= View::e($t['age_group']) ?>)</small><?php endif; ?>
                </h6>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal" data-bs-target="#addPlayerModal"
                            data-teamid="<?= (int)$t['id'] ?>"
                            data-teamname="<?= View::e($t['name']) ?>">
                        <i class="bi bi-person-plus"></i>
                    </button>
                    <form method="POST" action="<?= url('floorball/teams/' . (int)$t['id'] . '/delete') ?>"
                          onsubmit="return confirm('Usunąć drużynę?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
            <div class="card-body p-2">
                <?php $roster = $rosters[$t['id']] ?? []; ?>
                <?php if (empty($roster)): ?>
                    <div class="text-muted small p-2">Brak zawodników</div>
                <?php else: ?>
                <table class="table table-sm mb-0">
                    <tbody>
                    <?php foreach ($roster as $p): ?>
                        <tr>
                            <td class="text-muted" style="width:2.5rem"><?= $p['jersey_number'] ? '#' . (int)$p['jersey_number'] : '—' ?></td>
                            <td><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></td>
                            <td><span class="badge bg-secondary"><?= View::e($p['position']) ?></span></td>
                            <td>
                                <form method="POST" action="<?= url('floorball/teams/' . (int)$t['id'] . '/player/remove') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="member_id" value="<?= (int)$p['member_id'] ?>">
                                    <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-x"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal: Nowa drużyna -->
<div class="modal fade" id="teamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('floorball/teams/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Nowa drużyna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nazwa drużyny</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategoria wiekowa</label>
                        <input type="text" name="age_group" class="form-control" placeholder="np. Senior, U18">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Dodaj drużynę</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Dodaj zawodnika do drużyny -->
<div class="modal fade" id="addPlayerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addPlayerForm" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Dodaj zawodnika do drużyny <span id="modal-team-name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawodnik</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label">Numer</label>
                            <input type="number" name="jersey_number" class="form-control" min="1" max="99">
                        </div>
                        <div class="col-8">
                            <label class="form-label">Pozycja</label>
                            <select name="position" class="form-select">
                                <option value="napastnik">Napastnik</option>
                                <option value="obrońca">Obrońca</option>
                                <option value="bramkarz">Bramkarz</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Dodaj</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('addPlayerModal').addEventListener('show.bs.modal', function(e) {
    var btn = e.relatedTarget;
    document.getElementById('addPlayerForm').action = '<?= url('floorball/teams/') ?>' + btn.dataset.teamid + '/player/add';
    document.getElementById('modal-team-name').textContent = btn.dataset.teamname;
});
</script>
