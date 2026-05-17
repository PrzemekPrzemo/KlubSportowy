<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-controller text-primary me-2"></i>Moje gry esportowe</h4>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#profileModal">
        <i class="bi bi-plus-circle"></i> Dodaj/edytuj gre
    </button>
</div>

<?php if (empty($profiles)): ?>
    <div class="alert alert-info">
        Jeszcze nie dodales zadnej gry. Kliknij <strong>Dodaj/edytuj gre</strong> aby zaczac.
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($profiles as $p): ?>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title mb-1">
                                <?= View::e($p['game_display_name'] ?? $p['game_code']) ?>
                            </h5>
                            <span class="badge bg-secondary"><?= View::e($platforms[$p['platform']] ?? $p['platform']) ?></span>
                        </div>
                        <p class="text-muted small mb-2 font-monospace"><?= View::e($p['in_game_name']) ?></p>

                        <div class="row text-center">
                            <div class="col-4">
                                <div class="small text-muted">ELO</div>
                                <strong class="fs-5"><?= (int)$p['elo_rating'] ?></strong>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">Ranga</div>
                                <strong><?= View::e($p['rank_tier'] ?? '—') ?></strong>
                            </div>
                            <div class="col-4">
                                <div class="small text-muted">W / P</div>
                                <strong>
                                    <span class="text-success"><?= (int)$p['wins'] ?></span>
                                    /
                                    <span class="text-danger"><?= (int)$p['losses'] ?></span>
                                </strong>
                            </div>
                        </div>

                        <?php if (!empty($p['stream_url'])): ?>
                            <a href="<?= View::e($p['stream_url']) ?>" target="_blank" rel="noopener"
                               class="btn btn-sm btn-outline-purple mt-3 w-100" style="color:#6f42c1;border-color:#6f42c1;">
                                <i class="bi bi-broadcast"></i> Stream
                            </a>
                        <?php endif; ?>

                        <a href="<?= url('portal/esport/leaderboard/' . urlencode($p['game_code'])) ?>"
                           class="btn btn-sm btn-outline-warning mt-2 w-100">
                            <i class="bi bi-trophy"></i> Leaderboard
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('portal/esport/profiles/save') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-controller me-1"></i>Dodaj/edytuj gre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Gra</label>
                            <select name="game_code" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($games as $g): ?>
                                    <option value="<?= View::e($g['game_code']) ?>">
                                        <?= View::e($g['display_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Jesli edytujesz juz dodana gre — wybierz ja ponownie aby zaktualizowac dane.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nick w grze (IGN)</label>
                            <input type="text" name="in_game_name" class="form-control" required maxlength="200">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Platforma</label>
                            <select name="platform" class="form-select">
                                <?php foreach ($platforms as $k => $label): ?>
                                    <option value="<?= View::e($k) ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Ranga (opcjonalnie)</label>
                            <input type="text" name="rank_tier" class="form-control" maxlength="50"
                                   placeholder="np. Diamond II">
                        </div>
                        <div class="col-12">
                            <label class="form-label">URL streamingu (Twitch/YouTube)</label>
                            <input type="url" name="stream_url" class="form-control"
                                   placeholder="https://twitch.tv/twoj_kanal">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button class="btn btn-success"><i class="bi bi-check-circle"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
