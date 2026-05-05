<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-percent text-primary me-2"></i>
        Zniżki i rabaty
    </h3>
    <a href="<?= url('fees/discounts/new') ?>" class="btn btn-success">
        <i class="bi bi-plus-circle me-1"></i> Dodaj zniżkę
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kod</th>
                    <th>Nazwa</th>
                    <th>Typ</th>
                    <th class="text-end">Wartość</th>
                    <th>Stackable</th>
                    <th>Ważność</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($discounts)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">
                    Brak zniżek. <a href="<?= url('fees/discounts/new') ?>">Dodaj pierwszą</a>.
                </td></tr>
            <?php else: foreach ($discounts as $d):
                $isPercent = ($d['discount_type'] ?? '') === 'percent';
            ?>
                <tr class="<?= empty($d['is_active']) ? 'text-muted' : '' ?>">
                    <td><code><?= View::e($d['code']) ?></code></td>
                    <td>
                        <strong><?= View::e($d['name']) ?></strong>
                        <?php if (!empty($d['description'])): ?>
                            <small class="d-block text-muted"><?= View::e(mb_strimwidth($d['description'], 0, 80, '…')) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $isPercent ? 'info' : 'warning' ?>">
                            <?= View::e($types[$d['discount_type']] ?? $d['discount_type']) ?>
                        </span>
                    </td>
                    <td class="text-end font-monospace fw-bold">
                        <?php if ($isPercent): ?>
                            -<?= number_format((float)$d['value'], 2) ?>%
                        <?php else: ?>
                            -<?= format_money($d['value']) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($d['is_stackable'])): ?>
                            <i class="bi bi-link-45deg text-success" title="Można łączyć z innymi"></i> Tak
                        <?php else: ?>
                            <i class="bi bi-x-circle text-muted" title="Nie łączy się z innymi"></i> Nie
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted">
                        <?php if (!empty($d['valid_from']) || !empty($d['valid_to'])): ?>
                            <?= View::e($d['valid_from'] ?? '∞') ?> → <?= View::e($d['valid_to'] ?? '∞') ?>
                        <?php else: ?>
                            <span class="text-muted">bez ograniczeń</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($d['is_active'])): ?>
                            <span class="badge bg-success">aktywna</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">nieaktywna</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= url('fees/discounts/' . (int)$d['id'] . '/edit') ?>"
                               class="btn btn-sm btn-outline-primary" title="Edytuj">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="<?= url('fees/discounts/' . (int)$d['id'] . '/toggle') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-<?= !empty($d['is_active']) ? 'warning' : 'success' ?>"
                                        title="<?= !empty($d['is_active']) ? 'Dezaktywuj' : 'Aktywuj' ?>">
                                    <i class="bi bi-<?= !empty($d['is_active']) ? 'pause' : 'play' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= url('fees/discounts/' . (int)$d['id'] . '/delete') ?>"
                                  onsubmit="return confirm('Usunąć zniżkę? Operacja nieodwracalna.')" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" title="Usuń">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-info mt-3 small">
    <strong><i class="bi bi-lightbulb me-1"></i> Wskazówki:</strong>
    <ul class="mb-0 mt-2">
        <li><strong>Stackable</strong> = zniżka łączy się z innymi (np. junior + multi-sport).</li>
        <li><strong>Non-stackable</strong> = po jej zastosowaniu inne zniżki są pomijane (np. stypendium 100%).</li>
        <li><strong>Warunki JSON</strong> (opcjonalne, w edycji): <code>{"min_active_sports": 2}</code> — multi-sport, <code>{"age_max": 18}</code> — junior.</li>
    </ul>
</div>
