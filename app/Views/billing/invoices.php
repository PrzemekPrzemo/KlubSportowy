<?php use App\Helpers\View; ?>
<div class="mb-3 d-flex justify-content-between">
    <div></div>
    <a href="<?= url('billing/plans') ?>" class="btn btn-outline-primary"><i class="bi bi-arrow-up-circle"></i> Zmień plan</a>
</div>
<div class="card">
    <table class="table mb-0">
        <thead class="table-light"><tr><th>Nr faktury</th><th>Data</th><th>Termin</th><th class="text-end">Kwota</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php if (empty($invoices)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak faktur.</td></tr>
        <?php else: foreach ($invoices as $inv):
            $cls = match($inv['status']) { 'paid'=>'success', 'issued'=>'warning', 'cancelled'=>'secondary', default=>'info' };
        ?>
            <tr>
                <td><code><?= View::e($inv['number']) ?></code></td>
                <td><?= format_date($inv['issue_date']) ?></td>
                <td><?= format_date($inv['due_date']) ?></td>
                <td class="text-end"><strong><?= format_money($inv['total']) ?></strong></td>
                <td><span class="badge bg-<?= $cls ?>"><?= View::e($inv['status']) ?></span></td>
                <td class="text-end">
                    <?php if ($inv['status'] === 'issued'): ?>
                        <form method="POST" action="<?= url('billing/invoices/' . (int)$inv['id'] . '/paid') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-success" title="Oznacz jako opłaconą">
                                <i class="bi bi-check2"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
