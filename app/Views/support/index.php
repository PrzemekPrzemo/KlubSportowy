<?php use App\Helpers\View; ?>
<div class="mb-3 text-end">
    <a href="<?= url('support/create') ?>" class="btn btn-success"><i class="bi bi-plus"></i> NOWE ZGŁOSZENIE</a>
</div>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>#</th><th>Temat</th><th>Priorytet</th><th>Status</th><th>Data</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($tickets)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak zgłoszeń.</td></tr>
        <?php else: foreach ($tickets as $t): ?>
            <tr>
                <td>#<?= (int)$t['id'] ?></td>
                <td><a href="<?= url('support/' . (int)$t['id']) ?>"><?= View::e($t['subject']) ?></a></td>
                <td><span class="badge bg-<?= $t['priority']==='urgent'?'danger':'secondary' ?>"><?= View::e($t['priority']) ?></span></td>
                <td><span class="badge bg-<?= $t['status']==='open'?'danger':($t['status']==='closed'?'secondary':'info') ?>"><?= View::e($t['status']) ?></span></td>
                <td><small><?= format_datetime($t['created_at']) ?></small></td>
                <td class="text-end"><a href="<?= url('support/' . (int)$t['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
