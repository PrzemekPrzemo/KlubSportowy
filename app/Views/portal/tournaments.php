<?php use App\Helpers\View; ?>

<?php if (empty($tournaments)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>Brak zaplanowanych zawodów i turniejów.
</div>
<?php else: ?>
<div class="d-flex flex-column gap-3">
<?php foreach ($tournaments as $t):
    $statusBadge = match($t['status']) {
        'planowany'  => ['secondary', 'Planowany'],
        'otwarty'    => ['success',   'Rejestracja otwarta'],
        'w_trakcie'  => ['primary',   'W trakcie'],
        'zakonczony' => ['dark',      'Zakończony'],
        default      => ['secondary', $t['status']],
    };
    $myStatus = $t['my_status'] ?? null;
    $canRegister   = $t['status'] === 'otwarty' && $myStatus === null;
    $canWithdraw   = $t['status'] === 'otwarty' && $myStatus !== null && $myStatus !== 'wycofany';
?>
    <div class="card <?= $t['status'] === 'otwarty' ? 'border-success' : '' ?>">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                <div>
                    <h6 class="fw-semibold mb-0"><?= View::e($t['name']) ?></h6>
                    <?php if (!empty($t['location'])): ?>
                        <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i><?= View::e($t['location']) ?></div>
                    <?php endif; ?>
                </div>
                <span class="badge bg-<?= $statusBadge[0] ?>"><?= $statusBadge[1] ?></span>
            </div>

            <div class="row g-2 mb-3 small text-muted">
                <?php if (!empty($t['start_date'])): ?>
                <div class="col-auto"><i class="bi bi-calendar me-1"></i>
                    <?= date('d.m.Y', strtotime($t['start_date'])) ?>
                    <?php if (!empty($t['end_date']) && $t['end_date'] !== $t['start_date']): ?>
                        – <?= date('d.m.Y', strtotime($t['end_date'])) ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($t['max_participants'])): ?>
                <div class="col-auto"><i class="bi bi-people me-1"></i>Max <?= (int)$t['max_participants'] ?> uczestników</div>
                <?php endif; ?>
                <?php if (!empty($t['registration_deadline'])): ?>
                <div class="col-auto"><i class="bi bi-clock me-1"></i>Zgłoszenia do: <?= date('d.m.Y', strtotime($t['registration_deadline'])) ?></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($t['description'])): ?>
                <p class="small mb-3"><?= nl2br(View::e($t['description'])) ?></p>
            <?php endif; ?>

            <div class="d-flex gap-2 align-items-center flex-wrap">
                <?php if ($myStatus !== null && $myStatus !== 'wycofany'): ?>
                    <span class="badge bg-info text-dark">
                        <i class="bi bi-check-circle me-1"></i>
                        <?= match($myStatus) { 'zgłoszony' => 'Zgłoszony/a', 'potwierdzony' => 'Potwierdzony/a', default => View::e($myStatus) } ?>
                    </span>
                <?php endif; ?>

                <?php if ($canRegister): ?>
                    <form method="POST" action="<?= url('portal/tournaments/' . (int)$t['id'] . '/register') ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-success btn-sm">
                            <i class="bi bi-person-plus me-1"></i>Zgłoś udział
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($canWithdraw): ?>
                    <form method="POST" action="<?= url('portal/tournaments/' . (int)$t['id'] . '/withdraw') ?>"
                          onsubmit="return confirm('Wycofać zgłoszenie z tego turnieju?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-person-dash me-1"></i>Wycofaj zgłoszenie
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
