<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-arrow-up-right-circle text-primary me-2"></i>Wyniki — Skoki narciarskie</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#resModal">
        <i class="bi bi-plus-circle"></i> Dodaj wynik
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Data</th><th>Zawodnik</th><th>Skocznia</th><th>K-point</th><th>Skok 1</th><th>Skok 2</th><th>Total pkt</th><th>#</th><th>FIS</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Brak wyników.</td></tr>
            <?php else: foreach ($results as $r): ?>
                <tr class="<?= $r['dnf'] || $r['dns'] ? 'table-warning' : '' ?>">
                    <td class="small"><?= View::e($r['event_date']) ?></td>
                    <td><strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong></td>
                    <td class="small"><?= View::e($r['venue'] ?? $r['event_name']) ?> <?php if ($r['hill_size']): ?><span class="badge bg-dark"><?= View::e($r['hill_size']) ?></span><?php endif; ?></td>
                    <td class="font-monospace"><?= $r['hill_k'] ? 'K' . (int)$r['hill_k'] : '—' ?></td>
                    <td class="font-monospace small">
                        <?php if ($r['jump1_m']): ?>
                            <strong><?= number_format((float)$r['jump1_m'], 1) ?>m</strong>
                            <?php if ($r['jump1_points']): ?><small class="text-muted d-block"><?= number_format((float)$r['jump1_points'], 1) ?> pkt</small><?php endif; ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="font-monospace small">
                        <?php if ($r['jump2_m']): ?>
                            <strong><?= number_format((float)$r['jump2_m'], 1) ?>m</strong>
                            <?php if ($r['jump2_points']): ?><small class="text-muted d-block"><?= number_format((float)$r['jump2_points'], 1) ?> pkt</small><?php endif; ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="font-monospace fw-bold"><?= $r['total_points'] !== null ? number_format((float)$r['total_points'], 2) : '—' ?></td>
                    <td><?php if ($r['place']): ?><span class="badge bg-primary">#<?= (int)$r['place'] ?></span><?php endif; ?></td>
                    <td class="small"><?= $r['fis_points'] !== null ? number_format((float)$r['fis_points'], 2) : '—' ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= url('skijump/results/' . (int)$r['id']) ?>"
                               class="btn btn-sm btn-outline-secondary" title="Szczegóły">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= url('skijump/results/' . (int)$r['id'] . '/edit') ?>"
                               class="btn btn-sm btn-outline-primary" title="Edytuj">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="<?= url('skijump/results/' . (int)$r['id'] . '/delete') ?>"
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
            <form method="POST" action="<?= url('skijump/results/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj wynik skoków</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
                        <div class="col-6"><label class="form-label">Data</label>
                            <input type="date" name="event_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12"><label class="form-label">Nazwa zawodów</label>
                            <input type="text" name="event_name" class="form-control" required placeholder="np. PŚ Zakopane 2025">
                        </div>
                        <div class="col-6"><label class="form-label">Skocznia (venue)</label>
                            <input type="text" name="venue" class="form-control" placeholder="np. Krokiew — Zakopane">
                        </div>
                        <div class="col-3"><label class="form-label">Punkt K</label>
                            <input type="number" name="hill_k" class="form-control" min="10" max="250" placeholder="125">
                        </div>
                        <div class="col-3"><label class="form-label">Rozmiar</label>
                            <input type="text" name="hill_size" class="form-control" placeholder="HS140">
                        </div>
                        <div class="col-3"><label class="form-label">Skok 1 (m)</label>
                            <input type="number" step="0.5" name="jump1_m" class="form-control">
                        </div>
                        <div class="col-3"><label class="form-label">Skok 1 (pkt)</label>
                            <input type="number" step="0.1" name="jump1_points" class="form-control">
                        </div>
                        <div class="col-3"><label class="form-label">Skok 2 (m)</label>
                            <input type="number" step="0.5" name="jump2_m" class="form-control">
                        </div>
                        <div class="col-3"><label class="form-label">Skok 2 (pkt)</label>
                            <input type="number" step="0.1" name="jump2_points" class="form-control">
                        </div>
                        <div class="col-4"><label class="form-label">Miejsce</label>
                            <input type="number" name="place" class="form-control" min="1">
                        </div>
                        <div class="col-4"><label class="form-label">Kategoria</label>
                            <input type="text" name="category" class="form-control">
                        </div>
                        <div class="col-4"><label class="form-label">FIS pkt</label>
                            <input type="number" step="0.01" name="fis_points" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-check-inline"><input type="checkbox" name="dnf" id="sjdnf" class="form-check-input"><label for="sjdnf" class="form-check-label">DNF</label></div>
                            <div class="form-check form-check-inline"><input type="checkbox" name="dns" id="sjdns" class="form-check-input"><label for="sjdns" class="form-check-label">DNS</label></div>
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
