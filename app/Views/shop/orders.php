<?php use App\Helpers\View; ?>

<div class="container-fluid py-4">
    <h2><i class="bi bi-receipt"></i> Zamowienia sklepu</h2>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Klient</th>
                        <th>Email</th>
                        <th>Kwota</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Zmien status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pagination['data'])): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Brak zamowien.</td></tr>
                    <?php else: ?>
                        <?php
                        $statusColors = [
                            'nowe'          => 'info',
                            'opłacone'      => 'primary',
                            'w_realizacji'  => 'warning',
                            'wysłane'       => 'secondary',
                            'odebrane'      => 'success',
                            'anulowane'     => 'danger',
                        ];
                        foreach ($pagination['data'] as $o): ?>
                            <tr>
                                <td><?= (int)$o['id'] ?></td>
                                <td><?= View::e($o['customer_name']) ?></td>
                                <td><?= View::e($o['customer_email'] ?? '-') ?></td>
                                <td class="fw-bold"><?= format_money($o['total']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $statusColors[$o['status']] ?? 'secondary' ?>">
                                        <?= View::e($o['status']) ?>
                                    </span>
                                </td>
                                <td><?= format_datetime($o['created_at']) ?></td>
                                <td>
                                    <form method="POST" action="<?= url('shop/orders/' . $o['id'] . '/status') ?>" class="d-flex gap-1">
                                        <?= csrf_field() ?>
                                        <select name="status" class="form-select form-select-sm" style="width:140px;">
                                            <?php
                                            $statuses = ['nowe', 'opłacone', 'w_realizacji', 'wysłane', 'odebrane', 'anulowane'];
                                            foreach ($statuses as $s): ?>
                                                <option value="<?= View::e($s) ?>" <?= $o['status'] === $s ? 'selected' : '' ?>>
                                                    <?= View::e($s) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (($pagination['last_page'] ?? 1) > 1): ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center">
                <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
                    <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('shop/orders?page=' . $p) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>
