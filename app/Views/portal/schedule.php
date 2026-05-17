<?php use App\Helpers\View; ?>

<?php
$polishDays = ['Niedziela','Poniedziałek','Wtorek','Środa','Czwartek','Piątek','Sobota'];
$polishMonths = ['','Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec',
                  'Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'];
$activeStatuses = ['signed_up','zapisany','obecny','attended'];
$waitlistStatuses = ['waitlist'];
?>

<?php if (empty($memberSports)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>Nie jesteś przypisany do żadnej sekcji sportowej.
</div>
<?php else: ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2">
        <?php if ($week > 0): ?>
            <a href="?week=<?= $week - 1 ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-chevron-left"></i> Poprzedni tydzień
            </a>
        <?php endif; ?>
        <a href="?week=<?= $week + 1 ?>" class="btn btn-outline-secondary btn-sm">
            Następny tydzień <i class="bi bi-chevron-right"></i>
        </a>
    </div>
    <div class="text-muted small">
        Twoje sekcje:
        <?php foreach ($memberSports as $sp): ?>
            <span class="badge bg-secondary me-1"><?= View::e($sp['sport_name']) ?></span>
        <?php endforeach; ?>
    </div>
</div>

<?php if (empty($trainings)): ?>
<div class="alert alert-secondary">
    <i class="bi bi-calendar-x me-2"></i>Brak zaplanowanych treningów w tym okresie.
</div>
<?php else: ?>

<?php
$byDay = [];
foreach ($trainings as $t) {
    $day = date('Y-m-d', strtotime($t['start_time']));
    $byDay[$day][] = $t;
}
ksort($byDay);
?>

<div class="d-flex flex-column gap-3">
<?php foreach ($byDay as $date => $dayTrainings):
    $dt = new \DateTime($date);
    $dayName = $polishDays[(int)$dt->format('w')];
    $dateLabel = $dt->format('j') . ' ' . $polishMonths[(int)$dt->format('n')] . ' ' . $dt->format('Y');
?>
    <div>
        <div class="text-muted small fw-semibold mb-2 border-bottom pb-1">
            <?= $dayName ?>, <?= $dateLabel ?>
        </div>
        <div class="d-flex flex-column gap-2">
        <?php foreach ($dayTrainings as $t):
            $statusBadge = match($t['status']) {
                'zaplanowany' => ['primary', 'Zaplanowany'],
                'w_trakcie'   => ['success', 'W trakcie'],
                'zakonczony'  => ['secondary', 'Zakończony'],
                default       => ['secondary', $t['status']],
            };
            $myStatus      = $t['my_status'] ?? null;
            $isSignedUp    = in_array((string)$myStatus, $activeStatuses, true);
            $isWaitlisted  = in_array((string)$myStatus, $waitlistStatuses, true);
            $isCancelled   = (string)$myStatus === 'cancelled' || $myStatus === 'wypisany';
            $signupEnabled = (int)($t['signup_enabled'] ?? 1) === 1;
            $waitlistOn    = (int)($t['waitlist_enabled'] ?? 1) === 1;
            $beforeDeadline = !empty($t['before_deadline']);
            $max           = $t['max_participants'] !== null ? (int)$t['max_participants'] : 0;
            $signedCount   = (int)($t['signed_count'] ?? 0);
            $waitlistCount = (int)($t['waitlist_count'] ?? 0);
            $isFull        = $max > 0 && $signedCount >= $max;
        ?>
            <div class="card border-start border-4" style="border-color: <?= View::e($t['color'] ?? '#6c757d') ?> !important">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="fw-semibold"><?= View::e($t['sport_name']) ?></span>
                            <?php if (!empty($t['name'])): ?>
                                · <span class="text-muted"><?= View::e($t['name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-1 align-items-center">
                            <?php if ($isSignedUp): ?>
                                <span class="badge bg-success" title="Jesteś zapisany"><i class="bi bi-check-circle me-1"></i>Zapisany</span>
                            <?php elseif ($isWaitlisted): ?>
                                <span class="badge bg-warning text-dark" title="Lista rezerwowa"><i class="bi bi-hourglass-split me-1"></i>Waitlist</span>
                            <?php endif; ?>
                            <span class="badge bg-<?= $statusBadge[0] ?>"><?= $statusBadge[1] ?></span>
                        </div>
                    </div>
                    <div class="small text-muted mt-1">
                        <i class="bi bi-clock me-1"></i>
                        <?= date('H:i', strtotime($t['start_time'])) ?>
                        <?php if (!empty($t['end_time'])): ?>
                            – <?= date('H:i', strtotime($t['end_time'])) ?>
                        <?php endif; ?>
                        <?php if (!empty($t['location'])): ?>
                            &nbsp;·&nbsp;<i class="bi bi-geo-alt me-1"></i><?= View::e($t['location']) ?>
                        <?php endif; ?>
                        <?php if (!empty($t['instructor_name'])): ?>
                            &nbsp;·&nbsp;<i class="bi bi-person me-1"></i><?= View::e($t['instructor_name']) ?>
                        <?php endif; ?>
                        <?php if ($max > 0): ?>
                            &nbsp;·&nbsp;<i class="bi bi-people me-1"></i>
                            <?= $signedCount ?>/<?= $max ?>
                            <?php if ($waitlistCount > 0): ?>
                                <span class="text-warning">(+<?= $waitlistCount ?> waitlist)</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($signupEnabled && in_array($t['status'], ['zaplanowany','w_trakcie'], true)): ?>
                        <div class="mt-2 d-flex gap-2 align-items-center">
                            <?php if ($isSignedUp || $isWaitlisted): ?>
                                <?php if ($beforeDeadline): ?>
                                    <form method="POST" action="<?= url('portal/training/' . (int)$t['id'] . '/cancel') ?>"
                                          onsubmit="return confirm('Na pewno wypisać się z tego treningu?')" class="d-inline-flex gap-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="week" value="<?= (int)$week ?>">
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x-circle"></i> Wypisz się
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary" disabled
                                            title="Deadline minął — skontaktuj się z trenerem">
                                        <i class="bi bi-lock"></i> Wypisanie niedostępne
                                    </button>
                                <?php endif; ?>
                            <?php elseif ($beforeDeadline): ?>
                                <?php if (!$isFull): ?>
                                    <form method="POST" action="<?= url('portal/training/' . (int)$t['id'] . '/signup') ?>" class="d-inline-flex">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="week" value="<?= (int)$week ?>">
                                        <button class="btn btn-sm btn-primary">
                                            <i class="bi bi-plus-circle"></i> Zapisz się
                                        </button>
                                    </form>
                                <?php elseif ($waitlistOn): ?>
                                    <form method="POST" action="<?= url('portal/training/' . (int)$t['id'] . '/signup') ?>" class="d-inline-flex">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="week" value="<?= (int)$week ?>">
                                        <button class="btn btn-sm btn-warning text-dark">
                                            <i class="bi bi-hourglass-split"></i> Dołącz do listy rezerwowej
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Trening pełny</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-secondary" disabled
                                        title="Deadline minął — skontaktuj się z trenerem">
                                    <i class="bi bi-lock"></i> Zapisy zamknięte
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php elseif (!$signupEnabled): ?>
                        <div class="mt-1 small text-muted">
                            <i class="bi bi-info-circle"></i> Zapisy wyłączone — kontakt z trenerem.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>
