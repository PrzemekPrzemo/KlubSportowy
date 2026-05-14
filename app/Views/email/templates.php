<?php
use App\Helpers\View;
// Grupuj po typie
$byType = [];
foreach ($templates as $t) {
    $byType[$t['template_type']][] = $t;
}
$legacyTypes = ['welcome', 'fee_reminder', 'license_expiry', 'medical_expiry', 'event_reminder', 'password_reset'];
$catalog = $eventCatalog ?? [];
$registered = $registered ?? [];
?>
<p class="text-muted">Kazdy szablon posiada wersje globalna (domyslna) i moze byc nadpisany per-klub. Klub moze tez stworzyc szablon dla dowolnego zdarzenia z katalogu ponizej.</p>

<h5 class="mt-4 mb-2">Klasyczne szablony</h5>
<div class="card">
    <table class="table mb-0">
        <thead class="table-light">
            <tr><th>Typ</th><th>Nazwa</th><th>Temat</th><th>Zrodlo</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($legacyTypes as $type): ?>
            <?php $tpl = $byType[$type][0] ?? null; ?>
            <tr>
                <td><code><?= View::e($type) ?></code></td>
                <td><?= View::e($tpl['name'] ?? '—') ?></td>
                <td><small><?= View::e(substr($tpl['subject'] ?? '—', 0, 50)) ?></small></td>
                <td>
                    <?php if ($tpl && $tpl['club_id'] !== null): ?>
                        <span class="badge bg-primary">per-klub</span>
                    <?php elseif ($tpl): ?>
                        <span class="badge bg-secondary">globalny</span>
                    <?php else: ?>
                        <span class="badge bg-light text-dark">brak</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <a href="<?= url('email/templates/' . $type) ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edytuj
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (!empty($catalog)): ?>
<h5 class="mt-4 mb-2">Katalog zdarzen (nowy format <code>{{var.path}}</code>)</h5>
<p class="text-muted small">Lista zdarzen ktore moga wyzwolic email. Klub moze utworzyc wlasny szablon dla kazdego zdarzenia.</p>
<?php foreach ($catalog as $category => $events): ?>
    <div class="card mb-3">
        <div class="card-header"><strong><?= View::e(ucfirst($category)) ?></strong></div>
        <table class="table mb-0">
            <thead class="table-light">
                <tr><th>Kod</th><th>Nazwa</th><th>Opis</th><th>Status klubu</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($events as $ev): ?>
                <?php $hasOverride = isset($registered[$ev['code']]) && $registered[$ev['code']]['club_id'] !== null; ?>
                <tr>
                    <td><code><?= View::e($ev['code']) ?></code></td>
                    <td><?= View::e($ev['name']) ?></td>
                    <td><small class="text-muted"><?= View::e($ev['description']) ?></small></td>
                    <td>
                        <?php if ($hasOverride): ?>
                            <span class="badge bg-primary">nadpisany per-klub</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">uzywa default</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= url('email/templates/' . $ev['code']) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edytuj
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
<?php endif; ?>
