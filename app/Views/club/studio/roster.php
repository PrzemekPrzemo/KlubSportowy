<?php use App\Helpers\Csrf; use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="bi bi-people text-primary me-2"></i>
        <?= View::e($schedule['name']) ?>
        <small class="text-muted">— <?= View::e($date) ?></small>
    </h4>
    <div>
        <span class="badge bg-info">
            Zapisanych: <?= (int)$bookedCount ?> / <?= (int)$schedule['max_capacity'] ?>
        </span>
        <a href="<?= url('club/studio/' . $sport . '/schedules') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Klasy
        </a>
    </div>
</div>

<form method="GET" class="mb-3 d-flex gap-2 align-items-center">
    <label class="form-label mb-0 small">Data:</label>
    <input type="date" name="d" value="<?= View::e($date) ?>" class="form-control form-control-sm" style="width:160px;"
           onchange="window.location.href='<?= url('club/studio/' . $sport . '/roster/' . (int)$schedule['id'] . '/') ?>' + this.value">
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Zawodnik</th>
                    <th>Karnet</th>
                    <th>Status</th>
                    <th>Zapis</th>
                    <th class="text-end">Akcja</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr><td colspan="6" class="text-muted text-center py-4">Brak zapisanych.</td></tr>
                <?php endif; ?>
                <?php foreach ($bookings as $i => $b): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <strong><?= View::e($b['first_name'] . ' ' . $b['last_name']) ?></strong>
                            <?php if (!empty($b['member_number'])): ?>
                                <small class="text-muted d-block">#<?= View::e($b['member_number']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><small class="font-monospace">#<?= (int)$b['pass_id'] ?></small></td>
                        <td>
                            <?php
                            $statusBadges = [
                                'booked'    => ['bg-primary',   '✓ Zapisany'],
                                'attended'  => ['bg-success',   '✓ Obecny'],
                                'waitlist'  => ['bg-warning text-dark', '⏳ Waitlist'],
                                'no_show'   => ['bg-danger',    '✗ Nieobecny'],
                                'cancelled' => ['bg-secondary', '✗ Anulowane'],
                            ];
                            [$c, $label] = $statusBadges[$b['status']] ?? ['bg-light text-dark', $b['status']];
                            ?>
                            <span class="badge <?= $c ?>"><?= View::e($label) ?></span>
                        </td>
                        <td><small class="text-muted"><?= View::e(substr((string)$b['booked_at'], 0, 16)) ?></small></td>
                        <td class="text-end">
                            <?php if (in_array($b['status'], ['booked', 'attended'], true)): ?>
                                <?php if ($b['status'] !== 'attended'): ?>
                                    <form action="<?= url('club/studio/' . $sport . '/roster/attend/' . (int)$b['id']) ?>"
                                          method="POST" class="d-inline">
                                        <?= Csrf::field() ?>
                                        <button class="btn btn-sm btn-success"><i class="bi bi-check-circle"></i> Check-in</button>
                                    </form>
                                <?php endif; ?>
                                <form action="<?= url('club/studio/' . $sport . '/roster/no-show/' . (int)$b['id']) ?>"
                                      method="POST" class="d-inline">
                                    <?= Csrf::field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> No-show</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
