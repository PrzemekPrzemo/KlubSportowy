<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Pary taneczne — Taniec sportowy</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#coupleModal">
        <i class="bi bi-plus-circle"></i> Dodaj parę
    </button>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Para</th><th>Prowadzący</th><th>Partner(ka)</th><th>Dyscyplina</th><th>Klasa</th><th>Od</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($couples)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Brak zarejestrowanych par.</td></tr>
        <?php else: ?>
            <?php foreach ($couples as $c): ?>
                <tr>
                    <td><strong><?= View::e($c['couple_name'] ?: $c['leader_last'].' / '.($c['follower_last'] ?? '?')) ?></strong></td>
                    <td><?= View::e($c['leader_last']) ?> <?= View::e($c['leader_first']) ?></td>
                    <td><?= $c['follower_last'] ? View::e($c['follower_last'].' '.$c['follower_first']) : '<em class="text-muted">solo</em>' ?></td>
                    <td><span class="badge bg-info text-dark"><?= View::e($disciplines[$c['discipline']] ?? $c['discipline']) ?></span></td>
                    <td><span class="badge bg-primary"><?= View::e($classes[$c['class_level']] ?? $c['class_level']) ?></span></td>
                    <td><?= View::e($c['active_from'] ?? '—') ?></td>
                    <td>
                        <span class="badge bg-<?= $c['status'] === 'active' ? 'success' : 'secondary' ?>">
                            <?= $c['status'] === 'active' ? 'aktywna' : 'nieaktywna' ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="<?= url('dance_sport/couples/'.(int)$c['id'].'/delete') ?>"
                              onsubmit="return confirm('Usunąć parę?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal: Dodaj parę -->
<div class="modal fade" id="coupleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('dance_sport/couples/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-music-note-beamed me-1"></i> Dodaj parę taneczną</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nazwa pary (opcjonalnie)</label>
                        <input type="text" name="couple_name" class="form-control" placeholder="np. Kowalski / Nowak">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Prowadzący (lider) *</label>
                            <select name="leader_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Partner(ka) (follower)</label>
                            <select name="follower_id" class="form-select">
                                <option value="">— solo / brak —</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Dyscyplina *</label>
                            <select name="discipline" class="form-select" required>
                                <?php foreach ($disciplines as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Klasa *</label>
                            <select name="class_level" class="form-select" required>
                                <?php foreach ($classes as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Aktywna od</label>
                            <input type="date" name="active_from" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Aktywna do</label>
                            <input type="date" name="active_to" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i> Dodaj</button>
                </div>
            </form>
        </div>
    </div>
</div>
