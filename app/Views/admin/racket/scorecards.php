<?php
use App\Helpers\View;
$key = $sportKey ?? '';
$isGolf = $key === 'golf';
?>
<h4 class="mb-3">
    <i class="bi bi-check2-square me-2"></i>
    <?= View::e($title ?? 'Weryfikacja scorecardów') ?>
</h4>

<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#pending">
            Oczekujące <span class="badge bg-warning text-dark"><?= count($pending ?? []) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#verified">
            Zweryfikowane <span class="badge bg-success"><?= count($verified ?? []) ?></span>
        </a>
    </li>
</ul>

<div class="tab-content">
    <?php foreach (['pending' => $pending ?? [], 'verified' => $verified ?? []] as $tab => $rows): ?>
    <div class="tab-pane fade <?= $tab === 'pending' ? 'show active' : '' ?>" id="<?= $tab ?>">
        <div class="card"><div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Data</th><th>Zawodnik</th>
                        <?php if ($isGolf): ?>
                            <th>Pole</th><th>Strokes</th><th>To Par</th><th>HCP</th>
                        <?php else: ?>
                            <th>Dystans</th><th>Endy</th><th>Total</th><th>10/X</th>
                        <?php endif; ?>
                        <th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Brak rekordów.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?= View::e($isGolf ? $r['played_at'] : $r['shot_at']) ?></td>
                        <td><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?>
                            <small class="text-muted">#<?= View::e((string)$r['member_number']) ?></small></td>
                        <?php if ($isGolf): ?>
                            <td><?= View::e($r['course_name'] ?? '—') ?></td>
                            <td><?= $r['total_strokes'] !== null ? (int)$r['total_strokes'] : '—' ?></td>
                            <td><?= $r['total_to_par'] !== null ? sprintf('%+d', (int)$r['total_to_par']) : '—' ?></td>
                            <td><?= $r['handicap_used'] !== null ? View::e((string)$r['handicap_used']) : '—' ?></td>
                        <?php else: ?>
                            <td><?= (int)$r['distance_m'] ?>m</td>
                            <td><?= (int)$r['total_ends'] ?> × <?= (int)$r['arrows_per_end'] ?></td>
                            <td><?= $r['total_score'] !== null ? (int)$r['total_score'] : '—' ?></td>
                            <td><?= (int)$r['tens'] ?> / <?= (int)$r['x_count'] ?></td>
                        <?php endif; ?>
                        <td>
                            <?php if ((int)$r['verified']): ?>
                                <span class="badge bg-success">OK</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <?php if (!(int)$r['verified']): ?>
                            <form method="POST"
                                  action="<?= url('club/sport/' . $key . '/scorecards/' . (int)$r['id'] . '/verify') ?>"
                                  class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-success"><i class="bi bi-check2"></i></button>
                            </form>
                            <?php endif; ?>
                            <form method="POST"
                                  action="<?= url('club/sport/' . $key . '/scorecards/' . (int)$r['id'] . '/delete') ?>"
                                  class="d-inline" onsubmit="return confirm('Usunąć?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div></div>
    </div>
    <?php endforeach; ?>
</div>
