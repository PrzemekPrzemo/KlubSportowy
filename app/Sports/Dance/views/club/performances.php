<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-trophy text-primary me-2"></i>Taniec — Wystepy</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#perfModal">
        <i class="bi bi-plus-circle"></i> Dodaj wystep
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Turniej</th>
                    <th>Data</th>
                    <th>Zawodnik</th>
                    <th>Partner</th>
                    <th>Styl</th>
                    <th class="text-center">Nr</th>
                    <th class="text-end">Wynik</th>
                    <th class="text-center">Pozycja</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($performances)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Brak wystepow.</td></tr>
            <?php else: foreach ($performances as $p): ?>
                <tr>
                    <td><?= View::e($p['tournament_name']) ?></td>
                    <td class="small text-muted"><?= View::e($p['tournament_date']) ?></td>
                    <td><?= View::e($p['last_name'] . ' ' . $p['first_name']) ?></td>
                    <td>
                        <?= !empty($p['partner_last']) ? View::e($p['partner_last']) : '<span class="text-muted small">—</span>' ?>
                    </td>
                    <td><?= View::e($p['style_name'] ?? $p['style_code']) ?></td>
                    <td class="text-center"><?= $p['performance_number'] !== null ? (int)$p['performance_number'] : '—' ?></td>
                    <td class="text-end font-monospace fw-bold">
                        <?= $p['total_score'] !== null ? number_format((float)$p['total_score'], 2) : '<span class="text-muted">—</span>' ?>
                    </td>
                    <td class="text-center"><?= $p['rank'] !== null ? (int)$p['rank'] : '—' ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#judgeModal-<?= (int)$p['id'] ?>">
                            <i class="bi bi-clipboard-check"></i> Sedzia
                        </button>
                    </td>
                </tr>

                <!-- Modal: judge score per performance -->
                <div class="modal fade" id="judgeModal-<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="<?= url('club/dance/performances/' . (int)$p['id'] . '/judge') ?>">
                                <?= csrf_field() ?>
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="bi bi-clipboard-check me-1"></i>Dodaj ocene sedziego</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label">Imie sedziego</label>
                                            <input type="text" name="judge_name" class="form-control" required maxlength="200">
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label">Technika</label>
                                            <input type="number" name="technique_score" class="form-control"
                                                   step="0.01" min="0" max="10" placeholder="0-10">
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label">Artyzm</label>
                                            <input type="number" name="artistry_score" class="form-control"
                                                   step="0.01" min="0" max="10" placeholder="0-10">
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label">Trudnosc</label>
                                            <input type="number" name="difficulty_score" class="form-control"
                                                   step="0.01" min="0" max="10" placeholder="0-10">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Uwagi</label>
                                            <textarea name="notes" class="form-control" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                                    <button class="btn btn-success"><i class="bi bi-check"></i> Zapisz</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="perfModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('club/dance/performances/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-trophy me-1"></i>Dodaj wystep</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Turniej</label>
                            <select name="tournament_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($tournaments as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>">
                                        <?= View::e($t['name'] . ' (' . $t['date_start'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($tournaments)): ?>
                                <small class="text-danger">Brak turniejow. Utworz turniej w panelu turniejow.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Zawodnik</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Partner (opcjonalnie)</label>
                            <select name="partner_member_id" class="form-select">
                                <option value="">— brak —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-8">
                            <label class="form-label">Styl</label>
                            <select name="style_code" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($styles as $s): ?>
                                    <option value="<?= View::e($s['style_code']) ?>"><?= View::e($s['display_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label">Numer wystepu</label>
                            <input type="number" name="performance_number" class="form-control" min="1" max="999">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button class="btn btn-success"><i class="bi bi-plus-circle"></i> Dodaj</button>
                </div>
            </form>
        </div>
    </div>
</div>
