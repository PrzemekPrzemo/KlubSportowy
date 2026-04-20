<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0"><?= View::e($league['name']) ?> <span class="badge bg-secondary"><?= View::e($league['season']) ?></span></h5>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('football/leagues/' . (int)$league['id']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-clockwise"></i> Przelicz tabelę
        </a>
        <a href="<?= url('football/leagues') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Powrót
        </a>
    </div>
</div>

<div class="card">
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th class="text-center" style="width:50px">#</th>
                <th>Drużyna</th>
                <th class="text-center">Pkt</th>
                <th class="text-center">M</th>
                <th class="text-center">W</th>
                <th class="text-center">R</th>
                <th class="text-center">P</th>
                <th class="text-center">Bramki</th>
                <th class="text-center">Różnica</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($standings)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">Brak danych — dodaj drużyny i mecze.</td></tr>
        <?php else: ?>
            <?php $total = count($standings); ?>
            <?php foreach ($standings as $pos => $row): ?>
                <?php
                    $rowClass = '';
                    $rank = $pos + 1;
                    if ($rank <= 3) {
                        $rowClass = 'table-success';
                    } elseif ($rank > $total - 2) {
                        $rowClass = 'table-danger';
                    }
                    $goalDiff = (int)$row['goal_diff'];
                    $diffStr  = $goalDiff > 0 ? '+' . $goalDiff : (string)$goalDiff;
                ?>
                <tr class="<?= $rowClass ?>">
                    <td class="text-center fw-bold"><?= $rank ?></td>
                    <td><?= View::e($row['team_name']) ?></td>
                    <td class="text-center fw-bold"><?= (int)$row['points'] ?></td>
                    <td class="text-center"><?= (int)$row['games_played'] ?></td>
                    <td class="text-center"><?= (int)$row['wins'] ?></td>
                    <td class="text-center"><?= (int)$row['draws'] ?></td>
                    <td class="text-center"><?= (int)$row['losses'] ?></td>
                    <td class="text-center"><?= (int)$row['goals_for'] ?>:<?= (int)$row['goals_against'] ?></td>
                    <td class="text-center"><?= $diffStr ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-3 d-flex gap-2">
    <span class="badge bg-success p-2">Top 3 — awans/mistrzostwo</span>
    <span class="badge bg-danger p-2">Ostatnie 2 — spadek/baraż</span>
</div>
