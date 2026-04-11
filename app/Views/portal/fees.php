<?php use App\Helpers\View; ?>
<p class="text-muted">Twoje wpłaty w roku <?= (int)$year ?>.</p>
<div class="card">
    <table class="table mb-0">
        <thead class="table-light">
            <tr><th>Data</th><th>Tytuł</th><th>Okres</th><th class="text-end">Kwota</th><th>Metoda</th></tr>
        </thead>
        <tbody>
        <?php if (empty($payments)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Brak wpłat.</td></tr>
        <?php else: ?>
            <?php $total = 0; foreach ($payments as $p): $total += (float)$p['amount']; ?>
                <tr>
                    <td><?= format_date($p['payment_date']) ?></td>
                    <td><?= View::e($p['fee_name'] ?? '—') ?></td>
                    <td><?= (int)$p['period_year'] ?><?= $p['period_month'] ? '-' . str_pad((string)$p['period_month'],2,'0',STR_PAD_LEFT) : '' ?></td>
                    <td class="text-end"><strong><?= format_money($p['amount']) ?></strong></td>
                    <td><span class="badge bg-secondary"><?= View::e($p['method']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <tr class="table-light">
                <td colspan="3" class="text-end"><strong>Razem:</strong></td>
                <td class="text-end"><strong><?= format_money($total) ?></strong></td>
                <td></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
