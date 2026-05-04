<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Wyniki strzelań — Łucznictwo</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#scoreModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Zawodnik</th><th>Data</th><th>Dyscyplina</th><th>Łuk</th><th>Wynik</th><th>10-ki</th><th>X</th><th>Kat. wiek.</th><th>Miejsce</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($scores)): ?>
            <tr><td colspan="10" class="text-center text-muted py-4">Brak wyników.</td></tr>
        <?php else: ?>
            <?php foreach ($scores as $s):
                $medal = match((int)$s['placement']) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
            ?>
                <tr>
                    <td><strong><?= View::e($s['last_name']) ?> <?= View::e($s['first_name']) ?></strong></td>
                    <td><?= View::e($s['score_date']) ?></td>
                    <td><span class="badge bg-info text-dark"><?= View::e($disciplines[$s['discipline']] ?? $s['discipline']) ?></span></td>
                    <td class="small text-muted"><?= View::e($bowTypes[$s['bow_type']] ?? ($s['bow_type'] ?? '—')) ?></td>
                    <td><strong><?= View::e($s['total_score']) ?></strong></td>
                    <td><?= View::e($s['tens'] ?? '—') ?></td>
                    <td><?= View::e($s['xs'] ?? '—') ?></td>
                    <td><?= View::e($s['age_category'] ?? '—') ?></td>
                    <td><?= $medal ?> <?= $s['placement'] ? View::e($s['placement']).'.' : '—' ?></td>
                    <td>
                        <form method="POST" action="<?= url('archery/scores/'.(int)$s['id'].'/delete') ?>"
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
<div class="modal fade" id="scoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('archery/scores/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bullseye me-1"></i> Dodaj wynik strzelania</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Zawodnik *</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Data *</label>
                            <input type="date" name="score_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Zawody (opcjonalnie)</label>
                        <input type="text" name="competition_name" class="form-control" placeholder="np. Mistrzostwa Polski w Łucznictwie">
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
                            <label class="form-label">Typ łuku</label>
                            <select name="bow_type" class="form-select">
                                <option value="">— dowolny —</option>
                                <?php foreach ($bowTypes as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Wynik łączny *</label>
                            <input type="number" name="total_score" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ilość rund</label>
                            <input type="number" name="rounds" class="form-control" min="1" max="10" value="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Dziesiątki</label>
                            <input type="number" name="tens" class="form-control" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">X (najlepsze)</label>
                            <input type="number" name="xs" class="form-control" min="0">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategoria wiekowa</label>
                            <input type="text" name="age_category" class="form-control" placeholder="np. Juniorzy, Senior">
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
                    <button type="submit" class="btn btn-success"><i class="bi bi-bullseye me-1"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
