<?php use App\Helpers\View; ?>
<div class="alert alert-info small">
    <strong>REST API v1</strong> — endpointy: <code>/api/v1/members</code>, <code>/api/v1/events</code>,
    <code>/api/v1/payments</code>, <code>/api/v1/sports</code>. Uwierzytelnianie: nagłówek
    <code>Authorization: Bearer &lt;klucz&gt;</code>.
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card p-3">
            <h5>Istniejące klucze</h5>
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Nazwa</th><th>Prefix</th><th>Limit/min</th><th>Ostatnie użycie</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($keys)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">Brak kluczy.</td></tr>
                <?php else: foreach ($keys as $k): ?>
                    <tr>
                        <td><?= View::e($k['name']) ?></td>
                        <td><code><?= View::e($k['key_prefix']) ?>...</code></td>
                        <td><?= (int)$k['rate_limit'] ?></td>
                        <td><small><?= $k['last_used_at'] ? format_datetime($k['last_used_at']) : '—' ?></small></td>
                        <td><span class="badge bg-<?= $k['is_active'] ? 'success' : 'secondary' ?>"><?= $k['is_active'] ? 'aktywny' : 'wyłączony' ?></span></td>
                        <td class="text-end">
                            <?php if ($k['is_active']): ?>
                                <form method="POST" action="<?= url('club/api-keys/' . (int)$k['id'] . '/revoke') ?>" onsubmit="return confirm('Dezaktywować klucz?')" class="d-inline">
                                    <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card p-3">
            <h5>Wygeneruj nowy klucz</h5>
            <form method="POST" action="<?= url('club/api-keys/generate') ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label small">Nazwa (opis) *</label>
                    <input type="text" name="name" class="form-control" required placeholder="np. Strona WWW klubu">
                </div>
                <div class="mb-2">
                    <label class="form-label small">Limit żądań / minutę</label>
                    <input type="number" name="rate_limit" value="60" min="1" max="1000" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label small">Uprawnienia (scope)</label>
                    <?php foreach (['members:read','events:read','payments:read','sports:read'] as $s): ?>
                        <div class="form-check">
                            <input type="checkbox" name="scopes[]" value="<?= $s ?>" id="scope_<?= $s ?>" class="form-check-input" checked>
                            <label for="scope_<?= $s ?>" class="form-check-label"><?= $s ?></label>
                        </div>
                    <?php endforeach; ?>
                    <small class="text-muted">Puste = pełen dostęp do wszystkich endpointów read.</small>
                </div>
                <button class="btn btn-primary w-100"><i class="bi bi-key"></i> Wygeneruj klucz</button>
            </form>
        </div>
    </div>
</div>
