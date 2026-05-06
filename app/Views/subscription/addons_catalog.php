<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-plus-circle text-success me-2"></i>
        Dokup zasoby
    </h3>
    <a href="<?= url('club/subscription') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Wróć do subskrypcji
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="alert alert-info">
    <i class="bi bi-lightbulb"></i>
    Twoje aktualne limity:
    <strong><?= $limits['max_members'] === null ? 'bez limitu' : (int)$limits['max_members'] ?></strong> zawodników,
    <strong><?= $limits['max_sports']  === null ? 'bez limitu' : (int)$limits['max_sports']  ?></strong> sekcji sportowych.
    <?php if ($limits['addon_members_boost'] > 0 || $limits['addon_sports_boost'] > 0): ?>
        (w tym z addonów: +<?= (int)$limits['addon_members_boost'] ?> zaw., +<?= (int)$limits['addon_sports_boost'] ?> sek.)
    <?php endif; ?>
</div>

<?php foreach ($grouped as $catKey => $catItems):
    $catLabel = $categories[$catKey] ?? $catKey;
?>
    <h5 class="mt-4 mb-3 text-muted text-uppercase small">
        <?= View::e($catLabel) ?>
    </h5>
    <div class="row g-3">
        <?php foreach ($catItems as $item):
            $isActive = in_array((int)$item['id'], $activeAddonIds, true);
            $yearlyPerMonth = (float)$item['yearly_price'] / 12;
            $monthlySavings = (float)$item['monthly_price'] - $yearlyPerMonth;
        ?>
        <div class="col-md-6 col-lg-4 d-flex">
            <div class="card p-3 w-100 <?= $isActive ? 'border-success bg-light' : '' ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0"><?= View::e($item['name']) ?></h5>
                    <?php if ($isActive): ?>
                        <span class="badge bg-success">✓ AKTYWNY</span>
                    <?php endif; ?>
                </div>
                <small class="text-muted mb-3" style="min-height: 36px;">
                    <?= View::e($item['description']) ?>
                </small>

                <div class="mb-3">
                    <div class="display-6 fw-bold">
                        <?= number_format((float)$item['monthly_price'], 0, ',', ' ') ?> <small class="fs-6 text-muted">zł/m-c</small>
                    </div>
                    <?php if ((float)$item['yearly_price'] > 0 && $monthlySavings > 0): ?>
                        <small class="text-success">
                            Płatne rocznie: <?= number_format($yearlyPerMonth, 0, ',', ' ') ?> zł/m-c
                            (oszczędzasz <?= number_format($monthlySavings * 12, 0, ',', ' ') ?> zł rocznie)
                        </small>
                    <?php endif; ?>
                </div>

                <?php if (!empty($item['boost_field']) && !empty($item['boost_amount'])): ?>
                    <div class="mb-3">
                        <span class="badge bg-info-subtle text-info border border-info-subtle p-2">
                            <i class="bi bi-arrow-up-circle"></i>
                            Boost: +<?= (int)$item['boost_amount'] ?>
                            <?= $item['boost_field'] === 'max_members' ? 'zawodników' : 'sekcji' ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if ($isActive): ?>
                    <button class="btn btn-outline-secondary mt-auto" disabled>
                        Już aktywny
                    </button>
                <?php else: ?>
                    <form method="POST" action="<?= url('club/subscription/addons/buy') ?>" class="mt-auto">
                        <?= csrf_field() ?>
                        <input type="hidden" name="addon_code" value="<?= View::e($item['code']) ?>">
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text">Ilość</span>
                            <input type="number" name="quantity" value="1" min="1" max="20"
                                   class="form-control text-center">
                        </div>
                        <button type="submit" class="btn btn-success w-100"
                                onclick="return confirm('Aktywować addon <?= View::e($item['name']) ?>?')">
                            <i class="bi bi-cart-plus"></i> Aktywuj
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<div class="alert alert-light mt-4 text-center small text-muted">
    <i class="bi bi-shield-check"></i>
    Faktura VAT zostanie wystawiona w Twoim cyklu rozliczeniowym. Anuluj kiedy chcesz — addon pozostanie aktywny do końca opłaconego okresu.
</div>
