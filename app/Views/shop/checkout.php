<?php use App\Helpers\View; ?>

<div class="container py-4">
    <h2><i class="bi bi-credit-card"></i> Zamowienie</h2>

    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header"><strong>Dane zamawiajacego</strong></div>
                <div class="card-body">
                    <form method="POST" action="<?= url('shop/checkout/store') ?>">
                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Imie i nazwisko *</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customer_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="customer_email" name="customer_email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="customer_phone" class="form-label">Telefon</label>
                                <input type="text" class="form-control" id="customer_phone" name="customer_phone">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Adres dostawy</label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Uwagi</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-check-lg"></i> Zloz zamowienie
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card">
                <div class="card-header"><strong>Podsumowanie</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php foreach ($cart as $item): ?>
                                <tr>
                                    <td>
                                        <?= View::e($item['name']) ?>
                                        <?php if ($item['size']): ?>
                                            <small class="text-muted">(<?= View::e($item['size']) ?>)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?= (int)$item['quantity'] ?> x <?= format_money($item['price']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td class="fw-bold">Razem</td>
                                <td class="text-end fw-bold text-primary"><?= format_money($total) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
