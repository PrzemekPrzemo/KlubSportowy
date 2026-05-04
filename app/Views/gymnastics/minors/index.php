<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Zgody rodziców / opiekunów — Gimnastyka</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#consentModal">
        <i class="bi bi-plus-circle"></i> Dodaj / edytuj zgodę
    </button>
</div>

<?php
$missing = array_filter($consents, fn($c) => !$c['photo_consent'] || !$c['media_consent']);
if (!empty($missing)): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong><?= count($missing) ?></strong> zawodników ma niekompletne zgody rodzicielskie.
</div>
<?php endif; ?>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Zawodnik</th><th>Opiekun</th><th>Telefon</th>
                <th class="text-center">Zdjęcia</th><th class="text-center">Media</th>
                <th>Data podpisu</th><th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($consents)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Brak wpisów.</td></tr>
        <?php else: ?>
            <?php foreach ($consents as $c): ?>
            <tr>
                <td><strong><?= View::e($c['last_name']) ?> <?= View::e($c['first_name']) ?></strong>
                    <small class="text-muted d-block"><?= View::e($c['member_number']) ?></small></td>
                <td><?= View::e($c['guardian_name']) ?></td>
                <td><?= View::e($c['guardian_phone'] ?? '—') ?></td>
                <td class="text-center">
                    <i class="bi bi-<?= $c['photo_consent'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' ?>"></i>
                </td>
                <td class="text-center">
                    <i class="bi bi-<?= $c['media_consent'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger' ?>"></i>
                </td>
                <td><?= View::e($c['signed_date'] ?? '—') ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#consentModal"
                            data-mid="<?= (int)$c['member_id'] ?>"
                            data-gname="<?= View::e($c['guardian_name']) ?>"
                            data-gphone="<?= View::e($c['guardian_phone'] ?? '') ?>"
                            data-photo="<?= $c['photo_consent'] ?>"
                            data-media="<?= $c['media_consent'] ?>"
                            data-sdate="<?= View::e($c['signed_date'] ?? '') ?>"
                            data-notes="<?= View::e($c['notes'] ?? '') ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal: Dodaj/edytuj zgodę -->
<div class="modal fade" id="consentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('gymnastics/minors/save') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-shield-check me-1"></i> Zgoda rodzica / opiekuna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawodnik</label>
                        <select name="member_id" id="modal-member" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label">Imię i nazwisko opiekuna</label>
                            <input type="text" name="guardian_name" id="modal-gname" class="form-control" required>
                        </div>
                        <div class="col-5">
                            <label class="form-label">Telefon opiekuna</label>
                            <input type="text" name="guardian_phone" id="modal-gphone" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="photo_consent" id="modal-photo" value="1">
                            <label class="form-check-label" for="modal-photo">Zgoda na fotografowanie</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="media_consent" id="modal-media" value="1">
                            <label class="form-check-label" for="modal-media">Zgoda na publikację w mediach</label>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Data podpisu</label>
                            <input type="date" name="signed_date" id="modal-sdate" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Uwagi</label>
                        <textarea name="notes" id="modal-notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('consentModal').addEventListener('show.bs.modal', function(e) {
    var btn = e.relatedTarget;
    if (!btn || !btn.dataset.mid) return;
    document.getElementById('modal-member').value = btn.dataset.mid;
    document.getElementById('modal-gname').value  = btn.dataset.gname;
    document.getElementById('modal-gphone').value = btn.dataset.gphone;
    document.getElementById('modal-photo').checked = btn.dataset.photo === '1';
    document.getElementById('modal-media').checked = btn.dataset.media === '1';
    document.getElementById('modal-sdate').value  = btn.dataset.sdate;
    document.getElementById('modal-notes').value  = btn.dataset.notes;
});
</script>
