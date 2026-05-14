<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-list-columns text-primary me-2"></i>Drogi klubowe — Wspinaczka</h4>
    <div class="d-flex gap-2">
        <a href="?<?= $includeRetired ? '' : 'retired=1' ?>" class="btn btn-outline-secondary btn-sm">
            <?= $includeRetired ? 'Ukryj zdjęte' : 'Pokaż zdjęte' ?>
        </a>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#routeModal">
            <i class="bi bi-plus-circle"></i> Nowa droga
        </button>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nazwa</th><th>Typ</th><th>Stopień FR</th><th>Stopień V</th>
                    <th>Ściana</th><th>Kolor</th><th>Setter</th><th>Data</th>
                    <th class="text-center">Przejścia</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($routes)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Brak dróg.</td></tr>
            <?php else: foreach ($routes as $r):
                $ti = $types[$r['type']] ?? ['label' => $r['type'], 'class' => 'secondary'];
            ?>
                <tr class="<?= $r['retired'] ? 'text-muted bg-light' : '' ?>">
                    <td>
                        <strong><?= View::e($r['name']) ?></strong>
                        <?php if ($r['retired']): ?><span class="badge bg-secondary ms-1">Zdjęta</span><?php endif; ?>
                    </td>
                    <td><span class="badge bg-<?= $ti['class'] ?>"><?= View::e($ti['label']) ?></span></td>
                    <td class="font-monospace"><?= View::e($r['grade_french'] ?? '—') ?></td>
                    <td class="font-monospace"><?= View::e($r['grade_v'] ?? '—') ?></td>
                    <td class="small"><?= View::e($r['wall_name'] ?? '—') ?></td>
                    <td>
                        <?php if ($r['color']): ?>
                            <span class="d-inline-block" style="width:20px;height:20px;background:<?= View::e($r['color']) ?>;border:1px solid #333;vertical-align:middle;"></span>
                            <small class="text-muted"><?= View::e($r['color']) ?></small>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="small"><?= View::e($r['set_by'] ?? '—') ?></td>
                    <td class="small text-muted"><?= View::e($r['set_date'] ?? '—') ?></td>
                    <td class="text-center">
                        <span class="badge bg-success"><?= (int)$r['send_count'] ?></span>
                    </td>
                    <td class="d-flex gap-1">
                        <?php if (!$r['retired']): ?>
                            <form method="POST" action="<?= url('climbing/routes/' . (int)$r['id'] . '/retire') ?>" onsubmit="return confirm('Zdjąć drogę?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-warning" title="Zdejmij"><i class="bi bi-arrow-down-square"></i></button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="<?= url('climbing/routes/' . (int)$r['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="routeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('climbing/routes/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Nowa droga</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-8">
                            <label class="form-label">Nazwa drogi</label>
                            <input type="text" name="name" class="form-control" required placeholder="np. Blue Moon">
                        </div>
                        <div class="col-4">
                            <label class="form-label">Typ</label>
                            <select name="type" class="form-select">
                                <?php foreach ($types as $k => $t): ?>
                                    <option value="<?= $k ?>"><?= View::e($t['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Stopień FR (prowadzenie/TR)</label>
                            <select name="grade_french" class="form-select">
                                <option value="">—</option>
                                <?php foreach ($frenchGrades as $g): ?>
                                    <option value="<?= View::e($g) ?>"><?= View::e($g) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Stopień V (buldering)</label>
                            <select name="grade_v" class="form-select">
                                <option value="">—</option>
                                <?php foreach ($vGrades as $g): ?>
                                    <option value="<?= View::e($g) ?>"><?= View::e($g) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Ściana</label>
                            <input type="text" name="wall_name" class="form-control" placeholder="np. Overhang, Slab">
                        </div>
                        <div class="col-3">
                            <label class="form-label">Kolor chwytów</label>
                            <input type="text" name="color" class="form-control" placeholder="np. czerwony">
                        </div>
                        <div class="col-3">
                            <label class="form-label">Data ustawienia</label>
                            <input type="date" name="set_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Setter (kto ustawił)</label>
                            <input type="text" name="set_by" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Uwagi</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Dodaj drogę</button>
                </div>
            </form>
        </div>
    </div>
</div>
