<?php
use App\Helpers\Csrf;
use App\Helpers\View;
/** @var array $sports */
/** @var array $selectedIds */
?>
<section class="py-5" style="background:#f6f7fb;min-height:80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <?php include __DIR__ . '/_progress.php'; ?>

                        <h2 class="mb-1">Wybierz sporty</h2>
                        <p class="text-muted">Zaznacz <strong>1-3 sekcje sportowe</strong> dla swojego klubu (mozesz dodac wiecej pozniej).</p>

                        <?php if (!empty($flashError)): ?>
                            <div class="alert alert-danger"><?= View::e($flashError) ?></div>
                        <?php endif; ?>

                        <form method="post" action="<?= url('trial/sports') ?>" id="sportsForm">
                            <?= Csrf::field() ?>

                            <div class="row g-3">
                                <?php foreach ($sports as $sport):
                                    $checked = in_array((int)$sport['id'], array_map('intval', $selectedIds), true);
                                    $icon = $sport['icon'] ?? 'bi-trophy';
                                ?>
                                    <div class="col-sm-6 col-md-4">
                                        <label class="card h-100 p-3 sport-card" style="cursor:pointer;border:2px solid <?= $checked ? '#EE2C28' : '#e9ecef' ?>;transition:all .15s;">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="bi <?= View::e($icon) ?>" style="font-size:1.8rem;color:<?= View::e($sport['color'] ?? '#0d6efd') ?>;"></i>
                                                <input class="form-check-input ms-auto sport-check" type="checkbox"
                                                       name="sports[]" value="<?= (int)$sport['id'] ?>"
                                                       <?= $checked ? 'checked' : '' ?>>
                                            </div>
                                            <strong><?= View::e($sport['name']) ?></strong>
                                            <?php if (!empty($sport['federation_code'])): ?>
                                                <small class="text-muted d-block"><?= View::e($sport['federation_code']) ?></small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="<?= url('trial/branding') ?>" class="btn btn-link text-muted">
                                    <i class="bi bi-arrow-left"></i> Wstecz
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    Dalej <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function() {
    var checks = document.querySelectorAll('.sport-check');
    function update() {
        var n = 0;
        checks.forEach(function(c){ if (c.checked) n++; });
        checks.forEach(function(c){
            var card = c.closest('.sport-card');
            if (c.checked) {
                card.style.borderColor = '#EE2C28';
            } else {
                card.style.borderColor = '#e9ecef';
                if (n >= 3) c.disabled = true; else c.disabled = false;
            }
        });
        if (n < 3) checks.forEach(function(c){ if (!c.checked) c.disabled = false; });
    }
    checks.forEach(function(c){ c.addEventListener('change', update); });
    update();
})();
</script>
