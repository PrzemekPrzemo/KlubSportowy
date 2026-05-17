<?php
use App\Helpers\View;
$weekdays = [1=>'Pon', 2=>'Wt', 3=>'Sr', 4=>'Czw', 5=>'Pt', 6=>'Sob', 7=>'Nd'];
?>
<h2 class="mb-3"><i class="bi bi-person-badge"></i> Moja dostepnosc</h2>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><strong>Dostepnosc cykliczna</strong></div>
            <div class="card-body">
                <?php if (empty($availability)): ?>
                    <p class="text-muted">Twoja dostepnosc nie zostala jeszcze skonfigurowana. Skontaktuj sie z zarządem klubu.</p>
                <?php else: ?>
                    <table class="table table-sm table-bordered mb-0">
                        <thead><tr>
                            <?php foreach ($weekdays as $d): ?><th class="text-center"><?= $d ?></th><?php endforeach; ?>
                        </tr></thead>
                        <tbody><tr>
                        <?php foreach ($weekdays as $wd => $lbl): ?>
                            <td style="vertical-align:top; min-width:90px;">
                            <?php foreach ($availability as $a): if ((int)$a['weekday'] === $wd): ?>
                                <div class="badge bg-success text-white mb-1 d-block">
                                    <?= substr((string)$a['time_start'], 0, 5) ?>-<?= substr((string)$a['time_end'], 0, 5) ?>
                                </div>
                            <?php endif; endforeach; ?>
                            </td>
                        <?php endforeach; ?>
                        </tr></tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Moje treningi</strong>
                <a href="<?= url('trainer/dashboard') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-speedometer2"></i> Panel trenera
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($upcoming)): ?>
                    <p class="text-muted p-3 mb-0">Brak zaplanowanych treningow.</p>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Data</th><th>Nazwa</th><th>Klub</th><th>Status</th><th class="text-end">Akcje</th></tr></thead>
                        <tbody>
                            <?php
                            $today = (new DateTimeImmutable('today'))->format('Y-m-d');
                            foreach ($upcoming as $t):
                                $startDate = substr((string)$t['start_time'], 0, 10);
                                $total  = (int)($t['total_attendees'] ?? 0);
                                $marked = (int)($t['marked_attendees'] ?? 0);
                                $submitted = $total > 0 && $marked >= $total;

                                if ($startDate < $today) {
                                    if ($submitted) {
                                        $badgeClass = 'bg-success';
                                        $badgeText  = 'wpisane';
                                        $rowClass   = '';
                                    } else {
                                        $badgeClass = 'bg-danger';
                                        $badgeText  = 'WYMAGA WPISU';
                                        $rowClass   = 'table-danger';
                                    }
                                } elseif ($startDate === $today) {
                                    $badgeClass = 'bg-warning text-dark';
                                    $badgeText  = 'do wpisania po treningu';
                                    $rowClass   = 'table-warning';
                                } else {
                                    $badgeClass = 'bg-secondary';
                                    $badgeText  = 'planowany';
                                    $rowClass   = '';
                                }
                            ?>
                                <tr class="<?= $rowClass ?>">
                                    <td><small><?= View::e($t['start_time']) ?></small></td>
                                    <td><a href="<?= url('trainings/' . (int)$t['id']) ?>"><?= View::e($t['name']) ?></a></td>
                                    <td><small><?= View::e($t['club_name'] ?? '#' . $t['club_id']) ?></small></td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                                        <?php if ($total > 0): ?>
                                            <small class="d-block text-muted"><?= $marked ?>/<?= $total ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= url('trainer/training/' . (int)$t['id'] . '/attendance') ?>"
                                           class="btn btn-sm <?= $submitted ? 'btn-outline-success' : 'btn-primary' ?>">
                                            <i class="bi bi-check2-square"></i> Wpisz obecnosc
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><strong>Najblizsze urlopy</strong></div>
            <div class="card-body">
                <?php if (empty($leaves)): ?>
                    <p class="text-muted mb-0">Brak zaplanowanych urlopow.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($leaves as $l): ?>
                            <li class="list-group-item px-0">
                                <span class="badge bg-warning text-dark"><?= View::e($l['leave_type']) ?></span>
                                <strong><?= View::e($l['date_from']) ?> – <?= View::e($l['date_to']) ?></strong>
                                <?php if (!empty($l['reason'])): ?>
                                    <small class="text-muted d-block"><?= View::e($l['reason']) ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($conflicts)): ?>
            <div class="card mt-3 border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="bi bi-exclamation-triangle"></i> Konflikty wymagajace uwagi
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($conflicts as $c): ?>
                            <li class="list-group-item">
                                <small class="text-muted"><?= View::e($c['starts_at']) ?>:</small>
                                <span class="badge bg-danger"><?= View::e($c['conflict_type']) ?></span>
                                <?= View::e($c['training_name'] ?? '') ?>
                                <?php if (!empty($c['details'])): ?>
                                    <small class="d-block text-muted"><?= View::e($c['details']) ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
