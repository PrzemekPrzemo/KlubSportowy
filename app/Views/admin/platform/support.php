<?php use App\Helpers\View; ?>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>#</th><th>Klub</th><th>Temat</th><th>Priorytet</th><th>Kategoria</th><th>Status</th><th>Data</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($tickets)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Brak zgłoszeń.</td></tr>
        <?php else: foreach ($tickets as $t):
            $sCls = match($t['status']) { 'open'=>'danger', 'in_progress'=>'warning', 'waiting'=>'info', default=>'secondary' };
            $pCls = match($t['priority']) { 'urgent'=>'danger', 'high'=>'warning', default=>'secondary' };
        ?>
            <tr>
                <td>#<?= (int)$t['id'] ?></td>
                <td><?= View::e($t['club_name'] ?? '—') ?></td>
                <td><a href="<?= url('admin/platform/support/' . (int)$t['id']) ?>"><?= View::e($t['subject']) ?></a></td>
                <td><span class="badge bg-<?= $pCls ?>"><?= View::e($t['priority']) ?></span></td>
                <td><small><?= View::e($t['category']) ?></small></td>
                <td><span class="badge bg-<?= $sCls ?>"><?= View::e($t['status']) ?></span></td>
                <td><small><?= format_datetime($t['created_at']) ?></small></td>
                <td class="text-end">
                    <form method="POST" action="<?= url('admin/platform/support/' . (int)$t['id'] . '/close') ?>" class="d-inline">
                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary" title="Zamknij"><i class="bi bi-x-circle"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
