<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Prosby GDPR (RODO)</h4>
        <small class="text-muted">Self-service prosby od czlonkow: eksport (art. 20) i usuniecie (art. 17).</small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <label class="small me-2">Filtr statusu:</label>
            <select name="status" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                <option value="">Wszystkie</option>
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= View::e($key) ?>" <?= ($filter ?? '') === $key ? 'selected' : '' ?>>
                        <?= View::e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($filter)): ?>
                <a href="<?= url('admin/gdpr') ?>" class="btn btn-sm btn-outline-secondary">Wyczysc</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($requests)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox fs-3"></i>
                <div class="small mt-2">Brak prosb GDPR<?= !empty($filter) ? ' (dla wybranego statusu)' : '' ?>.</div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Czlonek</th>
                            <th>Typ</th>
                            <th>Status</th>
                            <th>Zlozono</th>
                            <th>Potwierdzono</th>
                            <th>Zrealizowano</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>#<?= (int)$r['id'] ?></td>
                            <td>
                                <strong><?= View::e($r['first_name'] . ' ' . $r['last_name']) ?></strong>
                                <?php if (!empty($r['member_number'])): ?>
                                    <span class="text-muted small">(#<?= View::e($r['member_number']) ?>)</span>
                                <?php endif; ?>
                                <?php if (!empty($r['email'])): ?>
                                    <div class="small text-muted"><?= View::e($r['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark"><?= View::e($types[$r['request_type']] ?? $r['request_type']) ?></span></td>
                            <td>
                                <?php
                                $statusClass = match($r['status']) {
                                    'completed'   => 'success',
                                    'in_progress' => 'info',
                                    'pending'     => 'warning',
                                    'rejected'    => 'danger',
                                    default       => 'secondary',
                                };
                                ?>
                                <span class="badge bg-<?= $statusClass ?>"><?= View::e($statuses[$r['status']] ?? $r['status']) ?></span>
                            </td>
                            <td class="small"><?= View::e($r['requested_at']) ?></td>
                            <td class="small"><?= View::e($r['confirmed_at'] ?? '—') ?></td>
                            <td class="small"><?= View::e($r['processed_at'] ?? '—') ?></td>
                            <td>
                                <a href="<?= url('admin/gdpr/' . (int)$r['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Szczegoly
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-info mt-3 small">
    <i class="bi bi-info-circle me-2"></i>
    Prosby <code>delete</code> i <code>export</code> sa realizowane automatycznie po kliknieciu linku
    potwierdzajacego przez czlonka. Pozostale typy (rectify, restrict, object) wymagaja recznego dzialania.
</div>
