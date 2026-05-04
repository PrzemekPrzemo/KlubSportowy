<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-telephone-fill text-danger me-2"></i>Kontakty w razie wypadku</h3>
        <p class="text-muted mb-0">Twoja lista kontaktów bezpieczeństwa w klubie</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if (empty($contacts)): ?>
    <div class="alert alert-danger mb-3">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <strong>Brak zapisanych kontaktów!</strong> Dodaj natychmiast — jest to wymagane dla Twojego bezpieczeństwa.
    </div>
<?php endif; ?>

<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#contactModal">
        <i class="bi bi-plus-circle"></i> Dodaj kontakt
    </button>
</div>

<?php foreach ($contacts as $c): ?>
    <div class="card shadow-sm mb-3 <?= $c['is_primary'] ? 'border-success' : '' ?>">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">
                        <?= View::e($c['contact_name']) ?>
                        <?php if ($c['is_primary']): ?>
                            <span class="badge bg-success ms-2"><i class="bi bi-star-fill"></i> Główny</span>
                        <?php endif; ?>
                    </h5>
                    <div class="text-muted small mb-2">
                        <span class="badge bg-secondary"><?= View::e($relationships[$c['relationship']] ?? $c['relationship']) ?></span>
                    </div>
                    <div>
                        <i class="bi bi-telephone text-primary"></i>
                        <a href="tel:<?= View::e($c['phone']) ?>" class="font-monospace text-decoration-none me-3"><?= View::e($c['phone']) ?></a>
                        <?php if ($c['phone_alt']): ?>
                            <span class="text-muted me-3">| <?= View::e($c['phone_alt']) ?></span>
                        <?php endif; ?>
                        <?php if ($c['email']): ?>
                            <i class="bi bi-envelope text-muted ms-2"></i> <?= View::e($c['email']) ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($c['notes']): ?>
                        <div class="small text-muted mt-2"><?= View::e($c['notes']) ?></div>
                    <?php endif; ?>
                </div>
                <form method="POST" action="<?= url('portal/emergency-contacts/' . (int)$c['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć kontakt?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- Modal -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('portal/emergency-contacts/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Nowy kontakt awaryjny</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Imię i nazwisko kontaktu</label>
                        <input type="text" name="contact_name" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Pokrewieństwo</label>
                            <select name="relationship" class="form-select">
                                <?php foreach ($relationships as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= View::e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Telefon</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Telefon 2</label>
                            <input type="tel" name="phone_alt" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Uwagi</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_primary" id="primChk" class="form-check-input">
                        <label class="form-check-label" for="primChk">Kontakt główny</label>
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
