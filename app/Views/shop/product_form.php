<?php use App\Helpers\View; ?>

<div class="container-fluid py-4">
    <h2><i class="bi bi-shop"></i> <?= View::e($title) ?></h2>

    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?= url('shop/products/store') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <?php if ($product): ?>
                    <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nazwa produktu *</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?= View::e($product['name'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Opis</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= View::e($product['description'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="price" class="form-label">Cena (PLN) *</label>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0"
                                       value="<?= View::e($product['price'] ?? '0.00') ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="category" class="form-label">Kategoria</label>
                                <select class="form-select" id="category" name="category">
                                    <?php
                                    $categories = ['odzież', 'sprzęt', 'akcesoria', 'gadżety', 'inne'];
                                    $current = $product['category'] ?? 'inne';
                                    foreach ($categories as $cat): ?>
                                        <option value="<?= View::e($cat) ?>" <?= $current === $cat ? 'selected' : '' ?>>
                                            <?= View::e(ucfirst($cat)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="stock" class="form-label">Stan magazynowy</label>
                                <input type="number" class="form-control" id="stock" name="stock" min="0"
                                       value="<?= (int)($product['stock'] ?? 0) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="sizes" class="form-label">Rozmiary (oddzielone przecinkami)</label>
                            <input type="text" class="form-control" id="sizes" name="sizes"
                                   placeholder="np. S, M, L, XL, XXL"
                                   value="<?php
                                       if (!empty($product['sizes'])) {
                                           $s = json_decode($product['sizes'], true);
                                           echo View::e(is_array($s) ? implode(', ', $s) : '');
                                       }
                                   ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="image" class="form-label">Zdjecie produktu</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <?php if (!empty($product['image_path'])): ?>
                                <div class="mt-2">
                                    <img src="<?= url($product['image_path']) ?>" alt="" class="img-thumbnail" style="max-width:200px;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                                   <?= ($product === null || (int)($product['is_active'] ?? 1)) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Aktywny (widoczny w sklepie)</label>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Zapisz
                    </button>
                    <a href="<?= url('shop/products') ?>" class="btn btn-secondary">Anuluj</a>
                </div>
            </form>
        </div>
    </div>
</div>
