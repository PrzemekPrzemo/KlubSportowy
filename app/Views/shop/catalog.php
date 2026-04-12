<?php use App\Helpers\View; ?>

<div class="pub-hero">
    <div class="container text-center">
        <?php if (!empty($club['logo_path'])): ?>
            <img src="<?= url($club['logo_path']) ?>" alt="" style="max-height:60px;" class="mb-2">
        <?php endif; ?>
        <h1><?= View::e($club['name']) ?> — Sklep</h1>
        <?php if (!empty($club['motto'])): ?>
            <p class="lead mb-0"><?= View::e($club['motto']) ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="<?= url('pub/' . View::e($club['subdomain'])) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Powrot do strony klubu
        </a>
        <a href="<?= url('shop/cart') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-cart3"></i> Koszyk
            <?php
            $cartCount = 0;
            $cart = \App\Helpers\Session::get('shop_cart', []);
            foreach ($cart as $item) $cartCount += $item['quantity'];
            if ($cartCount > 0): ?>
                <span class="badge bg-danger"><?= $cartCount ?></span>
            <?php endif; ?>
        </a>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-shop" style="font-size:3rem;"></i>
            <p class="mt-2">Sklep jest obecnie pusty.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($products as $p): ?>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="card h-100">
                        <?php if (!empty($p['image_path'])): ?>
                            <img src="<?= url($p['image_path']) ?>" class="card-img-top" alt="<?= View::e($p['name']) ?>"
                                 style="height:200px;object-fit:cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:200px;">
                                <i class="bi bi-image text-muted" style="font-size:3rem;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-secondary mb-2 align-self-start"><?= View::e($p['category']) ?></span>
                            <h6 class="card-title"><?= View::e($p['name']) ?></h6>
                            <?php if (!empty($p['description'])): ?>
                                <p class="card-text small text-muted flex-grow-1"><?= View::e(mb_strimwidth($p['description'], 0, 100, '...')) ?></p>
                            <?php endif; ?>
                            <div class="mt-auto">
                                <p class="fw-bold text-primary mb-2"><?= format_money($p['price']) ?></p>
                                <?php if ((int)$p['stock'] > 0): ?>
                                    <form method="POST" action="<?= url('shop/cart/add') ?>">
                                        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                        <input type="hidden" name="club_id" value="<?= (int)$club['id'] ?>">
                                        <?php
                                        $sizes = !empty($p['sizes']) ? json_decode($p['sizes'], true) : [];
                                        if (!empty($sizes)): ?>
                                            <select name="size" class="form-select form-select-sm mb-2">
                                                <option value="">Wybierz rozmiar</option>
                                                <?php foreach ($sizes as $sz): ?>
                                                    <option value="<?= View::e($sz) ?>"><?= View::e($sz) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                        <div class="d-flex gap-2">
                                            <input type="number" name="quantity" value="1" min="1" max="<?= (int)$p['stock'] ?>" class="form-control form-control-sm" style="width:70px;">
                                            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                                                <i class="bi bi-cart-plus"></i> Dodaj
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-danger">Brak w magazynie</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
