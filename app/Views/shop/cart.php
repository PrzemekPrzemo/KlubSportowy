<?php use App\Helpers\View; ?>

<div class="container py-4">
    <h2><i class="bi bi-cart3"></i> Koszyk</h2>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashWarning)): ?>
        <div class="alert alert-warning"><?= View::e($flashWarning) ?></div>
    <?php endif; ?>

    <?php if (empty($cart)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-cart-x" style="font-size:3rem;"></i>
            <p class="mt-2">Koszyk jest pusty.</p>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Produkt</th>
                            <th>Rozmiar</th>
                            <th>Cena</th>
                            <th>Ilosc</th>
                            <th>Suma</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $key => $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($item['image_path'])): ?>
                                            <img src="<?= url($item['image_path']) ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">
                                        <?php endif; ?>
                                        <?= View::e($item['name']) ?>
                                    </div>
                                </td>
                                <td><?= View::e($item['size'] ?: '-') ?></td>
                                <td><?= format_money($item['price']) ?></td>
                                <td><?= (int)$item['quantity'] ?></td>
                                <td class="fw-bold"><?= format_money($item['price'] * $item['quantity']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="text-end fw-bold">Razem:</td>
                            <td class="fw-bold text-primary"><?= format_money($total) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= url('shop/checkout') ?>" class="btn btn-primary">
                <i class="bi bi-credit-card"></i> Zamow
            </a>
        </div>
    <?php endif; ?>
</div>
