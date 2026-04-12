<?php use App\Helpers\View; ?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body text-center py-4">
                <?php if (($imported ?? 0) > 0): ?>
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                <?php else: ?>
                    <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                <?php endif; ?>
                <h4 class="mt-3">Import zakończony</h4>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h2 class="text-success mb-0"><?= (int)($imported ?? 0) ?></h2>
                        <small class="text-muted">Zaimportowanych</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h2 class="text-warning mb-0"><?= (int)($skipped ?? 0) ?></h2>
                        <small class="text-muted">Pominiętych</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h2 class="text-info mb-0"><?= (int)($imported ?? 0) + (int)($skipped ?? 0) ?></h2>
                        <small class="text-muted">Razem wierszy</small>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title text-danger"><i class="bi bi-exclamation-triangle"></i> Szczegóły błędów</h6>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <ul class="list-group list-group-flush small">
                            <?php foreach ($errors as $err): ?>
                                <li class="list-group-item text-danger py-1"><?= View::e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-2">
            <a href="<?= url('members') ?>" class="btn btn-primary">
                <i class="bi bi-people"></i> Przejdź do listy zawodników
            </a>
            <a href="<?= url('import') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-upload"></i> Importuj kolejny plik
            </a>
        </div>
    </div>
</div>
