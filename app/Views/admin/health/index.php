<?php use App\Helpers\View; ?>

<?php
function fmt_bytes(?int $b): string {
    if ($b === null) return '—';
    $u = ['B','KB','MB','GB','TB'];
    $i = 0;
    $v = (float)$b;
    while ($v >= 1024 && $i < 4) { $v /= 1024; $i++; }
    return number_format($v, 2) . ' ' . $u[$i];
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-heart-pulse me-2"></i>Zdrowie systemu</h4>
        <small class="text-muted">Ostatnie sprawdzenie: <?= View::e($checkedAt) ?></small>
    </div>
    <a href="<?= url('admin/health') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-clockwise"></i> Odśwież</a>
</div>

<div class="row g-3">
    <!-- PHP -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header py-2"><strong><i class="bi bi-filetype-php me-1"></i>PHP</strong></div>
            <div class="card-body py-2">
                <div class="mb-2">
                    <span class="h5"><?= View::e($php['version']) ?></span>
                    <?php if ($php['version_ok']): ?>
                        <span class="badge bg-success">OK</span>
                    <?php else: ?>
                        <span class="badge bg-danger">Aktualizuj do ≥ 8.1</span>
                    <?php endif; ?>
                </div>
                <dl class="row mb-0 small">
                    <dt class="col-6">SAPI</dt><dd class="col-6"><?= View::e($php['sapi']) ?></dd>
                    <dt class="col-6">Memory limit</dt><dd class="col-6"><?= View::e($php['memory_limit']) ?></dd>
                    <dt class="col-6">Upload max</dt><dd class="col-6"><?= View::e($php['max_upload']) ?></dd>
                    <dt class="col-6">POST max</dt><dd class="col-6"><?= View::e($php['post_max']) ?></dd>
                    <dt class="col-6">display_errors</dt><dd class="col-6"><?= $php['display_errors'] ? 'ON' : 'OFF' ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Disk -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header py-2"><strong><i class="bi bi-hdd me-1"></i>Dysk</strong></div>
            <div class="card-body py-2">
                <?php
                $pct = $disk['pct_used'] ?? 0;
                $barColor = $pct >= 95 ? 'bg-danger' : ($pct >= 80 ? 'bg-warning' : 'bg-success');
                ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small">
                        <span><?= fmt_bytes($disk['used_bytes']) ?> z <?= fmt_bytes($disk['total_bytes']) ?></span>
                        <strong><?= $pct ?>%</strong>
                    </div>
                    <div class="progress" style="height:10px;">
                        <div class="progress-bar <?= $barColor ?>" style="width: <?= (float)$pct ?>%;"></div>
                    </div>
                </div>
                <dl class="row mb-0 small">
                    <dt class="col-6">Ścieżka</dt><dd class="col-6"><code class="small"><?= View::e($disk['path']) ?></code></dd>
                    <dt class="col-6">Wolne</dt><dd class="col-6"><?= fmt_bytes($disk['free_bytes']) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- DB -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header py-2"><strong><i class="bi bi-database me-1"></i>Baza danych</strong></div>
            <div class="card-body py-2">
                <div class="mb-2 small text-muted">MySQL: <code><?= View::e($db['server'] ?? '?') ?></code></div>
                <div class="small text-muted mb-1">Największe tabele:</div>
                <table class="table table-sm mb-0">
                    <tbody>
                        <?php foreach (array_slice($db['tables'] ?? [], 0, 8) as $t): ?>
                            <tr>
                                <td class="small"><code><?= View::e($t['table_name']) ?></code></td>
                                <td class="small text-end"><?= number_format((int)$t['table_rows']) ?> rows</td>
                                <td class="small text-end"><?= fmt_bytes((int)$t['size_bytes']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Rozszerzenia -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2"><strong><i class="bi bi-puzzle me-1"></i>Rozszerzenia PHP</strong></div>
            <div class="card-body py-2">
                <?php foreach ($php['ext'] as $ext => $loaded): ?>
                    <span class="badge <?= $loaded ? 'bg-success' : 'bg-danger' ?> me-1 mb-1">
                        <i class="bi <?= $loaded ? 'bi-check' : 'bi-x' ?>"></i> <?= View::e($ext) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Migracje + pliki -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2"><strong><i class="bi bi-layers me-1"></i>Migracje + pliki logów</strong></div>
            <div class="card-body py-2">
                <dl class="row mb-0 small">
                    <dt class="col-5">Najnowsza migracja</dt>
                    <dd class="col-7"><code><?= View::e($latestMigration ?? '—') ?></code></dd>
                    <dt class="col-5">Liczba migracji</dt>
                    <dd class="col-7"><?= (int)$migrationsCount ?></dd>
                    <dt class="col-5">app.log</dt>
                    <dd class="col-7"><?= fmt_bytes($files['app_log']) ?></dd>
                    <dt class="col-5">errors.log</dt>
                    <dd class="col-7"><?= fmt_bytes($files['error_log']) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Błędy / bezpieczeństwo -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2"><strong><i class="bi bi-bug me-1"></i>Błędy + bezpieczeństwo</strong></div>
            <div class="card-body py-2">
                <dl class="row mb-0 small">
                    <dt class="col-7">Błędy (24h)</dt>
                    <dd class="col-5 text-end <?= $errors24 > 0 ? 'text-danger fw-bold' : '' ?>"><?= (int)$errors24 ?></dd>
                    <dt class="col-7">Błędy (7 dni)</dt>
                    <dd class="col-5 text-end"><?= (int)$errors7 ?></dd>
                    <dt class="col-7">Critical (7 dni)</dt>
                    <dd class="col-5 text-end <?= $critical7 > 0 ? 'text-danger fw-bold' : '' ?>"><?= (int)$critical7 ?></dd>
                    <dt class="col-7">Security events (24h)</dt>
                    <dd class="col-5 text-end"><?= (int)$secEvents24 ?></dd>
                    <dt class="col-7">Nieudane logowania (24h)</dt>
                    <dd class="col-5 text-end <?= $loginFailed24 > 10 ? 'text-warning fw-bold' : '' ?>"><?= (int)$loginFailed24 ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Session config -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2"><strong><i class="bi bi-shield me-1"></i>Konfiguracja sesji</strong></div>
            <div class="card-body py-2">
                <dl class="row mb-0 small">
                    <dt class="col-7">gc_maxlifetime</dt>
                    <dd class="col-5 text-end"><?= (int)$session['gc_maxlifetime'] ?> s</dd>
                    <dt class="col-7">cookie_httponly</dt>
                    <dd class="col-5 text-end"><?= $session['cookie_httponly'] ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-danger">OFF</span>' ?></dd>
                    <dt class="col-7">cookie_secure</dt>
                    <dd class="col-5 text-end"><?= $session['cookie_secure'] ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-warning">OFF</span>' ?></dd>
                    <dt class="col-7">cookie_samesite</dt>
                    <dd class="col-5 text-end"><?= View::e($session['cookie_samesite']) ?></dd>
                    <dt class="col-7">strict_mode</dt>
                    <dd class="col-5 text-end"><?= $session['strict_mode'] ? '<span class="badge bg-success">ON</span>' : '<span class="badge bg-warning">OFF</span>' ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Liczniki biznesowe -->
    <div class="col-12">
        <div class="card">
            <div class="card-header py-2"><strong><i class="bi bi-graph-up me-1"></i>Liczniki</strong></div>
            <div class="card-body py-2">
                <div class="row g-2 small">
                    <?php foreach ($counters as $k => $v): ?>
                        <div class="col-md-3">
                            <div class="border rounded p-2">
                                <div class="text-muted"><?= View::e($k) ?></div>
                                <div class="h5 mb-0"><?= (int)$v ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
