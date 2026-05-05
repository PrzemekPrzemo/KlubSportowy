<?php
use App\Helpers\View;
use App\Sports\XcSki\Models\XcSkiResultModel;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-stopwatch text-primary me-2"></i>Wyniki — Narciarstwo biegowe</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="<?= url('xcski/results') ?>" class="btn btn-sm btn-<?= !$techFilter ? 'primary' : 'outline-secondary' ?>">Wszystkie</a>
    <?php foreach ($techniques as $k => $v): ?>
        <a href="?technique=<?= urlencode($k) ?>" class="btn btn-sm btn-<?= $techFilter === $k ? 'primary' : 'outline-secondary' ?>"><?= View::e($v) ?></a>
    <?php endforeach; ?>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Data</th><th>Zawodnik</th><th>Styl</th><th>Dystans</th><th>Zawody</th><th>Czas</th><th>#</th><th>FIS pkt</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">Brak wyników.</td></tr>
            <?php else: foreach ($results as $r): ?>
                <tr class="<?= $r['dnf'] || $r['dns'] ? 'table-warning' : '' ?>">
                    <td class="small"><?= View::e($r['event_date']) ?></td>
                    <td><strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong></td>
                    <td><span class="badge bg-secondary"><?= View::e($techniques[$r['technique']] ?? $r['technique']) ?></span></td>
                    <td class="font-monospace"><?= number_format((float)$r['distance_km'], 1) ?> km</td>
                    <td class="small"><?= View::e($r['event_name']) ?></td>
                    <td class="font-monospace fw-bold">
                        <?php if ($r['dnf']): ?><span class="badge bg-danger">DNF</span>
                        <?php elseif ($r['dns']): ?><span class="badge bg-secondary">DNS</span>
                        <?php else: ?><?= XcSkiResultModel::formatTime($r['time_s']) ?><?php endif; ?>
                    </td>
                    <td><?php if ($r['place']): ?><span class="badge bg-primary">#<?= (int)$r['place'] ?></span><?php endif; ?></td>
                    <td class="small"><?= $r['fis_points'] !== null ? number_format((float)$r['fis_points'], 2) : '—' ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= url('xcski/results/' . (int)$r['id']) ?>"
                               class="btn btn-sm btn-outline-secondary" title="Szczegóły">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= url('xcski/results/' . (int)$r['id'] . '/edit') ?>"
                               class="btn btn-sm btn-outline-primary" title="Edytuj">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="<?= url('xcski/results/' . (int)$r['id'] . '/delete') ?>"
                                  onsubmit="return confirm('Usunąć?')" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" title="Usuń">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="resModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('xcski/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj wynik biegowy</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Zawodnik</label>
                            <select name="member_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $mm): ?>
                                    <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Styl / technika</label>
                            <select name="technique" class="form-select">
                                <?php foreach ($techniques as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Nazwa zawodów</label>
                            <input type="text" name="event_name" class="form-control" required>
                        </div>
                        <div class="col-6"><label class="form-label">Data</label>
                            <input type="date" name="event_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-4"><label class="form-label">Dystans (km)</label>
                            <input type="number" step="0.1" name="distance_km" class="form-control" required>
                        </div>
                        <div class="col-4"><label class="form-label">Czas (sekundy)</label>
                            <input type="number" name="time_s" class="form-control" min="0">
                        </div>
                        <div class="col-4"><label class="form-label">Miejsce</label>
                            <input type="number" name="place" class="form-control" min="1">
                        </div>
                        <div class="col-4"><label class="form-label">Venue</label>
                            <input type="text" name="venue" class="form-control">
                        </div>
                        <div class="col-4"><label class="form-label">Kategoria</label>
                            <input type="text" name="category" class="form-control">
                        </div>
                        <div class="col-4"><label class="form-label">FIS points</label>
                            <input type="number" step="0.01" name="fis_points" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-check-inline"><input type="checkbox" name="dnf" id="xdnf" class="form-check-input"><label for="xdnf" class="form-check-label">DNF</label></div>
                            <div class="form-check form-check-inline"><input type="checkbox" name="dns" id="xdns" class="form-check-input"><label for="xdns" class="form-check-label">DNS</label></div>
                        </div>
                        <div class="col-12"><label class="form-label">Uwagi</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
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
