<?php use App\Helpers\View; ?>
<div class="row">
    <!-- Facility list -->
    <div class="col-lg-8">
        <?php if (empty($facilities)): ?>
            <div class="card p-4 text-center text-muted">Brak obiektów sportowych. Dodaj pierwszy obiekt poniżej.</div>
        <?php else: ?>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nazwa</th>
                                <th>Typ</th>
                                <th>Pojemność</th>
                                <th>Lokalizacja</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facilities as $f): ?>
                                <tr>
                                    <td><strong><?= View::e($f['name']) ?></strong></td>
                                    <td><span class="badge bg-secondary"><?= View::e($f['type']) ?></span></td>
                                    <td><?= $f['capacity'] ? (int)$f['capacity'] : '—' ?></td>
                                    <td><?= View::e($f['location'] ?? '—') ?></td>
                                    <td class="text-end">
                                        <a href="<?= url('bookings/calendar?facility=' . (int)$f['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-calendar-week"></i>
                                        </a>
                                        <form method="POST" action="<?= url('bookings/facilities/' . (int)$f['id'] . '/delete') ?>"
                                              onsubmit="return confirm('Usunąć obiekt?')" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Inline create form -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-plus-circle"></i> Dodaj obiekt</h6></div>
            <div class="card-body">
                <form method="POST" action="<?= url('bookings/facilities/store') ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Nazwa</label>
                        <input type="text" name="name" class="form-control" required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Typ</label>
                        <select name="type" class="form-select">
                            <?php foreach (['boisko','sala','hala','tor','strzelnica','basen','kort','inne'] as $t): ?>
                                <option value="<?= $t ?>"><?= View::e(ucfirst($t)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pojemność</label>
                        <input type="number" name="capacity" class="form-control" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lokalizacja</label>
                        <input type="text" name="location" class="form-control" maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opis</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-plus"></i> Dodaj</button>
                </form>
            </div>
        </div>
    </div>
</div>
