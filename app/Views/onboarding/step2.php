<?php use App\Helpers\View; ?>

<div class="card">
    <div class="card-body p-4">
        <h4 class="mb-1"><i class="bi bi-trophy"></i> Sekcje sportowe</h4>
        <p class="text-muted mb-4">Wybierz sporty, w których działa Twój klub.</p>

        <form method="POST" action="<?= url('onboarding/step2') ?>">
            <?= csrf_field() ?>

            <div class="row g-3 mb-4">
                <?php foreach ($allSports as $sport): ?>
                    <?php $checked = in_array((int)$sport['id'], $clubSportIds, true); ?>
                    <div class="col-md-4 col-sm-6">
                        <label class="card h-100 border <?= $checked ? 'border-primary' : '' ?>" style="cursor:pointer;">
                            <div class="card-body text-center p-3">
                                <input type="checkbox" name="sports[]" value="<?= (int)$sport['id'] ?>"
                                       class="form-check-input position-absolute top-0 end-0 m-2"
                                       <?= $checked ? 'checked' : '' ?>>
                                <div class="fs-1 mb-2">
                                    <i class="bi <?= View::e($sport['icon'] ?? 'bi-trophy') ?>"
                                       style="color: <?= View::e($sport['color'] ?? '#0d6efd') ?>"></i>
                                </div>
                                <h6 class="mb-1"><?= View::e($sport['name']) ?></h6>
                                <?php if (!empty($sport['federation_name'])): ?>
                                    <small class="text-muted"><?= View::e($sport['federation_name']) ?></small>
                                <?php endif; ?>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($allSports)): ?>
                <div class="alert alert-info">
                    Brak dostepnych sportow w katalogu. Skontaktuj sie z administratorem.
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between">
                <a href="<?= url('onboarding/step1') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Wstecz
                </a>
                <button type="submit" class="btn btn-primary">
                    Dalej <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>
</div>
