<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Konie — Jeździectwo</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#horseModal">
        <i class="bi bi-plus-circle"></i> Dodaj konia
    </button>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Imię</th><th>Rasa</th><th>Rok ur.</th><th>Maść</th><th>Płeć</th><th>Nr paszportu</th><th>Właściciel</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($horses)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Brak koni w systemie.</td></tr>
        <?php else: ?>
            <?php foreach ($horses as $h): ?>
                <tr>
                    <td><strong><?= View::e($h['name']) ?></strong></td>
                    <td><?= View::e($h['breed'] ?? '—') ?></td>
                    <td><?= View::e($h['birth_year'] ?? '—') ?></td>
                    <td><?= View::e($h['color'] ?? '—') ?></td>
                    <td><?= View::e($sexOptions[$h['sex']] ?? $h['sex']) ?></td>
                    <td class="text-muted small"><?= View::e($h['passport_no'] ?? '—') ?></td>
                    <td><?= View::e($h['owner_name'] ?? '—') ?></td>
                    <td>
                        <?php $statusColors = ['active' => 'success', 'sick' => 'warning', 'retired' => 'secondary']; ?>
                        <span class="badge bg-<?= $statusColors[$h['status']] ?? 'secondary' ?>">
                            <?= ['active' => 'aktywny', 'sick' => 'chory', 'retired' => 'na emeryturze'][$h['status']] ?? $h['status'] ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="<?= url('equestrian/horses/'.(int)$h['id'].'/delete') ?>"
                              onsubmit="return confirm('Usunąć konia?')">
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

<!-- Modal: Dodaj konia -->
<div class="modal fade" id="horseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('equestrian/horses/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-compass me-1"></i> Dodaj konia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Imię konia *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Płeć</label>
                            <select name="sex" class="form-select">
                                <?php foreach ($sexOptions as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Rasa</label>
                            <input type="text" name="breed" class="form-control" placeholder="np. Warmblood, KWPN">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rok urodzenia</label>
                            <input type="number" name="birth_year" class="form-control" min="1980" max="<?= date('Y') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Maść</label>
                            <input type="text" name="color" class="form-control" placeholder="np. Gniada, Kara">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Paszport (legacy)</label>
                            <input type="text" name="passport_no" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Paszport PZJ</label>
                            <input type="text" name="pzj_passport_no" class="form-control" placeholder="np. PZJ-12345">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Paszport FEI</label>
                            <input type="text" name="fei_passport_no" class="form-control" placeholder="np. POL12345">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mikrochip ISO</label>
                            <input type="text" name="microchip" class="form-control" maxlength="20" placeholder="15-cyfrowy">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Wysokość w kłębie (cm)</label>
                            <input type="number" name="height_cm" class="form-control" min="80" max="200">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Klasa sportowa</label>
                            <select name="sport_class" class="form-select">
                                <?php foreach ($sportClasses as $k => $label): ?>
                                    <option value="<?= $k ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Dyscypliny (multi-select)</label>
                            <select name="discipline_focus[]" class="form-select" multiple size="3">
                                <?php foreach ($disciplines as $k => $label): ?>
                                    <option value="<?= $k ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small">Ctrl+klik dla wielu</div>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Właściciel (z rejestru)</label>
                            <select name="owner_id" class="form-select">
                                <option value="">— brak / wpisz ręcznie poniżej —</option>
                                <?php foreach ($owners as $oid => $oLabel): ?>
                                    <option value="<?= (int)$oid ?>"><?= View::e($oLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small">
                                <a href="<?= url('equestrian/owners') ?>">Zarządzaj rejestrem właścicieli</a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lub ad-hoc imię i nazwisko</label>
                            <input type="text" name="owner_name" class="form-control" placeholder="gdy właściciel nie jest w rejestrze">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle me-1"></i> Dodaj</button>
                </div>
            </form>
        </div>
    </div>
</div>
