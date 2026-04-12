<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div></div>
    <a href="<?= url('club/webhooks/create') ?>" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Nowy webhook
    </a>
</div>

<!-- Endpointy -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-link-45deg"></i> Endpointy</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>URL</th>
                    <th>Eventy</th>
                    <th>Aktywny</th>
                    <th>Utworzono</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($endpoints)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Brak skonfigurowanych webhookow.</td></tr>
                <?php else: ?>
                    <?php foreach ($endpoints as $ep): ?>
                        <tr>
                            <td><?= (int)$ep['id'] ?></td>
                            <td>
                                <code class="small"><?= View::e($ep['url']) ?></code>
                            </td>
                            <td>
                                <?php
                                $events = json_decode($ep['events'] ?? '[]', true) ?: [];
                                foreach ($events as $ev): ?>
                                    <span class="badge bg-secondary me-1"><?= View::e($ev) ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php if ($ep['is_active']): ?>
                                    <span class="badge bg-success">Tak</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Nie</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= format_datetime($ep['created_at']) ?></small></td>
                            <td class="text-end">
                                <form method="POST" action="<?= url('club/webhooks/' . (int)$ep['id'] . '/delete') ?>"
                                      onsubmit="return confirm('Usunac webhook?')">
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
</div>

<!-- Ostatnie logi -->
<div class="card">
    <div class="card-header"><i class="bi bi-clock-history"></i> Ostatnie logi webhookow</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Endpoint</th>
                    <th>Event</th>
                    <th>HTTP</th>
                    <th>Odpowiedz</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Brak logow.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><small><?= format_datetime($log['sent_at']) ?></small></td>
                            <td><code class="small"><?= View::e($log['endpoint_url'] ?? '') ?></code></td>
                            <td><span class="badge bg-info"><?= View::e($log['event']) ?></span></td>
                            <td>
                                <?php
                                $code = (int)$log['response_code'];
                                $cls  = $code >= 200 && $code < 300 ? 'success' : ($code === 0 ? 'secondary' : 'danger');
                                ?>
                                <span class="badge bg-<?= $cls ?>"><?= $code ?></span>
                            </td>
                            <td>
                                <small class="text-muted"><?= View::e(mb_substr($log['response_body'] ?? '', 0, 80)) ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
