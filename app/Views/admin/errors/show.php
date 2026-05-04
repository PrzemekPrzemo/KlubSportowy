<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-bug-fill me-2"></i>Błąd #<?= (int)$row['id'] ?></h4>
    <a href="<?= url('admin/errors') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Powrót</a>
</div>

<?php
$levelColors = ['debug' => 'secondary', 'info' => 'info', 'warning' => 'warning', 'error' => 'danger', 'critical' => 'dark'];
$color = $levelColors[$row['level']] ?? 'secondary';
?>

<div class="card mb-3">
    <div class="card-body">
        <div class="mb-2">
            <span class="badge bg-<?= $color ?>"><?= View::e($row['level']) ?></span>
            <span class="text-muted ms-2"><?= format_datetime($row['created_at']) ?></span>
        </div>
        <h5 class="mb-3"><?= View::e($row['message']) ?></h5>
        <dl class="row mb-0 small">
            <dt class="col-sm-2">Plik</dt>
            <dd class="col-sm-10"><code><?= View::e($row['file'] ?? '—') ?><?= $row['line'] ? ':' . (int)$row['line'] : '' ?></code></dd>
            <dt class="col-sm-2">URL</dt>
            <dd class="col-sm-10"><code><?= View::e($row['url'] ?? '—') ?></code></dd>
            <dt class="col-sm-2">IP</dt>
            <dd class="col-sm-10"><?= View::e($row['ip_address'] ?? '—') ?></dd>
            <dt class="col-sm-2">Użytkownik</dt>
            <dd class="col-sm-10">
                <?= $row['user_id'] ? '#' . (int)$row['user_id'] : '—' ?>
            </dd>
            <dt class="col-sm-2">Klub</dt>
            <dd class="col-sm-10">
                <?= $row['club_id'] ? '#' . (int)$row['club_id'] : '—' ?>
            </dd>
        </dl>
    </div>
</div>

<?php if (!empty($row['context'])): ?>
<div class="card mb-3">
    <div class="card-header py-2"><strong>Context (JSON)</strong></div>
    <div class="card-body">
        <pre class="mb-0" style="max-height:300px; overflow:auto;"><code><?= View::e(json_encode(json_decode((string)$row['context']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></code></pre>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($row['trace'])): ?>
<div class="card">
    <div class="card-header py-2"><strong>Stack trace</strong></div>
    <div class="card-body">
        <pre class="mb-0" style="max-height:500px; overflow:auto; font-size:0.8em;"><code><?= View::e($row['trace']) ?></code></pre>
    </div>
</div>
<?php endif; ?>
