<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-tag text-primary me-2"></i>
        Stawki prowizji trenerów
    </h3>
    <div>
        <a href="<?= url('club/trainers/commissions') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Wróć
        </a>
        <a href="<?= url('club/trainers/commissions/rates/new') ?>" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Dodaj stawkę
        </a>
    </div>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<?php if (empty($trainers)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Brak trenerów w klubie. Najpierw nadaj rolę "trener" lub "instruktor"
        w <a href="<?= url('club/users') ?>">zarządzaniu użytkownikami</a>.
    </div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Trener</th>
                    <th>Sport</th>
                    <th>Typ opłaty</th>
                    <th class="text-end">Stawka</th>
                    <th>Ważność</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rates)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Brak stawek. Kliknij "Dodaj stawkę".</td></tr>
                <?php else: foreach ($rates as $r):
                    $isPercent = ($r['commission_type'] ?? '') === 'percent';
                ?>
                    <tr class="<?= empty($r['is_active']) ? 'text-muted' : '' ?>">
                        <td>
                            <strong><?= View::e($r['trainer_name'] ?? $r['trainer_username']) ?></strong>
                            <small class="text-muted d-block">@<?= View::e($r['trainer_username']) ?></small>
                        </td>
                        <td>
                            <?php if (!empty($r['sport_name'])): ?>
                                <span class="badge bg-info"><?= View::e($r['sport_name']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Wszystkie sporty</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= View::e($appliesTo[$r['applies_to']] ?? $r['applies_to']) ?>
                            </span>
                        </td>
                        <td class="text-end font-monospace fw-bold">
                            <?php if ($isPercent): ?>
                                <?= number_format((float)$r['value'], 2) ?>%
                            <?php else: ?>
                                <?= format_money($r['value']) ?>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?= View::e($r['valid_from']) ?> → <?= View::e($r['valid_to'] ?? '∞') ?>
                        </td>
                        <td>
                            <?php if (!empty($r['is_active'])): ?>
                                <span class="badge bg-success">aktywna</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">nieaktywna</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('club/trainers/commissions/rates/' . (int)$r['id'] . '/edit') ?>"
                               class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="<?= url('club/trainers/commissions/rates/' . (int)$r['id'] . '/toggle') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-toggle-on"></i></button>
                            </form>
                            <form method="POST" action="<?= url('club/trainers/commissions/rates/' . (int)$r['id'] . '/delete') ?>" class="d-inline"
                                  onsubmit="return confirm('Usunąć stawkę?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
