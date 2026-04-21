<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-box-seam text-primary me-2"></i>Sprzęt klubowy</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#itemModal">
        <i class="bi bi-plus-circle"></i> Dodaj sprzęt
    </button>
</div>

<form method="GET" class="mb-3 d-flex gap-2 flex-wrap">
    <select name="sport" class="form-select form-select-sm" style="width:200px;">
        <option value="">Wszystkie sporty</option>
        <?php foreach ($sports as $s): ?>
            <option value="<?= View::e($s['key']) ?>" <?= $sportFilter === $s['key'] ? 'selected' : '' ?>>
                <?= View::e($s['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select name="state" class="form-select form-select-sm" style="width:160px;">
        <option value="">Wszystkie stany</option>
        <?php foreach ($states as $k => $s): ?>
            <option value="<?= $k ?>" <?= $stateFilter === $k ? 'selected' : '' ?>><?= View::e($s['label']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i></button>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nazwa</th><th>Sport</th><th>Kategoria</th><th>Marka/model</th>
                    <th>Rozmiar</th><th>Nr seryjny</th><th>Stan</th><th>Przypisany</th>
                    <th>Lokalizacja</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">Brak sprzętu.</td></tr>
            <?php else: foreach ($items as $it):
                $si = $states[$it['state']] ?? ['label' => $it['state'], 'class' => 'secondary'];
            ?>
                <tr>
                    <td>
                        <a href="<?= url('equipment/' . (int)$it['id']) ?>" class="text-decoration-none">
                            <strong><?= View::e($it['name']) ?></strong>
                        </a>
                    </td>
                    <td><small class="text-muted"><?= View::e($it['sport_key'] ?? '—') ?></small></td>
                    <td><span class="badge bg-light text-dark"><?= View::e($it['category']) ?></span></td>
                    <td class="small"><?= View::e(trim($it['brand'] . ' ' . $it['model'])) ?></td>
                    <td class="small"><?= View::e($it['size'] ?? '—') ?></td>
                    <td class="small font-monospace"><?= View::e($it['serial_number'] ?? '—') ?></td>
                    <td><span class="badge bg-<?= $si['class'] ?>"><?= View::e($si['label']) ?></span></td>
                    <td>
                        <?php if ($it['assigned_to']): ?>
                            <span class="badge bg-info"><i class="bi bi-person-fill"></i> <?= View::e($it['assigned_to']) ?></span>
                        <?php else: ?>
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Dostępny</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= View::e($it['location'] ?? '—') ?></td>
                    <td class="d-flex gap-1">
                        <a href="<?= url('equipment/' . (int)$it['id']) ?>" class="btn btn-sm btn-outline-primary" title="Szczegóły">
                            <i class="bi bi-eye"></i>
                        </a>
                        <form method="POST" action="<?= url('equipment/' . (int)$it['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('equipment/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Nowy sprzęt</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Nazwa</label><input type="text" name="name" class="form-control" required placeholder="np. Kij hokejowy CCM RBZ"></div>
                        <div class="col-6"><label class="form-label">Kategoria</label><input type="text" name="category" class="form-control" required placeholder="kij / rakieta / rower / linka"></div>
                        <div class="col-4"><label class="form-label">Sport (opcjonalny)</label>
                            <select name="sport_key" class="form-select">
                                <option value="">— ogólny —</option>
                                <?php foreach ($sports as $s): ?>
                                    <option value="<?= View::e($s['key']) ?>"><?= View::e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4"><label class="form-label">Marka</label><input type="text" name="brand" class="form-control"></div>
                        <div class="col-4"><label class="form-label">Model</label><input type="text" name="model" class="form-control"></div>
                        <div class="col-4"><label class="form-label">Rozmiar</label><input type="text" name="size" class="form-control" placeholder="np. L, 28cm, M50"></div>
                        <div class="col-4"><label class="form-label">Nr seryjny</label><input type="text" name="serial_number" class="form-control"></div>
                        <div class="col-4"><label class="form-label">Stan</label>
                            <select name="state" class="form-select">
                                <?php foreach ($states as $k => $s): ?>
                                    <option value="<?= $k ?>" <?= $k === 'dobry' ? 'selected' : '' ?>><?= View::e($s['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4"><label class="form-label">Data zakupu</label><input type="date" name="purchase_date" class="form-control"></div>
                        <div class="col-4"><label class="form-label">Cena zakupu (PLN)</label><input type="number" step="0.01" name="purchase_price" class="form-control"></div>
                        <div class="col-4"><label class="form-label">Lokalizacja</label><input type="text" name="location" class="form-control" placeholder="np. magazyn A / szafka 12"></div>
                        <div class="col-12"><label class="form-label">Uwagi</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Dodaj</button>
                </div>
            </form>
        </div>
    </div>
</div>
