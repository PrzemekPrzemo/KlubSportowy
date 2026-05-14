<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-bullseye text-primary me-2"></i>Mecze tenisa</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#matchModal">
        <i class="bi bi-plus-circle"></i> Dodaj mecz
    </button>
</div>

<div class="mb-3 d-flex gap-2 align-items-center">
    <label class="small text-muted mb-0">Filtr nawierzchni:</label>
    <a href="<?= url('tennis/matches') ?>" class="btn btn-sm btn-<?= !$surfaceFilter ? 'primary' : 'outline-secondary' ?>">Wszystkie</a>
    <?php foreach ($surfaces as $k => $s): ?>
        <a href="?surface=<?= urlencode($k) ?>"
           class="btn btn-sm btn-<?= $surfaceFilter === $k ? 'primary' : 'outline-secondary' ?>">
            <?= View::e($s['label']) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th><th>Zawodnik 1</th><th>Zawodnik 2</th>
                    <th>Sety</th><th>Zwycięzca</th><th>Typ</th><th>Nawierzchnia</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($matches)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Brak meczów.</td></tr>
            <?php else: foreach ($matches as $m):
                $isP1Winner = (int)$m['winner_id'] === (int)$m['player1_id'];
                $isP2Winner = (int)$m['winner_id'] === (int)$m['player2_id'];
                $surfaceInfo = $surfaces[$m['surface']] ?? ['label' => $m['surface'], 'color' => '#aaa'];
            ?>
                <tr>
                    <td class="small text-muted"><?= View::e($m['match_date']) ?></td>
                    <td class="<?= $isP1Winner ? 'fw-bold text-success' : '' ?>">
                        <?= View::e($m['p1_last'] . ' ' . $m['p1_first']) ?>
                        <?php if ($isP1Winner): ?><i class="bi bi-trophy-fill text-warning ms-1"></i><?php endif; ?>
                    </td>
                    <td class="<?= $isP2Winner ? 'fw-bold text-success' : '' ?>">
                        <?= View::e($m['p2_last'] . ' ' . $m['p2_first']) ?>
                        <?php if ($isP2Winner): ?><i class="bi bi-trophy-fill text-warning ms-1"></i><?php endif; ?>
                    </td>
                    <td class="font-monospace"><?= View::e($m['sets']) ?></td>
                    <td>
                        <?php if ($m['winner_id']): ?>
                            <?= $isP1Winner ? View::e($m['p1_last']) : View::e($m['p2_last']) ?>
                        <?php else: ?>
                            <span class="text-muted small">nierozstrzygnięte</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-secondary"><?= View::e($matchTypes[$m['match_type']] ?? $m['match_type']) ?></span></td>
                    <td>
                        <span class="badge" style="background:<?= $surfaceInfo['color'] ?>;color:#fff;">
                            <?= View::e($surfaceInfo['label']) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="<?= url('tennis/matches/' . (int)$m['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="matchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('tennis/matches/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bullseye me-1"></i>Dodaj mecz</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Zawodnik 1</label>
                            <select name="player1_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Zawodnik 2</label>
                            <select name="player2_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Data meczu</label>
                            <input type="date" name="match_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Sety (np. 6:4,7:5)</label>
                            <input type="text" name="sets" class="form-control" placeholder="6:4,7:5" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Nawierzchnia</label>
                            <select name="surface" class="form-select">
                                <?php foreach ($surfaces as $k => $s): ?>
                                    <option value="<?= $k ?>"><?= View::e($s['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Typ meczu</label>
                            <select name="match_type" class="form-select">
                                <?php foreach ($matchTypes as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= $k === 'towarzyski' ? 'selected' : '' ?>><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Zwycięzca</label>
                            <select name="winner_id" class="form-select">
                                <option value="">— nierozstrzygnięte —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Czas gry (min)</label>
                            <input type="number" name="duration_min" class="form-control" min="0" max="600">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Turniej (opcjonalnie)</label>
                            <input type="text" name="tournament" class="form-control" placeholder="Nazwa turnieju">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Uwagi</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
