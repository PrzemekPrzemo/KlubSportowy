<?php use App\Helpers\View; ?>
<?php
    $tid = (int)($tournament['id'] ?? 0);
    $typeLabels = [
        'single_elimination' => 'Single Elimination',
        'double_elimination' => 'Double Elimination',
        'round_robin'        => 'Każdy z każdym',
        'swiss'              => 'Swiss',
    ];
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0">
        <i class="bi bi-diagram-3 me-2"></i><?= View::e($tournament['name'] ?? '') ?>
        <span class="badge bg-info ms-2"><?= View::e($typeLabels[$bracketType] ?? $bracketType) ?></span>
    </h4>
    <div class="d-flex gap-2">
        <a href="<?= url('tournaments/' . $tid) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Wróć
        </a>
        <a href="<?= url('tournaments/' . $tid . '/bracket/seeds') ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-list-ol"></i> Seedy
        </a>
        <a href="<?= url('tournaments/' . $tid . '/bracket/generate') ?>" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-diagram-3"></i> Generuj
        </a>
        <a href="<?= url('tournaments/' . $tid . '/bracket/pdf') ?>" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-pdf"></i> PDF
        </a>
    </div>
</div>

<style>
  .bracket-wrap { overflow-x: auto; }
  .bracket { display: flex; gap: 24px; padding: 12px 0; align-items: stretch; min-height: 200px; }
  .bracket-round { display: flex; flex-direction: column; justify-content: space-around; min-width: 200px; gap: 12px; }
  .bracket-round h6 { text-transform: uppercase; font-size: 11px; color: #888; margin-bottom: 8px; }
  .bracket-match {
    border: 1px solid #ced4da; border-radius: 6px; background: #fff;
    padding: 6px 8px; font-size: 13px; box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    position: relative;
  }
  .bracket-match .player { display: flex; justify-content: space-between; padding: 2px 0; }
  .bracket-match .player + .player { border-top: 1px solid #eee; }
  .bracket-match .player.winner { font-weight: 600; color: #198754; }
  .bracket-match .player.loser  { color: #999; text-decoration: line-through; }
  .bracket-match .bye { color: #adb5bd; font-style: italic; }
  .bracket-match .score { color: #6c757d; font-size: 12px; min-width: 32px; text-align: right; }
  .bracket-match .match-num { position: absolute; top: -8px; right: 6px; background: #f8f9fa; padding: 0 4px; font-size: 10px; color: #888; }
  .bracket-section + .bracket-section { margin-top: 32px; }
  .rr-table { font-size: 13px; }
  .rr-table th, .rr-table td { text-align: center; vertical-align: middle; min-width: 50px; }
  .rr-table .diag { background: #f0f0f0; }
</style>

<?php
    $renderMatch = function(array $m) use ($tournament, $tid) {
        $w  = (int)($m['winner_id'] ?? 0);
        $p1 = (int)($m['player1_id'] ?? 0);
        $p2 = (int)($m['player2_id'] ?? 0);
        $isBye = !empty($m['is_bye']) || ($p1 === 0 xor $p2 === 0);
        $p1cls = $w && $p1 === $w ? 'winner' : ($w && $p1 ? 'loser' : '');
        $p2cls = $w && $p2 === $w ? 'winner' : ($w && $p2 ? 'loser' : '');
        ?>
        <div class="bracket-match">
            <span class="match-num">#<?= (int)$m['match_number'] ?></span>
            <div class="player <?= $p1cls ?>">
                <?php if ($p1): ?>
                    <span><?= View::e($m['player1_name']) ?></span>
                    <span class="score"><?= View::e($m['score1'] ?? '') ?></span>
                <?php else: ?>
                    <span class="bye">— BYE —</span><span></span>
                <?php endif; ?>
            </div>
            <div class="player <?= $p2cls ?>">
                <?php if ($p2): ?>
                    <span><?= View::e($m['player2_name']) ?></span>
                    <span class="score"><?= View::e($m['score2'] ?? '') ?></span>
                <?php else: ?>
                    <span class="bye">— BYE —</span><span></span>
                <?php endif; ?>
            </div>
            <?php if (!$w && $p1 && $p2 && ($tournament['status'] ?? '') === 'active'): ?>
                <form method="POST" action="<?= url('tournaments/match/' . (int)$m['id'] . '/result') ?>" class="mt-1">
                    <?= csrf_field() ?>
                    <div class="row g-1 mb-1">
                        <div class="col-6"><input type="text" name="score1" class="form-control form-control-sm" placeholder="Wyn. 1" maxlength="20"></div>
                        <div class="col-6"><input type="text" name="score2" class="form-control form-control-sm" placeholder="Wyn. 2" maxlength="20"></div>
                    </div>
                    <select name="winner_id" class="form-select form-select-sm mb-1" required>
                        <option value="">— zwycięzca —</option>
                        <option value="<?= $p1 ?>"><?= View::e($m['player1_name']) ?></option>
                        <option value="<?= $p2 ?>"><?= View::e($m['player2_name']) ?></option>
                    </select>
                    <button class="btn btn-sm btn-primary w-100"><i class="bi bi-check2"></i> Zapisz</button>
                </form>
            <?php endif; ?>
        </div>
        <?php
    };
?>

<?php if (empty($matches)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>Drabinka nie zostala jeszcze wygenerowana.
        <a href="<?= url('tournaments/' . $tid . '/bracket/generate') ?>">Generuj teraz</a>
    </div>
<?php elseif ($bracketType === 'round_robin'): ?>
    <!-- Round-robin: tabela 2D wszystkich par + lista rund -->
    <?php
        // Build participants set (unique players in matches)
        $players = [];
        foreach ($matches as $m) {
            if ($m['player1_id']) $players[(int)$m['player1_id']] = $m['player1_name'];
            if ($m['player2_id']) $players[(int)$m['player2_id']] = $m['player2_name'];
        }
        // Build cell lookup: results[$a][$b] = match
        $results = [];
        foreach ($matches as $m) {
            $a = (int)$m['player1_id']; $b = (int)$m['player2_id'];
            if ($a && $b) {
                $results[$a][$b] = $m;
                $results[$b][$a] = $m;
            }
        }
    ?>
    <div class="card mb-4">
        <div class="card-header"><strong>Tabela meczów (każdy z każdym)</strong></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-sm rr-table">
                <thead>
                    <tr><th></th>
                    <?php foreach ($players as $pid => $pname): ?>
                        <th title="<?= View::e($pname) ?>"><?= View::e(mb_substr($pname,0,3)) ?></th>
                    <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($players as $aId => $aName): ?>
                    <tr>
                        <th class="text-start"><?= View::e($aName) ?></th>
                        <?php foreach ($players as $bId => $bName): ?>
                            <?php if ($aId === $bId): ?>
                                <td class="diag">—</td>
                            <?php else: $cell = $results[$aId][$bId] ?? null; ?>
                                <td>
                                    <?php if ($cell && $cell['winner_id']): ?>
                                        <strong><?= View::e($cell['score1']) ?>:<?= View::e($cell['score2']) ?></strong>
                                    <?php elseif ($cell): ?>
                                        <span class="text-muted">vs</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Lista rund -->
    <?php foreach ($grouped['upper'] as $r => $list): ?>
        <h6 class="text-uppercase text-muted small mt-3">Runda <?= (int)$r ?></h6>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($list as $m): ?>
                <div style="min-width:240px"><?php $renderMatch($m); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

<?php elseif ($bracketType === 'double_elimination'): ?>
    <!-- DE: Winners + Losers + Final -->
    <div class="bracket-section">
        <h5 class="mb-2"><i class="bi bi-trophy me-1"></i>Winners Bracket</h5>
        <div class="bracket-wrap"><div class="bracket">
            <?php foreach ($grouped['upper'] as $r => $list): ?>
                <div class="bracket-round">
                    <h6>Runda <?= (int)$r ?></h6>
                    <?php foreach ($list as $m): $renderMatch($m); endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
    <?php if (!empty($grouped['lower'])): ?>
    <div class="bracket-section">
        <h5 class="mb-2 text-warning"><i class="bi bi-arrow-down-circle me-1"></i>Losers Bracket</h5>
        <div class="bracket-wrap"><div class="bracket">
            <?php foreach ($grouped['lower'] as $r => $list): ?>
                <div class="bracket-round">
                    <h6>LB-R<?= (int)$r ?></h6>
                    <?php foreach ($list as $m): $renderMatch($m); endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
    <?php endif; ?>
    <?php if (!empty($grouped['final'])): ?>
    <div class="bracket-section">
        <h5 class="mb-2 text-success"><i class="bi bi-flag-fill me-1"></i>Grand Final</h5>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($grouped['final'] as $r => $list): foreach ($list as $m): ?>
                <div style="min-width:240px"><?php $renderMatch($m); ?></div>
            <?php endforeach; endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Single elimination: drzewo -->
    <div class="bracket-wrap">
        <div class="bracket">
            <?php
                // Merge final into upper for display ordering (final round is the last col)
                $cols = [];
                foreach ($grouped['upper'] as $r => $list) { $cols[$r] = ['title' => "Runda {$r}", 'matches' => $list]; }
                foreach ($grouped['final'] as $r => $list) { $cols[$r] = ['title' => 'Finał', 'matches' => $list]; }
                ksort($cols);
            ?>
            <?php foreach ($cols as $r => $col): ?>
                <div class="bracket-round">
                    <h6><?= View::e($col['title']) ?></h6>
                    <?php foreach ($col['matches'] as $m): $renderMatch($m); endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php if (!empty($grouped['third_place'])): ?>
    <div class="bracket-section">
        <h5 class="mb-2 text-info"><i class="bi bi-award me-1"></i>Mecz o 3 miejsce</h5>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($grouped['third_place'] as $r => $list): foreach ($list as $m): ?>
                <div style="min-width:240px"><?php $renderMatch($m); ?></div>
            <?php endforeach; endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>
