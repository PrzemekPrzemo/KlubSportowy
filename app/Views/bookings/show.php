<?php use App\Helpers\View; ?>
<?php $b = $booking; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-calendar-check"></i> Rezerwacja #<?= (int)$b['id'] ?></h4>
    <div>
        <a href="<?= url('bookings') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kalendarz</a>
        <a href="<?= url('bookings/list') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-list"></i> Lista</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5><?= View::e($b['title']) ?></h5>
                <p class="text-muted mb-2">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:<?= View::e($b['resource_color']) ?>"></span>
                    <?= View::e($b['resource_name']) ?> (<?= View::e($b['resource_type']) ?>)
                </p>
                <table class="table table-sm">
                    <tr><th>Termin</th><td><?= View::e($b['start_at']) ?> &mdash; <?= View::e($b['end_at']) ?></td></tr>
                    <tr><th>Status</th><td><span class="badge bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning text-dark' : 'secondary') ?>"><?= View::e($b['status']) ?></span></td></tr>
                    <?php if (!empty($b['member_first'])): ?>
                        <tr><th>Zawodnik</th><td><?= View::e(trim(($b['member_first'] ?? '') . ' ' . ($b['member_last'] ?? ''))) ?> <?php if (!empty($b['member_email'])): ?><small class="text-muted">(<?= View::e($b['member_email']) ?>)</small><?php endif; ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($b['booked_by_name'])): ?>
                        <tr><th>Bookował</th><td><?= View::e($b['booked_by_name']) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($b['participants_count'])): ?>
                        <tr><th>Uczestników</th><td><?= (int)$b['participants_count'] ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($b['description'])): ?>
                        <tr><th>Opis</th><td><?= nl2br(View::e($b['description'])) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($b['notes'])): ?>
                        <tr><th>Notatki</th><td><?= nl2br(View::e($b['notes'])) ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($b['cancellation_reason'])): ?>
                        <tr><th>Powód anulowania</th><td><?= View::e($b['cancellation_reason']) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6>Akcje</h6>
                <?php if ($b['status'] === 'pending'): ?>
                    <form method="POST" action="<?= url('bookings/' . (int)$b['id'] . '/confirm') ?>" class="mb-2">
                        <?= csrf_field() ?>
                        <button class="btn btn-success btn-sm w-100"><i class="bi bi-check-circle"></i> Potwierdź</button>
                    </form>
                <?php endif; ?>
                <?php if (!in_array($b['status'], ['cancelled','completed'], true)): ?>
                    <form method="POST" action="<?= url('bookings/' . (int)$b['id'] . '/cancel') ?>"
                          onsubmit="return confirm('Anulować rezerwację?')">
                        <?= csrf_field() ?>
                        <input name="cancellation_reason" class="form-control form-control-sm mb-2" placeholder="Powód (opcjonalnie)">
                        <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-x-circle"></i> Anuluj</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
