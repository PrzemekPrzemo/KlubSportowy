<?php use App\Helpers\View; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-shop"></i> Produkty sklepu</h2>
        <a href="<?= url('shop/products/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Dodaj produkt
        </a>
    </div>

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
                        <th>Zdjecie</th>
                        <th>Nazwa</th>
                        <th>Kategoria</th>
                        <th>Cena</th>
                        <th>Stan magazynowy</th>
                        <th>Aktywny</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Brak produktow.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($p['image_path'])): ?>
                                        <img src="<?= url($p['image_path']) ?>" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:4px;">
                                    <?php else: ?>
                                        <span class="text-muted"><i class="bi bi-image" style="font-size:1.5rem;"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= View::e($p['name']) ?></td>
                                <td><span class="badge bg-secondary"><?= View::e($p['category']) ?></span></td>
                                <td><?= format_money($p['price']) ?></td>
                                <td>
                                    <?php if ((int)$p['stock'] <= 0): ?>
                                        <span class="badge bg-danger">0</span>
                                    <?php elseif ((int)$p['stock'] <= 5): ?>
                                        <span class="badge bg-warning text-dark"><?= (int)$p['stock'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?= (int)$p['stock'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= (int)$p['is_active'] ? '<span class="badge bg-success">Tak</span>' : '<span class="badge bg-secondary">Nie</span>' ?>
                                </td>
                                <td>
                                    <a href="<?= url('shop/products/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary" title="Edytuj">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="<?= url('shop/products/' . $p['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Usunac produkt?')">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger" title="Usun"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
