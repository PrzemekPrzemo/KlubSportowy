<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><i class="bi bi-plug"></i> Integracje</h1>
    <a href="<?= url('help/api/v2') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-book"></i> Dokumentacja API v2
    </a>
</div>

<?php if (!empty($plainToken)): ?>
    <div class="alert alert-warning">
        <strong>Nowy token API — pokazany TYLKO RAZ:</strong>
        <code class="d-block mt-2 p-2 bg-dark text-light"><?= View::e($plainToken) ?></code>
        Skopiuj go teraz. Po opuszczeniu tej strony nie bedzie juz dostepny (DB trzyma tylko SHA-256 hash).
    </div>
<?php endif; ?>

<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-webhooks" type="button">
            <i class="bi bi-broadcast"></i> Webhooki
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tokens" type="button">
            <i class="bi bi-key"></i> Tokeny API v2
        </button>
    </li>
</ul>

<div class="tab-content">

    <!-- ── WEBHOOKI ─────────────────────────────────────────── -->
    <div class="tab-pane fade show active" id="tab-webhooks">

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-plus-lg"></i> Nowy webhook</div>
            <div class="card-body">
                <form method="POST" action="<?= url('club/integrations/webhook/store') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Nazwa</label>
                            <input type="text" name="name" class="form-control" required maxlength="100"
                                   placeholder="np. Integracja CRM">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">URL docelowy</label>
                            <input type="url" name="target_url" class="form-control" required maxlength="500"
                                   placeholder="https://example.com/webhook">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Eventy do subskrypcji</label>
                        <div class="row">
                            <?php foreach ($availableEvents as $ev): ?>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="events[]"
                                               value="<?= View::e($ev) ?>" id="ev_<?= View::e($ev) ?>">
                                        <label class="form-check-label" for="ev_<?= View::e($ev) ?>">
                                            <code><?= View::e($ev) ?></code>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Dodaj webhook
                        </button>
                        <small class="text-muted ms-2">Secret zostanie wygenerowany automatycznie (64 hex).</small>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-broadcast"></i> Skonfigurowane webhooki</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th><th>Nazwa</th><th>URL</th><th>Eventy</th>
                            <th>Aktywny</th><th>Ostatni sukces</th><th>Bledy</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($webhooks)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Brak webhookow.</td></tr>
                        <?php else: foreach ($webhooks as $w): ?>
                            <?php $events = json_decode($w['event_types'] ?? '[]', true) ?: []; ?>
                            <tr>
                                <td><?= (int)$w['id'] ?></td>
                                <td><?= View::e($w['name']) ?></td>
                                <td><code class="small"><?= View::e($w['target_url']) ?></code></td>
                                <td>
                                    <?php foreach ($events as $ev): ?>
                                        <span class="badge bg-secondary me-1"><?= View::e($ev) ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php if ((int)$w['active'] === 1): ?>
                                        <span class="badge bg-success">Tak</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Nie</span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= View::e($w['last_success_at'] ?? '-') ?></small></td>
                                <td><span class="badge bg-warning"><?= (int)$w['failure_count'] ?></span></td>
                                <td class="text-end">
                                    <form method="POST" action="<?= url('club/integrations/webhook/' . (int)$w['id'] . '/test') ?>" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-info" title="Wyslij test event">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= url('club/integrations/webhook/' . (int)$w['id'] . '/delete') ?>"
                                          class="d-inline" onsubmit="return confirm('Usunac webhook?')">
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

        <div class="card">
            <div class="card-header"><i class="bi bi-list-ul"></i> Ostatnie dostawy (30)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Webhook</th><th>Event</th><th>Status</th><th>HTTP</th><th>Proby</th><th>Czas</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($deliveries)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">Brak dostaw.</td></tr>
                        <?php else: foreach ($deliveries as $d): ?>
                            <tr>
                                <td><?= (int)$d['id'] ?></td>
                                <td><small><?= View::e($d['subscription_name'] ?? '') ?></small></td>
                                <td><code class="small"><?= View::e($d['event_type']) ?></code></td>
                                <td>
                                    <?php
                                    $statusClass = match ($d['status']) {
                                        'delivered' => 'bg-success',
                                        'failed'    => 'bg-danger',
                                        'retrying'  => 'bg-warning',
                                        default     => 'bg-secondary',
                                    };
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= View::e($d['status']) ?></span>
                                </td>
                                <td><?= $d['http_status'] !== null ? (int)$d['http_status'] : '-' ?></td>
                                <td><?= (int)$d['attempts'] ?></td>
                                <td><small><?= View::e($d['created_at']) ?></small></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ── TOKENY API v2 ────────────────────────────────────── -->
    <div class="tab-pane fade" id="tab-tokens">

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-plus-lg"></i> Nowy token API v2</div>
            <div class="card-body">
                <form method="POST" action="<?= url('club/integrations/token/store') ?>">
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nazwa tokenu</label>
                            <input type="text" name="name" class="form-control" required maxlength="100"
                                   placeholder="np. Integracja BI / dashboard">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Wygasa (opcjonalnie)</label>
                            <input type="date" name="expires_at" class="form-control">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Scope-y</label>
                        <div class="row">
                            <?php foreach ($availableScopes as $sc): ?>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="scopes[]"
                                               value="<?= View::e($sc) ?>" id="sc_<?= View::e($sc) ?>">
                                        <label class="form-check-label" for="sc_<?= View::e($sc) ?>">
                                            <code><?= View::e($sc) ?></code>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Wygeneruj token
                        </button>
                        <small class="text-muted ms-2">Token bedzie pokazany RAZ. Skopiuj go do bezpiecznego miejsca.</small>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-key"></i> Tokeny API v2</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>ID</th><th>Nazwa</th><th>Scope-y</th><th>Utworzony</th><th>Ostatnio uzyty</th><th>Wygasa</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tokens)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4">Brak tokenow.</td></tr>
                        <?php else: foreach ($tokens as $t): ?>
                            <?php $tScopes = json_decode($t['scopes'] ?? '[]', true) ?: []; ?>
                            <tr>
                                <td><?= (int)$t['id'] ?></td>
                                <td><?= View::e($t['name']) ?></td>
                                <td>
                                    <?php foreach ($tScopes as $sc): ?>
                                        <span class="badge bg-info text-dark me-1"><?= View::e($sc) ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td><small><?= View::e($t['created_at']) ?></small></td>
                                <td><small><?= View::e($t['last_used_at'] ?? '-') ?></small></td>
                                <td><small><?= View::e($t['expires_at'] ?? 'never') ?></small></td>
                                <td>
                                    <?php if ($t['revoked_at'] !== null): ?>
                                        <span class="badge bg-danger">Uniewazniony</span>
                                    <?php elseif ($t['expires_at'] !== null && strtotime((string)$t['expires_at']) <= time()): ?>
                                        <span class="badge bg-warning">Wygasl</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Aktywny</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($t['revoked_at'] === null): ?>
                                        <form method="POST" action="<?= url('club/integrations/token/' . (int)$t['id'] . '/revoke') ?>"
                                              onsubmit="return confirm('Uniewaznic token?')">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x-circle"></i> Uniewaznij
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
