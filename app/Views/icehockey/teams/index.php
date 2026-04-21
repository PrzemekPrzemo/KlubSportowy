<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-snow text-primary me-2"></i>Drużyny — Hokej na lodzie</h4>
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
                <?php if ($t['age_group']): ?><span class="badge bg-light text-dark ms-2"><?= View::e($t['age_group']) ?></span><?php endif; ?>
                <?php if ($t['arena']): ?><small class="text-muted ms-2"><i class="bi bi-geo-alt"></i> <?= View::e($t['arena']) ?></small><?php endif; ?>
                <?php if ($t['coach_last']): ?><small class="text-muted ms-2"><i class="bi bi-person-badge"></i> <?= View::e($t['coach_last']) ?></small><?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addPlayer<?= (int)$t['id'] ?>">
                    <i class="bi bi-plus"></i> Zawodnik
                </button>
                <form method="POST" action="<?= url('icehockey/teams/' . (int)$t['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th style="width:60px">Nr</th><th>Zawodnik</th><th>Pozycja</th><th>Chwyt</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (empty($roster)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Brak zawodników.</td></tr>
                    <?php else: foreach ($roster as $p):
                        $posInfo = $positions[$p['position']] ?? ['label' => $p['position'], 'class' => 'secondary'];
                    ?>
                        <tr>
                            <td><span class="badge bg-dark"><?= $p['jersey_number'] ? (int)$p['jersey_number'] : '—' ?></span></td>
                            <td>
                                <?= View::e($p['last_name'] . ' ' . $p['first_name']) ?>
                                <?php if ($p['is_captain']): ?><span class="badge bg-warning text-dark ms-1">C</span><?php endif; ?>
                                <?php if ($p['is_assistant']): ?><span class="badge bg-info ms-1">A</span><?php endif; ?>
                            </td>
                            <td><span class="badge bg-<?= $posInfo['class'] ?>"><?= View::e($posInfo['label']) ?></span></td>
                            <td><small class="text-muted"><?= View::e($p['shoots']) ?></small></td>
                            <td>
                                <form method="POST" action="<?= url('icehockey/players/' . (int)$p['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

    <div class="modal fade" id="addPlayer<?= (int)$t['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="<?= url('icehockey/teams/' . (int)$t['id'] . '/player') ?>">
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
                            <div class="col-4">
                                <label class="form-label">Nr</label>
                                <input type="number" name="jersey_number" class="form-control" min="1" max="99">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Pozycja</label>
                                <select name="position" class="form-select">
                                    <?php foreach ($positions as $k => $v): ?>
                                        <option value="<?= $k ?>"><?= View::e($v['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="form-label">Chwyt kija</label>
                                <select name="shoots" class="form-select">
                                    <option value="prawy">Prawy</option>
                                    <option value="lewy">Lewy</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-check mt-3"><input type="checkbox" name="is_captain" id="cap<?= (int)$t['id'] ?>" class="form-check-input"><label class="form-check-label" for="cap<?= (int)$t['id'] ?>">Kapitan (C)</label></div>
                        <div class="form-check"><input type="checkbox" name="is_assistant" id="a<?= (int)$t['id'] ?>" class="form-check-input"><label class="form-check-label" for="a<?= (int)$t['id'] ?>">Asystent (A)</label></div>
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
            <form method="POST" action="<?= url('icehockey/teams/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Nowa drużyna</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nazwa</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Grupa wiekowa</label><input type="text" name="age_group" class="form-control" placeholder="np. U20, Senior"></div>
                    <div class="mb-3"><label class="form-label">Lodowisko</label><input type="text" name="arena" class="form-control"></div>
                    <div class="mb-3">
                        <label class="form-label">Trener</label>
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
