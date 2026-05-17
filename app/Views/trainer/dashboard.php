<?php
use App\Helpers\View;
?>
<h2 class="mb-3"><i class="bi bi-speedometer2"></i> Panel trenera</h2>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-calendar-day"></i> Dzisiejsze treningi
            </div>
            <div class="card-body p-0">
                <?php if (empty($todayTrainings)): ?>
                    <p class="text-muted p-3 mb-0">Nie masz dzisiaj treningow.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                    <?php foreach ($todayTrainings as $t): ?>
                        <?php
                        $total = (int)($t['total_attendees'] ?? 0);
                        $marked = (int)($t['marked'] ?? 0);
                        $allMarked = $total > 0 && $marked >= $total;
                        ?>
                        <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center">
                            <div class="me-2">
                                <strong><?= View::e($t['name']) ?></strong>
                                <small class="text-muted d-block">
                                    <i class="bi bi-clock"></i>
                                    <?= View::e(substr((string)$t['start_time'], 11, 5)) ?>
                                    <?php if (!empty($t['end_time'])): ?>
                                        – <?= View::e(substr((string)$t['end_time'], 11, 5)) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($t['location'])): ?>
                                        · <i class="bi bi-geo-alt"></i> <?= View::e($t['location']) ?>
                                    <?php endif; ?>
                                </small>
                                <small class="text-muted">
                                    <?= $marked ?>/<?= $total ?> wpisanych
                                </small>
                            </div>
                            <a href="<?= url('trainer/training/' . (int)$t['id'] . '/attendance') ?>"
                               class="btn btn-sm <?= $allMarked ? 'btn-outline-success' : 'btn-warning' ?>">
                                <i class="bi bi-check2-square"></i>
                                <?= $allMarked ? 'Edytuj obecnosc' : 'Wpisz obecnosc' ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-calendar-week"></i> Nadchodzace w tygodniu
            </div>
            <div class="card-body p-0">
                <?php if (empty($upcomingWeek)): ?>
                    <p class="text-muted p-3 mb-0">Brak planowanych treningow w nadchodzacych 7 dniach.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                    <?php foreach ($upcomingWeek as $t): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted"><?= View::e($t['start_time']) ?></small>
                                <strong class="d-block"><?= View::e($t['name']) ?></strong>
                            </div>
                            <a href="<?= url('trainings/' . (int)$t['id']) ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($crossClubToday)): ?>
    <div class="col-12 col-lg-6">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <i class="bi bi-shuffle"></i> Dzisiaj — inne kluby
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                <?php foreach ($crossClubToday as $t): ?>
                    <li class="list-group-item">
                        <span class="badge bg-secondary"><?= View::e($t['club_name'] ?? ('#' . $t['club_id'])) ?></span>
                        <strong><?= View::e($t['name']) ?></strong>
                        <small class="text-muted d-block"><?= View::e($t['start_time']) ?></small>
                        <small class="text-muted">Przelacz klub, aby wpisac obecnosc.</small>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history"></i> Ostatnio wpisane obecnosci
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent)): ?>
                    <p class="text-muted p-3 mb-0">Brak ostatnich wpisow.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush small">
                    <?php foreach ($recent as $r): ?>
                        <li class="list-group-item">
                            <code><?= View::e($r['occurred_at']) ?></code>
                            <small class="d-block text-muted"><?= View::e((string)($r['notes'] ?? '')) ?></small>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
