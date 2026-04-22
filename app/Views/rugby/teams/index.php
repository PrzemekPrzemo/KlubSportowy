<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-people-fill text-primary me-2"></i>Drużyny rugby</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#teamModal">
        <i class="bi bi-plus-circle"></i> Nowa drużyna
    </button>
</div>

<?php if (empty($teams)): ?>
    <div class="alert alert-info">Brak drużyn.</div>
<?php else: foreach ($teams as $t): $roster = $rosters[$t['id']] ?? []; ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong><?= View::e($t['name']) ?></strong>
                <span class="badge bg-secondary ms-2"><?= View::e($categories[$t['category']] ?? $t['category']) ?></span>
                <span class="badge bg-dark ms-1"><?= View::e($formats[$t['format']] ?? $t['format']) ?></span>
                <?php if ($t['coach_last']): ?><small class="text-muted ms-2"><i class="bi bi-person-badge"></i> <?= View::e($t['coach_last']) ?></small><?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addP<?= (int)$t['id'] ?>">
                    <i class="bi bi-plus"></i> Zawodnik
                </button>
                <form method="POST" action="<?= url('rugby/teams/' . (int)$t['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th style="width:60px">Nr</th><th>Zawodnik</th><th>Pozycja</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($roster)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">Brak zawodników.</td></tr>
                <?php else: foreach ($roster as $p): ?>
                    <tr>
                        <td><span class="badge bg-dark"><?= $p['jersey_number'] ? (int)$p['jersey_number'] : '—' ?></span></td>
                        <td><?= View::e($p['last_name'] . ' ' . $p['first_name']) ?> <?php if ($p['is_captain']): ?><span class="badge bg-warning text-dark ms-1">K</span><?php endif; ?></td>
                        <td><small><?= View::e($positions[$p['position']] ?? $p['position']) ?></small></td>
                        <td>
                            <form method="POST" action="<?= url('rugby/players/' . (int)$p['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć z drużyny?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="addP<?= (int)$t['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="<?= url('rugby/teams/' . (int)$t['id'] . '/player') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-header"><h5 class="modal-title">Dodaj zawodnika</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Zawodnik</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2">
                            <div class="col-4"><label class="form-label">Nr</label><input type="number" name="jersey_number" class="form-control" min="1" max="30"></div>
                            <div class="col-8"><label class="form-label">Pozycja</label>
                                <select name="position" class="form-select">
                                    <?php foreach ($positions as $k => $v): ?>
                                        <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-check mt-3"><input type="checkbox" name="is_captain" id="rcap<?= (int)$t['id'] ?>" class="form-check-input"><label for="rcap<?= (int)$t['id'] ?>" class="form-check-label">Kapitan</label></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" class="btn btn-success">Dodaj</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; endif; ?>

<div class="modal fade" id="teamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('rugby/teams/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Nowa drużyna</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nazwa</label><input type="text" name="name" class="form-control" required></div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Kategoria</label>
                            <select name="category" class="form-select">
                                <?php foreach ($categories as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Format</label>
                            <select name="format" class="form-select">
                                <?php foreach ($formats as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 mt-3"><label class="form-label">Rocznik (opc.)</label><input type="text" name="age_group" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Trener</label>
                        <select name="coach_id" class="form-select">
                            <option value="">— brak —</option>
                            <?php foreach ($members as $mm): ?>
                                <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Utwórz</button>
                </div>
            </form>
        </div>
    </div>
</div>
