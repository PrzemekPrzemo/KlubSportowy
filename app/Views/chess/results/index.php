<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Wyniki partii — Szachy</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resultModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Zawodnik</th>
                    <th>Data</th>
                    <th>Zawody</th>
                    <th>Rywal</th>
                    <th>Wynik</th>
                    <th>Kolor</th>
                    <th>Kategoria</th>
                    <th>Zmiana ELO</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Brak wyników.</td></tr>
            <?php else: ?>
                <?php foreach ($results as $r):
                    [$resultBadge, $resultIcon] = match($r['result']) {
                        'win'  => ['bg-success', '🏆'],
                        'draw' => ['bg-warning text-dark', '½'],
                        'loss' => ['bg-danger', '✗'],
                        default => ['bg-secondary', '?'],
                    };
                    $colorBadge = match($r['color'] ?? null) {
                        'white' => '<span class="badge bg-light text-dark border">Białe</span>',
                        'black' => '<span class="badge bg-dark">Czarne</span>',
                        default => '<span class="text-muted">—</span>',
                    };
                    $catLabel = $categories[$r['category']] ?? $r['category'];
                    $eloChange = $r['rating_change'];
                    $eloHtml = '—';
                    if ($eloChange !== null) {
                        if ((int)$eloChange > 0) {
                            $eloHtml = '<span class="text-success">+'.View::e($eloChange).'</span>';
                        } elseif ((int)$eloChange < 0) {
                            $eloHtml = '<span class="text-danger">'.View::e($eloChange).'</span>';
                        } else {
                            $eloHtml = '<span class="text-muted">0</span>';
                        }
                    }
                ?>
                    <tr>
                        <td><strong><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></strong></td>
                        <td><?= View::e($r['competition_date']) ?></td>
                        <td>
                            <?= View::e($r['competition_name']) ?>
                            <?php if ($r['tournament_round']): ?>
                                <br><span class="text-muted small"><?= View::e($r['tournament_round']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= View::e($r['opponent_name'] ?? '—') ?></td>
                        <td>
                            <span class="badge <?= $resultBadge ?>">
                                <?= $resultIcon ?>
                                <?= View::e(match($r['result']) { 'win' => 'Wygrana', 'draw' => 'Remis', 'loss' => 'Przegrana', default => $r['result'] }) ?>
                            </span>
                        </td>
                        <td><?= $colorBadge ?></td>
                        <td><span class="badge bg-secondary"><?= View::e($catLabel) ?></span></td>
                        <td><?= $eloHtml ?></td>
                        <td>
                            <form method="POST" action="<?= url('chess/results/'.(int)$r['id'].'/delete') ?>"
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
</div>

<!-- Modal: Dodaj wynik -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('chess/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-grid-3x3 me-1"></i> Dodaj wynik partii</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
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
                        <div class="col-md-6">
                            <label class="form-label">Data *</label>
                            <input type="date" name="competition_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Nazwa zawodów *</label>
                            <input type="text" name="competition_name" class="form-control" required placeholder="np. Mistrzostwa Polski w Szachach">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Runda</label>
                            <input type="text" name="tournament_round" class="form-control" placeholder="np. 5, Final">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Rywal</label>
                            <input type="text" name="opponent_name" class="form-control" placeholder="Imię i nazwisko">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Wynik *</label>
                            <select name="result" class="form-select" required>
                                <option value="win">Wygrana</option>
                                <option value="draw">Remis</option>
                                <option value="loss">Przegrana</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kolor</label>
                            <select name="color" class="form-select">
                                <option value="">— brak —</option>
                                <option value="white">Białe</option>
                                <option value="black">Czarne</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kategoria</label>
                            <select name="category" class="form-select">
                                <?php foreach ($categories as $cKey => $cLabel): ?>
                                    <option value="<?= $cKey ?>"><?= View::e($cLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Otwarcie (kod ECO)</label>
                            <input type="text" name="opening" class="form-control" placeholder="np. E60 Grunfeld">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Miejsce końcowe</label>
                            <input type="number" name="placement" class="form-control" min="1" placeholder="">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Zmiana ELO</label>
                            <input type="number" name="rating_change" class="form-control" placeholder="np. +15 lub -8">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-grid-3x3 me-1"></i> Zapisz wynik</button>
                </div>
            </form>
        </div>
    </div>
</div>
