<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-people-fill text-success me-2"></i>Drużyny — Futsal</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#teamModal">
        <i class="bi bi-plus-circle"></i> Dodaj drużynę
    </button>
</div>

<div class="row g-3">
<?php if (empty($teams)): ?>
    <div class="col-12 text-muted">Brak drużyn — dodaj pierwszą.</div>
<?php else: foreach ($teams as $t): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><strong><?= View::e($t['name']) ?></strong>
                      <span class="badge bg-secondary"><?= View::e($t['category']) ?></span></span>
                <form method="POST" action="<?= url('futsal/teams/' . (int)$t['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć drużynę?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
            <div class="card-body p-2">
                <p class="small text-muted mb-2">Zawodników: <?= (int)$t['player_count'] ?></p>
                <ul class="list-group list-group-flush small mb-2">
                    <?php foreach (($rosters[$t['id']] ?? []) as $p): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-1">
                            <span>
                                <?php if ($p['jersey_number']): ?><span class="badge bg-dark"><?= (int)$p['jersey_number'] ?></span><?php endif; ?>
                                <?= View::e($p['first_name'] . ' ' . $p['last_name']) ?>
                                <span class="text-muted small">(<?= View::e($p['position']) ?>)</span>
                                <?php if ($p['is_captain']): ?><i class="bi bi-c-circle text-warning" title="kapitan"></i><?php endif; ?>
                            </span>
                            <form method="POST" action="<?= url('futsal/players/' . (int)$p['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć z drużyny?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-x"></i></button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button class="btn btn-sm btn-outline-success w-100" data-bs-toggle="modal" data-bs-target="#playerModal-<?= (int)$t['id'] ?>">
                    <i class="bi bi-person-plus"></i> Dodaj zawodnika
                </button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="playerModal-<?= (int)$t['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="<?= url('futsal/teams/' . (int)$t['id'] . '/player') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-header"><h5 class="modal-title">Dodaj zawodnika — <?= View::e($t['name']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-2"><label class="form-label">ID członka klubu</label>
                            <input type="number" name="member_id" class="form-control" required min="1" placeholder="np. 42">
                            <small class="text-muted">Wpisz ID członka (znajdziesz na liście Członków).</small>
                        </div>
                        <div class="row g-2">
                            <div class="col-4"><label class="form-label">Numer</label>
                                <input type="number" name="jersey_number" class="form-control" min="1" max="99">
                            </div>
                            <div class="col-5"><label class="form-label">Pozycja</label>
                                <select name="position" class="form-select">
                                    <option value="bramkarz">Bramkarz</option>
                                    <option value="obronca">Obrońca</option>
                                    <option value="skrzydlowy">Skrzydłowy</option>
                                    <option value="pivot">Pivot</option>
                                    <option value="uniwersalny" selected>Uniwersalny</option>
                                </select>
                            </div>
                            <div class="col-3 d-flex align-items-end"><div class="form-check">
                                <input type="checkbox" name="is_captain" value="1" id="cap-<?= (int)$t['id'] ?>" class="form-check-input">
                                <label for="cap-<?= (int)$t['id'] ?>" class="form-check-label">Kapitan</label>
                            </div></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button class="btn btn-success">Dodaj</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; endif; ?>
</div>

<div class="modal fade" id="teamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('futsal/teams/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj drużynę</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label">Nazwa</label>
                        <input type="text" name="name" class="form-control" required maxlength="150" placeholder="np. Futsal Seniorzy A">
                    </div>
                    <div class="mb-2"><label class="form-label">Kategoria</label>
                        <select name="category" class="form-select">
                            <option value="senior_m">Seniorzy M</option>
                            <option value="senior_k">Seniorki K</option>
                            <option value="junior_m">Juniorzy M</option>
                            <option value="junior_k">Juniorki K</option>
                            <option value="U18">U18</option>
                            <option value="U16">U16</option>
                            <option value="U14">U14</option>
                            <option value="dzieci">Dzieci</option>
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">Grupa wiekowa (opc.)</label>
                        <input type="text" name="age_group" class="form-control" maxlength="50">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button class="btn btn-success">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
