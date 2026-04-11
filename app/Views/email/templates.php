<?php
use App\Helpers\View;
// Grupuj po typie
$byType = [];
foreach ($templates as $t) {
    $byType[$t['template_type']][] = $t;
}
$allTypes = ['welcome', 'fee_reminder', 'license_expiry', 'medical_expiry', 'event_reminder', 'password_reset'];
?>
<p class="text-muted">Każdy szablon posiada wersję globalną (domyślną) i może być nadpisany per-klub.</p>

<div class="card">
    <table class="table mb-0">
        <thead class="table-light">
            <tr><th>Typ</th><th>Nazwa</th><th>Temat</th><th>Źródło</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($allTypes as $type): ?>
            <?php $tpl = $byType[$type][0] ?? null; ?>
            <tr>
                <td><code><?= View::e($type) ?></code></td>
                <td><?= View::e($tpl['name'] ?? '—') ?></td>
                <td><small><?= View::e(substr($tpl['subject'] ?? '—', 0, 50)) ?></small></td>
                <td>
                    <?php if ($tpl && $tpl['club_id'] !== null): ?>
                        <span class="badge bg-primary">per-klub</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">globalny</span>
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
