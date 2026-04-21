<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-list-ol text-primary me-2"></i>Ranking tenisa — <?= View::e($season) ?></h4>
    <form method="GET" class="d-flex gap-2 align-items-center">
        <label class="small text-muted mb-0">Sezon:</label>
        <input type="number" name="season" class="form-control form-control-sm" style="width:110px"
               value="<?= View::e($season) ?>" min="2020" max="2050">
        <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i></button>
    </form>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:60px">Poz.</th>
                    <th>Zawodnik</th>
                    <th class="text-center">Punkty</th>
                    <th class="text-center">Mecze</th>
                    <th class="text-center">W</th>
                    <th class="text-center">P</th>
                    <th class="text-center">Skuteczność</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($ranking)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Brak wpisów w rankingu. Dodaj mecze, aby zbudować ranking.</td></tr>
            <?php else: foreach ($ranking as $r):
                $winrate = $r['matches_played'] > 0 ? round(($r['wins'] / $r['matches_played']) * 100) : 0;
            ?>
                <tr>
                    <td class="text-center">
                        <?php if ($r['position'] === 1): ?>
                            <i class="bi bi-trophy-fill text-warning fs-5"></i>
                        <?php elseif ($r['position'] === 2): ?>
                            <i class="bi bi-award-fill" style="color:#c0c0c0;font-size:1.2rem;"></i>
                        <?php elseif ($r['position'] === 3): ?>
                            <i class="bi bi-award-fill" style="color:#cd7f32;font-size:1.2rem;"></i>
                        <?php else: ?>
                            <span class="badge bg-secondary">#<?= (int)$r['position'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?></strong>
                        <small class="text-muted d-block">#<?= View::e($r['member_number']) ?></small>
                    </td>
                    <td class="text-center"><span class="badge bg-primary fs-6"><?= (int)$r['points'] ?></span></td>
                    <td class="text-center"><?= (int)$r['matches_played'] ?></td>
                    <td class="text-center text-success fw-bold"><?= (int)$r['wins'] ?></td>
                    <td class="text-center text-danger"><?= (int)$r['losses'] ?></td>
                    <td class="text-center">
                        <div class="progress" style="height:18px;">
                            <div class="progress-bar <?= $winrate >= 70 ? 'bg-success' : ($winrate >= 50 ? 'bg-primary' : 'bg-danger') ?>"
                                 style="width:<?= $winrate ?>%"><?= $winrate ?>%</div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-muted small mt-3">
    <i class="bi bi-info-circle"></i>
    Punktacja: turniejowy +50, rankingowy +20, towarzyski +5. Mecze treningowe nie wpływają na ranking.
</p>
