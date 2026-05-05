<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-people text-primary me-2"></i>
        Wszyscy zawodnicy
    </h3>
    <a href="<?= url('members') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-list"></i> Klasyczna lista
    </a>
</div>

<form method="GET" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">Status członka</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">— wszyscy —</option>
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $statusFilter === $key ? 'selected' : '' ?>>
                        <?= View::e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small">Sport</label>
            <select name="sport_id" class="form-select form-select-sm">
                <option value="">— wszystkie sporty —</option>
                <?php foreach ($clubSports as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= (int)$sportFilter === (int)$s['id'] ? 'selected' : '' ?>>
                        <?= View::e($s['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary btn-sm w-100">
                <i class="bi bi-funnel"></i> Filtruj
            </button>
        </div>
        <div class="col-md-2">
            <a href="<?= url('members-all') ?>" class="btn btn-link btn-sm">wyczyść</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Zawodnik</th>
                    <th>Sporty</th>
                    <th class="text-center">Subskrypcje</th>
                    <th class="text-end">Saldo</th>
                    <th>Kontakt</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($members)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Brak zawodników pasujących do filtrów.</td></tr>
            <?php else: foreach ($members as $m):
                $balance      = (float)$m['outstanding_balance'];
                $overdueCount = (int)$m['overdue_count'];
            ?>
                <tr>
                    <td>
                        <strong><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></strong>
                        <small class="d-block text-muted">#<?= View::e($m['member_number'] ?? '?') ?></small>
                    </td>
                    <td>
                        <?php if (!empty($m['sports_list'])): ?>
                            <span class="badge bg-light text-secondary border">
                                <?= (int)$m['sports_count'] ?>
                            </span>
                            <small class="text-muted"><?= View::e($m['sports_list']) ?></small>
                        <?php else: ?>
                            <span class="text-muted small">brak sekcji</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ((int)$m['active_subscriptions'] > 0): ?>
                            <span class="badge bg-info"><?= (int)$m['active_subscriptions'] ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end font-monospace">
                        <?php if ($balance > 0): ?>
                            <span class="<?= $overdueCount > 0 ? 'text-danger fw-bold' : 'text-warning' ?>">
                                <?= format_money($balance) ?>
                                <?php if ($overdueCount > 0): ?>
                                    <small class="d-block">(<?= $overdueCount ?> przeterminowane)</small>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="text-success">0,00 zł</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted">
                        <?= View::e($m['email'] ?? '') ?>
                        <?php if (!empty($m['phone'])): ?>
                            <small class="d-block"><?= View::e($m['phone']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                            $cls = match($m['status']) {
                                'aktywny'    => 'success',
                                'zawieszony' => 'warning',
                                'wykreslony' => 'secondary',
                                'urlop'      => 'info',
                                default      => 'secondary',
                            };
                        ?>
                        <span class="badge bg-<?= $cls ?>"><?= View::e($statuses[$m['status']] ?? $m['status']) ?></span>
                    </td>
                    <td>
                        <a href="<?= url('members/' . (int)$m['id']) ?>"
                           class="btn btn-sm btn-outline-primary" title="Profil">
                            <i class="bi bi-person"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
