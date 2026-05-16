<?php
use App\Helpers\View;
$weekdays = [1=>'Pon', 2=>'Wt', 3=>'Sr', 4=>'Czw', 5=>'Pt', 6=>'Sob', 7=>'Nd'];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><i class="bi bi-calendar2-week"></i> Plany trenerow</h2>
</div>

<?php if (empty($trainers)): ?>
    <div class="alert alert-info">Brak trenerow w tym klubie. Dodaj uzytkownika z rola "trener" lub "instruktor".</div>
<?php else: ?>
    <?php foreach ($trainers as $t): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <strong><?= View::e($t['full_name'] ?? $t['username']) ?></strong>
                    <small class="text-muted ms-2"><?= View::e($t['role'] ?? '') ?></small>
                </div>
                <div class="btn-group btn-group-sm">
                    <a href="<?= url('club/trainer-schedule/' . (int)$t['id'] . '/edit') ?>" class="btn btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edytuj dostepnosc
                    </a>
                    <a href="<?= url('club/trainer-schedule/' . (int)$t['id'] . '/leaves/add') ?>" class="btn btn-outline-warning">
                        <i class="bi bi-airplane"></i> Dodaj urlop
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">
                        <h6 class="text-muted text-uppercase small">Dostepnosc (per weekday)</h6>
                        <?php if (empty($t['availability'])): ?>
                            <p class="text-muted small">Brak skonfigurowanej dostepnosci.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr>
                                    <?php foreach ($weekdays as $d): ?><th class="text-center"><?= $d ?></th><?php endforeach; ?>
                                </tr></thead>
                                <tbody><tr>
                                <?php foreach ($weekdays as $wd => $lbl): ?>
                                    <td style="vertical-align:top; min-width:90px;">
                                    <?php foreach ($t['availability'] as $a): if ((int)$a['weekday'] === $wd): ?>
                                        <div class="badge bg-success text-white mb-1 d-block" style="white-space:normal;">
                                            <?= substr((string)$a['time_start'], 0, 5) ?>-<?= substr((string)$a['time_end'], 0, 5) ?>
                                            <?php if (empty($a['club_id'])): ?>
                                                <i class="bi bi-globe" title="Globalna"></i>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; endforeach; ?>
                                    </td>
                                <?php endforeach; ?>
                                </tr></tbody>
                            </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-5">
                        <h6 class="text-muted text-uppercase small">Najblizsze urlopy</h6>
                        <?php if (empty($t['leaves'])): ?>
                            <p class="text-muted small">Brak urlopow.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($t['leaves'] as $l): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <span>
                                            <span class="badge bg-warning text-dark"><?= View::e($l['leave_type']) ?></span>
                                            <?= View::e($l['date_from']) ?> – <?= View::e($l['date_to']) ?>
                                            <?php if (!empty($l['reason'])): ?>
                                                <small class="text-muted d-block"><?= View::e($l['reason']) ?></small>
                                            <?php endif; ?>
                                        </span>
                                        <form method="POST" action="<?= url('club/trainer-schedule/leaves/' . (int)$l['id'] . '/delete') ?>" class="m-0"
                                              onsubmit="return confirm('Usunac urlop?');">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($conflicts)): ?>
    <div class="card mt-4">
        <div class="card-header bg-danger text-white">
            <i class="bi bi-exclamation-triangle"></i> Wykryte konflikty (nierozwiazane)
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead><tr>
                    <th>Trener</th><th>Typ</th><th>Czas</th><th>Trening</th><th>Wykryto</th>
                </tr></thead>
                <tbody>
                <?php foreach ($conflicts as $c): ?>
                    <tr>
                        <td><?= View::e($c['trainer_name'] ?? '#' . $c['user_id']) ?></td>
                        <td><span class="badge bg-danger"><?= View::e($c['conflict_type']) ?></span></td>
                        <td><small><?= View::e($c['starts_at']) ?> – <?= View::e($c['ends_at']) ?></small></td>
                        <td><?= View::e($c['training_name'] ?? '—') ?></td>
                        <td><small><?= View::e($c['detected_at']) ?></small></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
