<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-snow text-primary me-2"></i>Drużyny — Curling</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#teamModal">
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
                <form method="POST" action="<?= url('curling/teams/' . (int)$t['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
            <div class="card-body p-2">
                <p class="small text-muted mb-2">Skład: <?= (int)$t['player_count'] ?> / 5</p>
                <ul class="list-group list-group-flush small mb-2">
                    <?php foreach (($rosters[$t['id']] ?? []) as $p): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-2 py-1">
                            <span>
                                <span class="badge bg-info text-dark"><?= View::e($p['position']) ?></span>
                                <?= View::e($p['first_name'] . ' ' . $p['last_name']) ?>
                                <?php if ($p['is_captain']): ?><i class="bi bi-c-circle text-warning" title="kapitan"></i><?php endif; ?>
                            </span>
                            <form method="POST" action="<?= url('curling/players/' . (int)$p['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-x"></i></button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#playerModal-<?= (int)$t['id'] ?>">
                    <i class="bi bi-person-plus"></i> Dodaj zawodnika
                </button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="playerModal-<?= (int)$t['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="<?= url('curling/teams/' . (int)$t['id'] . '/player') ?>">
                    <?= csrf_field() ?>
                    <div class="modal-header"><h5 class="modal-title">Dodaj zawodnika — <?= View::e($t['name']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="mb-2"><label class="form-label">ID członka klubu</label>
                            <input type="number" name="member_id" class="form-control" required min="1">
                        </div>
                        <div class="mb-2"><label class="form-label">Pozycja</label>
                            <select name="position" class="form-select">
                                <option value="skip">Skip (kapitan)</option>
                                <option value="third">Third</option>
                                <option value="second">Second</option>
                                <option value="lead" selected>Lead</option>
                                <option value="alternate">Alternate</option>
                            </select>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_captain" value="1" id="ccap-<?= (int)$t['id'] ?>" class="form-check-input">
                            <label for="ccap-<?= (int)$t['id'] ?>" class="form-check-label">Kapitan</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button class="btn btn-primary">Dodaj</button>
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
            <form method="POST" action="<?= url('curling/teams/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj drużynę</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label">Nazwa</label>
                        <input type="text" name="name" class="form-control" required maxlength="150">
                    </div>
                    <div class="mb-2"><label class="form-label">Kategoria</label>
                        <select name="category" class="form-select">
                            <option value="mixed">Mixed</option>
                            <option value="senior_m">Seniorzy M</option>
                            <option value="senior_k">Seniorki K</option>
                            <option value="mixed_doubles">Mixed doubles</option>
                            <option value="wheelchair">Wheelchair</option>
                            <option value="junior">Junior</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button class="btn btn-primary">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
