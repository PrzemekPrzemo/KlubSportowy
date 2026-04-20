<?php use App\Helpers\View; ?>

<?php
$polishDays = ['Niedziela','Poniedziałek','Wtorek','Środa','Czwartek','Piątek','Sobota'];
$polishMonths = ['','Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec',
                  'Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'];
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
        ?>
            <div class="card border-start border-4" style="border-color: <?= View::e($t['color'] ?? '#6c757d') ?> !important">
                <div class="card-body py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold"><?= View::e($t['sport_name']) ?></span>
                            <?php if (!empty($t['name'])): ?>
                                · <span class="text-muted"><?= View::e($t['name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-<?= $statusBadge[0] ?>"><?= $statusBadge[1] ?></span>
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
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>
