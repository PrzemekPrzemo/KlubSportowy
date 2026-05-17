<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-people-fill text-primary me-2"></i>E-sport — Profile graczy</h4>
    <a href="<?= url('club/esport/games') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-controller"></i> Katalog gier
    </a>
</div>

<form method="GET" class="mb-3 d-flex gap-2 align-items-center">
    <label class="small text-muted mb-0">Filtr po grze:</label>
    <select name="game" class="form-select form-select-sm" style="width:280px" onchange="this.form.submit()">
        <option value="">— wszystkie —</option>
        <?php foreach ($games as $g): ?>
            <option value="<?= View::e($g['game_code']) ?>"
                <?= $currentGame === $g['game_code'] ? 'selected' : '' ?>>
                <?= View::e($g['display_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if ($currentGame): ?>
        <a href="<?= url('club/esport/leaderboard/' . urlencode($currentGame)) ?>" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-trophy"></i> Leaderboard
        </a>
    <?php endif; ?>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Gracz</th>
                    <th>Gra</th>
                    <th>In-Game Name</th>
                    <th>Platforma</th>
                    <th class="text-center">Ranga</th>
                    <th class="text-end">ELO</th>
                    <th class="text-center">W / P</th>
                    <th class="text-end">Godziny</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($profiles)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Brak profili graczy — zawodnicy moga dodawac swoje profile w portalu.</td></tr>
            <?php else: foreach ($profiles as $p): ?>
                <tr>
                    <td>
                        <strong><?= View::e($p['last_name'] . ' ' . $p['first_name']) ?></strong>
                        <small class="text-muted">#<?= View::e($p['member_number'] ?? '') ?></small>
                    </td>
                    <td><?= View::e($p['game_display_name'] ?? $p['game_code']) ?></td>
                    <td class="font-monospace"><?= View::e($p['in_game_name']) ?></td>
                    <td><span class="badge bg-secondary"><?= View::e($platforms[$p['platform']] ?? $p['platform']) ?></span></td>
                    <td class="text-center small"><?= View::e($p['rank_tier'] ?? '—') ?></td>
                    <td class="text-end font-monospace"><?= (int)$p['elo_rating'] ?></td>
                    <td class="text-center">
                        <span class="text-success"><?= (int)$p['wins'] ?></span>
                        /
                        <span class="text-danger"><?= (int)$p['losses'] ?></span>
                    </td>
                    <td class="text-end small text-muted"><?= (int)$p['hours_played'] ?>h</td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
