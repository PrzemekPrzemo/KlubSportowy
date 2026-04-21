<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-telephone-fill text-danger me-2"></i>Kontakty w razie wypadku</h4>
        <small class="text-muted">
            <?= View::e($member['last_name'] . ' ' . $member['first_name']) ?>
            <span class="badge bg-light text-dark ms-1">#<?= View::e($member['member_number']) ?></span>
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('members/' . (int)$member['id']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Profil
        </a>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#contactModal">
            <i class="bi bi-plus-circle"></i> Dodaj kontakt
        </button>
    </div>
</div>

<div class="alert alert-warning small mb-3">
    <i class="bi bi-exclamation-triangle me-1"></i>
    <strong>Wymóg bezpieczeństwa:</strong> kontakt w razie wypadku jest niezbędny dla sportów kontaktowych
    (boks, BJJ, hokej, taekwondo, piłka ręczna, szermierka, wspinaczka). Zalecamy minimum 2 kontakty.
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Imię i nazwisko</th><th>Pokrewieństwo</th><th>Telefon</th>
                    <th>Telefon 2</th><th>Email</th><th>Status</th><th>Uwagi</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($contacts)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">
                    <i class="bi bi-exclamation-triangle text-warning"></i>
                    Brak kontaktów awaryjnych. <strong>Dodaj natychmiast</strong>.
                </td></tr>
            <?php else: foreach ($contacts as $c): ?>
                <tr class="<?= $c['is_primary'] ? 'table-success' : '' ?>">
                    <td>
                        <strong><?= View::e($c['contact_name']) ?></strong>
                        <?php if ($c['is_primary']): ?>
                            <span class="badge bg-success ms-1"><i class="bi bi-star-fill"></i> Główny</span>
                        <?php endif; ?>
                    </td>
                    <td><small><?= View::e($relationships[$c['relationship']] ?? $c['relationship']) ?></small></td>
                    <td>
                        <a href="tel:<?= View::e($c['phone']) ?>" class="text-decoration-none font-monospace">
                            <i class="bi bi-telephone"></i> <?= View::e($c['phone']) ?>
                        </a>
                    </td>
                    <td class="font-monospace small"><?= View::e($c['phone_alt'] ?? '—') ?></td>
                    <td class="small">
                        <?php if ($c['email']): ?>
                            <a href="mailto:<?= View::e($c['email']) ?>" class="text-decoration-none">
                                <?= View::e($c['email']) ?>
                            </a>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$c['is_primary']): ?>
                            <form method="POST" action="<?= url('members/' . (int)$member['id'] . '/emergency-contacts/' . (int)$c['id'] . '/primary') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-primary" title="Ustaw jako główny">
                                    <i class="bi bi-star"></i> Ustaw
                                </button>
                            </form>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="small"><?= View::e($c['notes'] ?? '') ?></td>
                    <td>
                        <form method="POST" action="<?= url('members/' . (int)$member['id'] . '/emergency-contacts/' . (int)$c['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć kontakt?')">
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

<!-- Modal -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('members/' . (int)$member['id'] . '/emergency-contacts/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Nowy kontakt awaryjny</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Imię i nazwisko</label>
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
                            <label class="form-label">Telefon główny</label>
                            <input type="tel" name="phone" class="form-control" required placeholder="+48 ...">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Telefon dodatkowy</label>
                            <input type="tel" name="phone_alt" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Uwagi (np. godziny dostępności)</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_primary" id="primChk" class="form-check-input">
                        <label class="form-check-label" for="primChk">Kontakt główny (priorytet w razie wypadku)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
