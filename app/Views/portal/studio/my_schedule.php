<?php use App\Helpers\Csrf; use App\Helpers\View; ?>

<div class="container-md py-3">
    <h4 class="mb-3">
        <i class="bi bi-calendar-check text-primary me-2"></i>Moje nadchodzące zajęcia
    </h4>

    <div class="mb-3 d-flex gap-2 flex-wrap">
        <a href="<?= url('portal/studio/catalog') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-grid-3x3-gap"></i> Katalog klas
        </a>
        <a href="<?= url('portal/studio/my-passes') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-card-checklist"></i> Moje karnety
        </a>
        <a href="<?= url('portal/studio/buy-pass') ?>" class="btn btn-outline-success btn-sm">
            <i class="bi bi-cart-plus"></i> Kup karnet
        </a>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Nie masz nadchodzących zapisów.
            <a href="<?= url('portal/studio/catalog') ?>">Zobacz katalog klas</a>.
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Klasa</th>
                            <th>Sport</th>
                            <th>Sala</th>
                            <th>Status</th>
                            <th class="text-end">Akcja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td>
                                    <strong><?= View::e($b['class_date']) ?></strong>
                                    <small class="d-block text-muted"><?= View::e(substr((string)$b['time_start'], 0, 5)) ?></small>
                                </td>
                                <td>
                                    <strong><?= View::e($b['class_name']) ?></strong>
                                    <small class="d-block text-muted"><?= (int)$b['duration_min'] ?> min</small>
                                </td>
                                <td><span class="badge bg-secondary"><?= View::e($b['sport_key']) ?></span></td>
                                <td><small><?= View::e($b['room'] ?? '—') ?></small></td>
                                <td>
                                    <?php if ($b['status'] === 'waitlist'): ?>
                                        <span class="badge bg-warning text-dark">Waitlist</span>
                                    <?php elseif ($b['status'] === 'attended'): ?>
                                        <span class="badge bg-success">Obecność</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Zapisany</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if (in_array($b['status'], ['booked', 'waitlist'], true)): ?>
                                        <form action="<?= url('portal/studio/cancel/' . (int)$b['id']) ?>"
                                              method="POST" onsubmit="return confirm('Anulować rezerwację?');">
                                            <?= Csrf::field() ?>
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x-lg"></i> Anuluj
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
