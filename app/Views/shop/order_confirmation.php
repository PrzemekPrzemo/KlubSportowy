<?php use App\Helpers\View; ?>

<div class="container py-4">
    <div class="text-center mb-4">
        <i class="bi bi-check-circle text-success" style="font-size:4rem;"></i>
        <h2 class="mt-2">Zamowienie przyjete!</h2>
        <p class="text-muted">Numer zamowienia: <strong>#<?= (int)$order['id'] ?></strong></p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><strong>Szczegoly zamowienia</strong></div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Zamawiajacy:</div>
                        <div class="col-sm-8"><?= View::e($order['customer_name']) ?></div>
                    </div>
                    <?php if (!empty($order['customer_email'])): ?>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Email:</div>
                            <div class="col-sm-8"><?= View::e($order['customer_email']) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($order['customer_phone'])): ?>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Telefon:</div>
                            <div class="col-sm-8"><?= View::e($order['customer_phone']) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($order['shipping_address'])): ?>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Adres dostawy:</div>
                            <div class="col-sm-8"><?= nl2br(View::e($order['shipping_address'])) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Status:</div>
                        <div class="col-sm-8"><span class="badge bg-info"><?= View::e($order['status']) ?></span></div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><strong>Produkty</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produkt</th>
                                <th>Rozmiar</th>
                                <th>Ilosc</th>
                                <th class="text-end">Cena</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                                <tr>
                                    <td><?= View::e($item['product_name']) ?></td>
                                    <td><?= View::e($item['size'] ?: '-') ?></td>
                                    <td><?= (int)$item['quantity'] ?></td>
                                    <td class="text-end"><?= format_money($item['unit_price'] * $item['quantity']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="3" class="text-end fw-bold">Razem:</td>
                                <td class="text-end fw-bold text-primary"><?= format_money($order['total']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
