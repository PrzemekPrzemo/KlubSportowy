<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Wyniki zawodów — Taniec sportowy</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Para / Zawodnik</th><th>Zawody</th><th>Data</th><th>Dyscyplina</th><th>Klasa</th><th>Kat. wiek.</th><th>Runda</th><th>Miejsce</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($results)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Brak wyników.</td></tr>
        <?php else: ?>
            <?php foreach ($results as $r):
                $medal     = match((int)$r['placement']) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
                $partnerName = $r['follower_last'] ? View::e($r['follower_last'].' '.$r['follower_first']) : '';
                $pairLabel = View::e($r['leader_last'].' '.$r['leader_first'])
                             . ($partnerName ? ' / '.$partnerName : '');
            ?>
                <tr>
                    <td><strong><?= $pairLabel ?></strong></td>
                    <td><?= View::e($r['competition_name']) ?></td>
                    <td><?= View::e($r['competition_date']) ?></td>
                    <td><span class="badge bg-info text-dark"><?= View::e($disciplines[$r['discipline']] ?? $r['discipline']) ?></span></td>
                    <td><span class="badge bg-primary"><?= View::e($classes[$r['class_level']] ?? $r['class_level']) ?></span></td>
                    <td><?= View::e($r['age_category'] ?? '—') ?></td>
                    <td><?= View::e($r['round_reached'] ?? '—') ?></td>
                    <td><?= $medal ?> <?= $r['placement'] ? View::e($r['placement']).'.' : '—' ?></td>
                    <td>
                        <form method="POST" action="<?= url('dance_sport/results/'.(int)$r['id'].'/delete') ?>"
                              onsubmit="return confirm('Usunąć wynik?')">
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

<!-- Modal: Dodaj wynik -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('dance_sport/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-trophy me-1"></i> Dodaj wynik zawodów</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Para taneczna (opcjonalnie)</label>
                            <select name="couple_id" class="form-select" id="coupleSelect">
                                <option value="">— wybierz parę —</option>
                                <?php foreach ($couples as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>"
                                            data-leader="<?= (int)$c['leader_id'] ?>">
                                        <?= View::e($c['couple_name'] ?: $c['leader_last'].' / '.($c['follower_last'] ?? '?')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prowadzący (lider) *</label>
                            <select name="leader_id" class="form-select" required id="leaderSelect">
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nazwa zawodów *</label>
                        <input type="text" name="competition_name" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Data *</label>
                            <input type="date" name="competition_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategoria wiekowa</label>
                            <input type="text" name="age_category" class="form-control" placeholder="np. Juniorzy, Senior">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Dyscyplina</label>
                            <select name="discipline" class="form-select">
                                <?php foreach ($disciplines as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Klasa</label>
                            <select name="class_level" class="form-select">
                                <?php foreach ($classes as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Runda (osiągnięta)</label>
                            <input type="text" name="round_reached" class="form-control" placeholder="np. Finał, Półfinał, Ćwierćfinał">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Miejsce</label>
                            <input type="number" name="placement" class="form-control" min="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-trophy me-1"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('coupleSelect').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const leaderId = opt.dataset.leader;
    if (leaderId) {
        document.getElementById('leaderSelect').value = leaderId;
    }
});
</script>
