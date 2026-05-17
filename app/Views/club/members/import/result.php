<?php
/**
 * Wynik importu członków (sekretariat).
 *
 * @var int   $imported
 * @var int   $skipped
 * @var array $errors
 */
use App\Helpers\View;
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card mb-3">
            <div class="card-body text-center py-4">
                <?php if (($imported ?? 0) > 0): ?>
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                <?php else: ?>
                    <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                <?php endif; ?>
                <h4 class="mt-3">Import członków zakończony</h4>
                <small class="text-muted">Szczegóły operacji zostały zapisane w dzienniku dostępu (audit log).</small>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h2 class="text-success mb-0"><?= (int)($imported ?? 0) ?></h2>
                        <small class="text-muted">Dodanych</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h2 class="text-warning mb-0"><?= (int)($skipped ?? 0) ?></h2>
                        <small class="text-muted">Pominiętych (duplikaty / błędy)</small>
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
                    <h6 class="card-title text-danger"><i class="bi bi-exclamation-triangle"></i> Szczegóły</h6>
                    <div style="max-height: 360px; overflow-y: auto;">
                        <ul class="list-group list-group-flush small">
                            <?php foreach ($errors as $err): ?>
                                <li class="list-group-item text-danger py-1"><?= View::e((string)$err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-2">
            <a href="<?= url('members') ?>" class="btn btn-primary">
                <i class="bi bi-people"></i> Lista członków
            </a>
            <a href="<?= url('club/members/import') ?>" class="btn btn-outline-primary">
                <i class="bi bi-upload"></i> Importuj kolejny plik
            </a>
            <a href="<?= url('sekretariat') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-building"></i> Dashboard biura
            </a>
        </div>
    </div>
</div>
