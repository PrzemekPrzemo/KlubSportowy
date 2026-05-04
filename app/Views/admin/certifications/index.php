<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-patch-check-fill text-primary me-2"></i>Uprawnienia trenerskie i sędziowskie</h4>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#certModal">
        <i class="bi bi-plus-circle"></i> Dodaj uprawnienie
    </button>
</div>

<div class="alert alert-info small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Wymóg ustawowy</strong> (ustawa o sporcie art. 41): prowadzenie zajęć z młodzieżą wymaga odpowiednich uprawnień.
    Polskie związki sportowe wymagają trener klasy II+ dla szkoleń licencyjnych.
</div>

<?php if (!empty($expiring)): ?>
<div class="alert alert-warning mb-3">
    <strong><i class="bi bi-clock me-1"></i> <?= count($expiring) ?> uprawnień wygasa w ciągu 60 dni:</strong>
    <ul class="mb-0 mt-2 small">
        <?php foreach (array_slice($expiring, 0, 10) as $e):
            $days = (int)($e['days_remaining'] ?? 0);
            $name = $e['member_last'] ? ($e['member_last'] . ' ' . $e['member_first']) : ($e['user_name'] ?? '—');
        ?>
            <li><?= View::e($name) ?> — <?= View::e($e['cert_name']) ?> (za <?= $days ?> dni, sport: <?= View::e($e['sport_key']) ?>)</li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="GET" class="mb-3 d-flex gap-2">
    <select name="sport" class="form-select form-select-sm" style="width:200px;">
        <option value="">Wszystkie sporty</option>
        <?php foreach ($sports as $s): ?>
            <option value="<?= View::e($s['key']) ?>" <?= $sportFilter === $s['key'] ? 'selected' : '' ?>><?= View::e($s['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="level" class="form-select form-select-sm" style="width:250px;">
        <option value="">Wszystkie poziomy</option>
        <?php foreach ($levels as $k => $l): ?>
            <option value="<?= $k ?>" <?= $levelFilter === $k ? 'selected' : '' ?>><?= View::e($l['label']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i></button>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Osoba</th><th>Sport</th><th>Uprawnienie</th><th>Poziom</th>
                    <th>Numer</th><th>Wydane przez</th><th>Data</th><th>Ważne do</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($certs)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Brak uprawnień.</td></tr>
            <?php else: foreach ($certs as $c):
                $li = $levels[$c['cert_level']] ?? ['label' => $c['cert_level'], 'class' => 'secondary'];
                $days = $c['days_remaining'] !== null ? (int)$c['days_remaining'] : null;
                $name = $c['member_last'] ? ($c['member_last'] . ' ' . $c['member_first']) : ($c['user_name'] ?? '—');
            ?>
                <tr>
                    <td>
                        <strong><?= View::e($name) ?></strong>
                        <?php if ($c['member_number']): ?><small class="text-muted">#<?= View::e($c['member_number']) ?></small><?php endif; ?>
                    </td>
                    <td><small class="text-muted"><?= View::e($c['sport_key']) ?></small></td>
                    <td><?= View::e($c['cert_name']) ?></td>
                    <td><span class="badge bg-<?= $li['class'] ?>"><?= View::e($li['label']) ?></span></td>
                    <td class="small font-monospace"><?= View::e($c['cert_number'] ?? '—') ?></td>
                    <td class="small"><?= View::e($c['issuing_body'] ?? '—') ?></td>
                    <td class="small"><?= View::e($c['issued_at']) ?></td>
                    <td class="small"><?= View::e($c['valid_until'] ?? 'bezterminowe') ?></td>
                    <td>
                        <?php if ($c['valid_until'] === null): ?>
                            <span class="badge bg-success">Bezterminowe</span>
                        <?php elseif ($days < 0): ?>
                            <span class="badge bg-danger">Wygasłe (<?= abs($days) ?> dni)</span>
                        <?php elseif ($days <= 60): ?>
                            <span class="badge bg-warning text-dark">Kończy za <?= $days ?> dni</span>
                        <?php else: ?>
                            <span class="badge bg-success">Aktywne</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" action="<?= url('certifications/' . (int)$c['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="certModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('certifications/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Nowe uprawnienie</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Zawodnik/trener (z członków)</label>
                            <select name="member_id" class="form-select">
                                <option value="">— brak —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Wybierz osobę jeśli jest członkiem klubu</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Sport</label>
                            <select name="sport_key" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($sports as $s): ?>
                                    <option value="<?= View::e($s['key']) ?>"><?= View::e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nazwa uprawnienia</label>
                            <input type="text" name="cert_name" class="form-control" required placeholder="np. Trener klasy II — PZKosz">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Poziom</label>
                            <select name="cert_level" class="form-select">
                                <?php foreach ($levels as $k => $l): ?>
                                    <option value="<?= $k ?>"><?= View::e($l['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Numer uprawnienia</label>
                            <input type="text" name="cert_number" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Wydane przez</label>
                            <input type="text" name="issuing_body" class="form-control" placeholder="np. PZKosz, AWF Warszawa">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Data wydania</label>
                            <input type="date" name="issued_at" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Ważne do (puste = bezterminowe)</label>
                            <input type="date" name="valid_until" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Uwagi</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
