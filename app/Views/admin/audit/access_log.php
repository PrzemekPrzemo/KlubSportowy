<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-eye-slash me-2"></i>Cross-tenant access log</h4>
        <small class="text-muted">
            Kazde wywolanie <code>ClubScopedModel::withoutScope()</code> — kto, kiedy i z jakiej tabeli czytal/pisal dane bez filtra <code>club_id</code>.
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/audit/isolation') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-shield-check"></i> Audyt izolacji
        </a>
    </div>
</div>

<?php if (!empty($stats)): ?>
<div class="card mb-3">
    <div class="card-header py-2"><small class="text-muted">Top bypasses (ostatnie 7 dni)</small></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th>Tabela</th>
                    <th>Operacja</th>
                    <th class="text-end">Wywolan</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($stats as $s): ?>
                <tr>
                    <td><code><?= View::e($s['table_name']) ?></code></td>
                    <td><span class="badge bg-secondary"><?= View::e($s['operation']) ?></span></td>
                    <td class="text-end"><?= (int)$s['c'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header py-2">
        <small class="text-muted">
            Ostatnie wpisy (<?= (int)$listing['total'] ?> lacznie, strona <?= (int)$listing['current_page'] ?> z <?= (int)$listing['last_page'] ?>)
        </small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kiedy</th>
                    <th>Uzytkownik</th>
                    <th>Klub aktywny</th>
                    <th>Tabela</th>
                    <th>Operacja</th>
                    <th>Caller</th>
                    <th>Request</th>
                    <th>Severity</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($listing['data'] as $row): ?>
                <tr>
                    <td><small class="text-muted"><?= View::e($row['occurred_at']) ?></small></td>
                    <td>
                        <?= View::e($row['username'] ?? '-') ?>
                        <?php if ((int)($row['is_super_admin'] ?? 0) === 1): ?>
                            <span class="badge bg-warning text-dark ms-1">super</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $row['active_club_id'] !== null ? (int)$row['active_club_id'] : '<em class="text-muted">brak</em>' ?></td>
                    <td><code><?= View::e($row['table_name']) ?></code></td>
                    <td><span class="badge bg-secondary"><?= View::e($row['operation']) ?></span></td>
                    <td>
                        <?php if (!empty($row['caller_file'])): ?>
                            <small><code><?= View::e(basename((string)$row['caller_file'])) ?>:<?= (int)$row['caller_line'] ?></code></small>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><small class="text-muted"><?= View::e(($row['request_method'] ?? '') . ' ' . ($row['request_path'] ?? '')) ?></small></td>
                    <td>
                        <?php
                            $sev = $row['severity'] ?? 'info';
                            $sevClass = $sev === 'critical' ? 'danger' : ($sev === 'warning' ? 'warning text-dark' : 'info text-dark');
                        ?>
                        <span class="badge bg-<?= $sevClass ?>"><?= View::e($sev) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($listing['data'])): ?>
                <tr><td colspan="8" class="text-center text-muted py-3">Brak wpisow.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (((int)$listing['last_page']) > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm">
        <?php for ($p = 1; $p <= (int)$listing['last_page']; $p++): ?>
            <li class="page-item <?= $p === (int)$listing['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= url('admin/audit/access-log?page=' . $p) ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
