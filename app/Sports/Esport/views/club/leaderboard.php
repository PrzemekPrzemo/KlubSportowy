<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="bi bi-trophy-fill text-warning me-2"></i>
        Leaderboard — <?= View::e($game['display_name']) ?>
    </h4>
    <a href="<?= url('club/esport/profiles?game=' . urlencode($game['game_code'])) ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Wszystkie profile
    </a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:60px">Poz.</th>
                    <th>Zawodnik</th>
                    <th>IGN</th>
                    <th class="text-center">Ranga</th>
                    <th class="text-end">ELO</th>
                    <th class="text-center">W / P</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($top)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Brak graczy w tej grze.</td></tr>
            <?php else: foreach ($top as $i => $p): ?>
                <tr>
                    <td class="text-center">
                        <?php if ($i === 0): ?>
                            <i class="bi bi-trophy-fill text-warning fs-5"></i>
                        <?php elseif ($i === 1): ?>
                            <i class="bi bi-award-fill" style="color:#c0c0c0;font-size:1.2rem;"></i>
                        <?php elseif ($i === 2): ?>
                            <i class="bi bi-award-fill" style="color:#cd7f32;font-size:1.2rem;"></i>
                        <?php else: ?>
                            <strong><?= $i + 1 ?></strong>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= View::e($p['last_name'] . ' ' . $p['first_name']) ?></strong>
                        <small class="text-muted">#<?= View::e($p['member_number'] ?? '') ?></small>
                    </td>
                    <td class="font-monospace"><?= View::e($p['in_game_name']) ?></td>
                    <td class="text-center small"><?= View::e($p['rank_tier'] ?? '—') ?></td>
                    <td class="text-end font-monospace fw-bold"><?= (int)$p['elo_rating'] ?></td>
                    <td class="text-center">
                        <span class="text-success"><?= (int)$p['wins'] ?></span>
                        /
                        <span class="text-danger"><?= (int)$p['losses'] ?></span>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
