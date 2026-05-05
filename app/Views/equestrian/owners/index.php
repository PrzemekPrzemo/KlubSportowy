<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-person-vcard me-2"></i>Właściciele koni</h3>
    <button class="btn btn-sm btn-success" type="button" data-bs-toggle="collapse" data-bs-target="#ownerForm">
        <i class="bi bi-plus-circle me-1"></i> Dodaj właściciela
    </button>
</div>

<div class="alert alert-info small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    Konie należą do właścicieli, którzy mogą być członkami klubu lub osobami zewnętrznymi
    (hodowcy, stowarzyszenia, sponsorzy). PZJ wymaga rejestru właścicieli dla każdego konia
    startującego w zawodach.
</div>

<div id="ownerForm" class="collapse mb-3">
    <div class="card p-3">
        <form method="POST" action="<?= url('equestrian/owners/store') ?>" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-md-6">
                <label class="form-label">Pełne imię i nazwisko *</label>
                <input type="text" name="full_name" class="form-control" required maxlength="150">
            </div>
            <div class="col-md-3">
                <label class="form-label">NIP / PESEL</label>
                <input type="text" name="tax_id" class="form-control" maxlength="20">
            </div>
            <div class="col-md-3">
                <label class="form-label">Telefon</label>
                <input type="text" name="phone" class="form-control" maxlength="30">
            </div>
            <div class="col-md-6">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control" maxlength="120">
            </div>
            <div class="col-md-3">
                <label class="form-label">Miasto</label>
                <input type="text" name="city" class="form-control" maxlength="100">
            </div>
            <div class="col-md-3">
                <label class="form-label">Adres</label>
                <input type="text" name="address" class="form-control" maxlength="255">
            </div>
            <div class="col-12">
                <label class="form-label">Notatki</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-12 text-end">
                <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Imię i nazwisko</th>
                <th>Typ</th>
                <th>Telefon</th>
                <th>E-mail</th>
                <th>Miasto</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($owners)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">
                    Brak właścicieli. Dodaj pierwszego klikając przycisk powyżej.
                </td></tr>
            <?php else: foreach ($owners as $o): ?>
                <tr>
                    <td><strong><?= View::e($o['full_name']) ?></strong></td>
                    <td>
                        <?php if (!empty($o['member_id'])): ?>
                            <span class="badge bg-primary">Członek klubu</span>
                            <small class="text-muted">
                                <?= View::e($o['member_first_name'] ?? '') ?> <?= View::e($o['member_last_name'] ?? '') ?>
                            </small>
                        <?php else: ?>
                            <span class="badge bg-secondary">Zewnętrzny</span>
                        <?php endif; ?>
                    </td>
                    <td><?= View::e($o['phone'] ?? '—') ?></td>
                    <td><?= View::e($o['email'] ?? '—') ?></td>
                    <td><?= View::e($o['city'] ?? '—') ?></td>
                    <td class="text-end">
                        <form method="POST" action="<?= url('equestrian/owners/' . (int)$o['id'] . '/delete') ?>"
                              onsubmit="return confirm('Usunąć właściciela?')" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
