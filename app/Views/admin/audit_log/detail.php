<?php
use App\Helpers\View;

/** @var string $source */
/** @var array  $entry */
/** @var array  $related */
/** @var ?int   $clubId */

$sourceLabel = [
    'activity_log'     => 'Activity log',
    'sensitive_access' => 'Dostęp do danych wrażliwych',
    'tenant_access'    => 'Cross-tenant access',
][$source] ?? $source;

$when = $entry['created_at'] ?? $entry['occurred_at'] ?? null;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Szczegóły zdarzenia audytowego</h4>
        <small class="text-muted">
            <span class="badge bg-secondary"><?= View::e($source) ?></span>
            #<?= (int)$entry['id'] ?> · <?= View::e($sourceLabel) ?>
        </small>
    </div>
    <div>
        <a href="<?= url('admin/audit-log') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Powrót
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card p-3">
            <h6 class="mb-3">Główne dane</h6>
            <dl class="row mb-0">
                <dt class="col-sm-4">Kiedy</dt>
                <dd class="col-sm-8"><code><?= View::e($when ?? '—') ?></code></dd>

                <dt class="col-sm-4">Użytkownik</dt>
                <dd class="col-sm-8">
                    <?php if (!empty($entry['user_id'])): ?>
                        <a href="<?= url('admin/users/' . (int)$entry['user_id']) ?>">
                            <?= View::e($entry['username'] ?? ('#' . (int)$entry['user_id'])) ?>
                        </a>
                        (ID #<?= (int)$entry['user_id'] ?>)
                    <?php else: ?>
                        <span class="text-muted">— anonim/system —</span>
                    <?php endif; ?>
                </dd>

                <dt class="col-sm-4">Klub</dt>
                <dd class="col-sm-8">
                    <?php
                    $entryClub = $entry['club_id'] ?? $entry['active_club_id'] ?? null;
                    if ($entryClub !== null): ?>
                        <?= View::e($entry['club_name'] ?? '#' . (int)$entryClub) ?>
                    <?php else: ?>
                        <span class="text-muted">brak / cross-club</span>
                    <?php endif; ?>
                </dd>

                <?php if ($source === 'activity_log'): ?>
                    <dt class="col-sm-4">Akcja</dt>
                    <dd class="col-sm-8"><code><?= View::e($entry['action']) ?></code></dd>
                    <dt class="col-sm-4">Encja</dt>
                    <dd class="col-sm-8">
                        <?= View::e($entry['entity'] ?? '—') ?>
                        <?php if (!empty($entry['entity_id'])): ?>
                            <span class="text-muted">#<?= (int)$entry['entity_id'] ?></span>
                        <?php endif; ?>
                    </dd>
                    <dt class="col-sm-4">Szczegóły</dt>
                    <dd class="col-sm-8"><?= View::e($entry['details'] ?? '—') ?></dd>
                    <dt class="col-sm-4">IP</dt>
                    <dd class="col-sm-8"><code><?= View::e($entry['ip_address'] ?? '—') ?></code></dd>
                <?php elseif ($source === 'sensitive_access'): ?>
                    <dt class="col-sm-4">Typ danych</dt>
                    <dd class="col-sm-8"><code><?= View::e($entry['data_type']) ?></code></dd>
                    <dt class="col-sm-4">Akcja</dt>
                    <dd class="col-sm-8"><code><?= View::e($entry['action']) ?></code></dd>
                    <dt class="col-sm-4">Członek</dt>
                    <dd class="col-sm-8">
                        <?php if (!empty($entry['member_id'])): ?>
                            <a href="<?= url('members/' . (int)$entry['member_id']) ?>">
                                <?= View::e(trim((string)($entry['member_name'] ?? ''))) ?: ('#' . (int)$entry['member_id']) ?>
                            </a>
                            <?php if (!empty($entry['member_number'])): ?>
                                <small class="text-muted">(<?= View::e($entry['member_number']) ?>)</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </dd>
                    <dt class="col-sm-4">Kontekst</dt>
                    <dd class="col-sm-8"><code class="small"><?= View::e($entry['context'] ?? '—') ?></code></dd>
                    <dt class="col-sm-4">IP</dt>
                    <dd class="col-sm-8"><code><?= View::e($entry['ip_address'] ?? '—') ?></code></dd>
                    <dt class="col-sm-4">User-agent</dt>
                    <dd class="col-sm-8"><small class="text-muted"><?= View::e($entry['user_agent'] ?? '—') ?></small></dd>
                <?php else: /* tenant_access */ ?>
                    <dt class="col-sm-4">Tabela</dt>
                    <dd class="col-sm-8"><code><?= View::e($entry['table_name']) ?></code></dd>
                    <dt class="col-sm-4">Operacja</dt>
                    <dd class="col-sm-8"><code><?= View::e($entry['operation']) ?></code></dd>
                    <dt class="col-sm-4">Caller</dt>
                    <dd class="col-sm-8">
                        <?php if (!empty($entry['caller_file'])): ?>
                            <code class="small"><?= View::e(basename((string)$entry['caller_file'])) ?>:<?= (int)($entry['caller_line'] ?? 0) ?></code>
                            <?php if (!empty($entry['caller_class'])): ?>
                                <br><small class="text-muted"><?= View::e($entry['caller_class']) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </dd>
                    <dt class="col-sm-4">Request</dt>
                    <dd class="col-sm-8">
                        <code class="small">
                            <?= View::e(($entry['request_method'] ?? '') . ' ' . ($entry['request_path'] ?? '')) ?>
                        </code>
                    </dd>
                    <dt class="col-sm-4">Severity</dt>
                    <dd class="col-sm-8">
                        <?php $sev = (string)($entry['severity'] ?? 'info'); ?>
                        <span class="badge bg-<?= $sev === 'critical' ? 'danger' : ($sev === 'warning' ? 'warning text-dark' : 'info text-dark') ?>">
                            <?= View::e($sev) ?>
                        </span>
                    </dd>
                    <dt class="col-sm-4">Notatka</dt>
                    <dd class="col-sm-8"><?= View::e($entry['notes'] ?? '—') ?></dd>
                <?php endif; ?>
            </dl>
        </div>

        <?php
        // JSON diff: jeśli details wygląda jak JSON z "before"/"after", pokaż diff.
        $diff = null;
        $detailsRaw = $entry['details'] ?? null;
        if (is_string($detailsRaw) && $detailsRaw !== '' && str_starts_with(trim($detailsRaw), '{')) {
            $decoded = json_decode($detailsRaw, true);
            if (is_array($decoded) && (isset($decoded['before']) || isset($decoded['after']))) {
                $diff = $decoded;
            }
        }
        if ($diff !== null): ?>
            <div class="card p-3 mt-3">
                <h6 class="mb-3">Zmiana danych</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block mb-1">Przed</small>
                        <pre class="bg-light p-2 rounded small mb-0"><?= View::e(json_encode($diff['before'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block mb-1">Po</small>
                        <pre class="bg-light p-2 rounded small mb-0"><?= View::e(json_encode($diff['after'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-5">
        <div class="card p-3">
            <h6 class="mb-3">Powiązane zdarzenia <small class="text-muted">(±30 min)</small></h6>
            <?php if (empty($related)): ?>
                <p class="text-muted mb-0 small">Brak innych zdarzeń tego użytkownika w tym oknie czasowym.</p>
            <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($related as $r): ?>
                        <li class="mb-2 pb-2 border-bottom">
                            <small class="text-muted"><?= View::e(format_datetime($r['created_at'])) ?></small><br>
                            <code class="small"><?= View::e($r['action']) ?></code>
                            <?php if (!empty($r['entity'])): ?>
                                <span class="text-muted">— <?= View::e($r['entity']) ?>
                                    <?php if (!empty($r['entity_id'])): ?>#<?= (int)$r['entity_id'] ?><?php endif; ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($r['details'])): ?>
                                <br><small class="text-muted"><?= View::e(mb_substr((string)$r['details'], 0, 100)) ?></small>
                            <?php endif; ?>
                            <a href="<?= url('admin/audit-log/activity_log/' . (int)$r['id']) ?>" class="ms-1"><i class="bi bi-arrow-right"></i></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card p-3 mt-3">
            <h6 class="mb-2">Surowy zapis</h6>
            <pre class="bg-light p-2 rounded small mb-0" style="max-height: 320px; overflow:auto;"><?= View::e(json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        </div>
    </div>
</div>
