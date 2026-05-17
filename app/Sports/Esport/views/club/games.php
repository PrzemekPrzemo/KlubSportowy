<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-controller text-primary me-2"></i>E-sport — Katalog gier</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#gameModal">
        <i class="bi bi-plus-circle"></i> Dodaj wlasna gre
    </button>
</div>

<p class="text-muted small">
    Gry globalne (z katalogu) sa widoczne dla wszystkich klubow.
    Wlasne gry klubowe wystarczy dodac jednokrotnie — pozniej dostepne dla profili graczy i turniejow.
</p>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kod</th>
                    <th>Nazwa</th>
                    <th>Gatunek</th>
                    <th class="text-center">Druzyna</th>
                    <th>System rankingu</th>
                    <th>Domyslny format</th>
                    <th>Typ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($games)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Brak gier — dodaj pierwsza wlasna gre.</td></tr>
            <?php else: foreach ($games as $g): ?>
                <tr>
                    <td class="font-monospace small"><?= View::e($g['game_code']) ?></td>
                    <td><strong><?= View::e($g['display_name']) ?></strong></td>
                    <td><span class="badge bg-secondary"><?= View::e($genres[$g['genre']] ?? $g['genre']) ?></span></td>
                    <td class="text-center"><?= (int)$g['team_size'] ?></td>
                    <td class="small"><?= View::e($rankingSystems[$g['ranking_system']] ?? $g['ranking_system']) ?></td>
                    <td class="small"><?= View::e($formats[$g['default_format']] ?? $g['default_format']) ?></td>
                    <td>
                        <?php if ($g['club_id'] === null): ?>
                            <span class="badge bg-info text-dark">Globalna</span>
                        <?php else: ?>
                            <span class="badge bg-primary">Klubowa</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($g['club_id'] !== null): ?>
                            <form method="POST" action="<?= url('club/esport/games/' . (int)$g['id'] . '/deactivate') ?>"
                                  onsubmit="return confirm('Dezaktywowac gre?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="gameModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('club/esport/games/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-controller me-1"></i>Dodaj gre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Nazwa wyswietlana</label>
                            <input type="text" name="display_name" class="form-control" required maxlength="200">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Kod (a-z, 0-9, _)</label>
                            <input type="text" name="game_code" class="form-control font-monospace"
                                   required pattern="[a-z0-9_]{2,50}" placeholder="np. minecraft">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Wielkosc druzyny</label>
                            <input type="number" name="team_size" class="form-control" min="1" max="20" value="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Gatunek</label>
                            <select name="genre" class="form-select">
                                <?php foreach ($genres as $k => $label): ?>
                                    <option value="<?= View::e($k) ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">System rankingu</label>
                            <select name="ranking_system" class="form-select">
                                <?php foreach ($rankingSystems as $k => $label): ?>
                                    <option value="<?= View::e($k) ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Domyslny format turnieju</label>
                            <select name="default_format" class="form-select">
                                <?php foreach ($formats as $k => $label): ?>
                                    <option value="<?= View::e($k) ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
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
