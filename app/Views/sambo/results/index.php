<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Wyniki zawodów — Sambo</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Zawodnik</th><th>Zawody</th><th>Data</th><th>Styl</th><th>Waga</th><th>Kat. wiekowa</th><th>Miejsce</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($results)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Brak wyników.</td></tr>
        <?php else: ?>
            <?php foreach ($results as $r):
                $medal = match((int)$r['placement']) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
                $styleLabel = $styles[$r['style']] ?? $r['style'];
                $styleBadge = match($r['style']) {
                    'sport_sambo'    => 'bg-primary',
                    'combat_sambo'   => 'bg-danger',
                    'freestyle_sambo' => 'bg-warning text-dark',
                    'beach_sambo'    => 'bg-info text-dark',
                    default          => 'bg-secondary',
                };
            ?>
                <tr>
                    <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                    <td><?= View::e($r['competition_name']) ?></td>
                    <td><?= View::e($r['competition_date']) ?></td>
                    <td><span class="badge <?= $styleBadge ?>"><?= View::e($styleLabel) ?></span></td>
                    <td><?= View::e($r['weight_class'] ? $r['weight_class'].' kg' : '—') ?></td>
                    <td><?= View::e($r['age_category'] ?? '—') ?></td>
                    <td><?= $medal ?> <?= $r['placement'] ? View::e($r['placement']).'.' : '—' ?></td>
                    <td>
                        <form method="POST" action="<?= url('sambo/results/'.(int)$r['id'].'/delete') ?>"
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('sambo/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-trophy me-1"></i> Dodaj wynik zawodów</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawodnik *</label>
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
                        <label class="form-label">Nazwa zawodów *</label>
                        <input type="text" name="competition_name" class="form-control" required placeholder="np. Mistrzostwa Polski Sambo">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Data *</label>
                            <input type="date" name="competition_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Styl</label>
                            <select name="style" class="form-select">
                                <?php foreach ($styles as $sKey => $sLabel): ?>
                                    <option value="<?= $sKey ?>"><?= View::e($sLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategoria wagowa</label>
                            <select name="weight_class" class="form-select">
                                <option value="">— ogólna —</option>
                                <?php foreach ($weightClasses as $wc): ?>
                                    <option value="<?= $wc ?>"><?= $wc ?> kg</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategoria wiekowa</label>
                            <input type="text" name="age_category" class="form-control" placeholder="np. U18, Senior">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Miejsce (1=złoto, 2=srebro, 3=brąz)</label>
                        <input type="number" name="placement" class="form-control" min="1" placeholder="np. 1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-trophy me-1"></i> Zapisz wynik</button>
                </div>
            </form>
        </div>
    </div>
</div>
