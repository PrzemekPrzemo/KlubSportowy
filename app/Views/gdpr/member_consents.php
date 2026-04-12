<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <h5><?= View::e($member['first_name']) ?> <?= View::e($member['last_name']) ?> (#<?= View::e($member['member_number']) ?>)</h5>
    <?php if (!empty($member['anonymized_at'])): ?>
        <div class="alert alert-dark py-2"><i class="bi bi-shield-lock-fill"></i> Dane zanonimizowane: <?= format_datetime($member['anonymized_at']) ?></div>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card p-3">
            <h5>Zgody</h5>
            <table class="table mb-0">
                <thead class="table-light"><tr><th>Typ</th><th>Opis</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($types as $k => $desc):
                    $c = $consents[$k] ?? null;
                    $granted = $c && $c['granted'];
                ?>
                    <tr>
                        <td><code><?= View::e($k) ?></code></td>
                        <td><small><?= View::e($desc) ?></small></td>
                        <td><span class="badge bg-<?= $granted ? 'success' : 'secondary' ?>"><?= $granted ? 'udzielona' : 'brak' ?></span></td>
                        <td class="text-end">
                            <?php if ($granted): ?>
                                <form method="POST" action="<?= url('gdpr/member/' . (int)$member['id'] . '/revoke') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="consent_type" value="<?= View::e($k) ?>">
                                    <button class="btn btn-sm btn-outline-danger">Cofnij</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="<?= url('gdpr/member/' . (int)$member['id'] . '/grant') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="consent_type" value="<?= View::e($k) ?>">
                                    <button class="btn btn-sm btn-outline-success">Udziel</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 mb-3">
            <h6><i class="bi bi-download"></i> Eksport danych (Art. 20)</h6>
            <p class="small text-muted">Pobierz pełny eksport danych zawodnika w formacie JSON.</p>
            <a href="<?= url('gdpr/member/' . (int)$member['id'] . '/export') ?>" class="btn btn-outline-primary w-100">
                <i class="bi bi-file-earmark-arrow-down"></i> Pobierz JSON
            </a>
        </div>
        <div class="card p-3 border-danger">
            <h6 class="text-danger"><i class="bi bi-exclamation-triangle"></i> Anonimizacja</h6>
            <p class="small text-muted">Nieodwracalne usunięcie danych osobowych. Imię, nazwisko, PESEL, e-mail, telefon, adres zostaną zastąpione przez ***.</p>
            <form method="POST" action="<?= url('gdpr/member/' . (int)$member['id'] . '/anonymize') ?>" onsubmit="return confirm('UWAGA: Tej operacji NIE MOŻNA cofnąć. Wszystkie dane osobowe zostaną trwale usunięte. Kontynuować?')">
                <?= csrf_field() ?>
                <button class="btn btn-danger w-100"><i class="bi bi-shield-lock-fill"></i> Anonimizuj dane</button>
            </form>
        </div>
    </div>
</div>
