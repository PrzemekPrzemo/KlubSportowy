<?php
use App\Helpers\View;
$key   = $sportKey ?? '';
$title = $title ?? 'Mój profil';
$canSelfReport = $canSelfReport ?? false;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-person-vcard me-2"></i><?= View::e($title) ?></h4>
    <?php if ($canSelfReport): ?>
        <a href="<?= url('portal/sport/' . $key . '/scorecard/new') ?>" class="btn btn-success btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Dodaj scorecard
        </a>
    <?php endif; ?>
</div>

<?php if ($key === 'badminton'): ?>
    <?php $profile = $profile ?? null; $ranking = $ranking ?? []; $results = $results ?? []; ?>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="text-muted">Profil</h6>
                <?php if ($profile): ?>
                    <div>Dyscyplina: <strong><?= View::e((string)($profile['discipline'] ?? '—')) ?></strong></div>
                    <div>Ręka: <strong><?= View::e((string)($profile['hand'] ?? '—')) ?></strong></div>
                    <div>BWF points: <strong><?= (int)($profile['bwf_points'] ?? 0) ?></strong></div>
                    <div>Ranking krajowy: <strong><?= $profile['national_rank'] !== null ? (int)$profile['national_rank'] : '—' ?></strong></div>
                <?php else: ?>
                    <p class="text-muted">Profil badmintonowy nie został jeszcze utworzony — skontaktuj się z klubem.</p>
                <?php endif; ?>
            </div></div>
        </div>
        <div class="col-md-8">
            <div class="card h-100"><div class="card-body">
                <h6 class="text-muted">Top 10 klubu (BWF points)</h6>
                <ol class="mb-0">
                    <?php foreach (array_slice($ranking, 0, 10) as $r): ?>
                        <li><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?>
                            — <strong><?= (int)$r['bwf_points'] ?></strong></li>
                    <?php endforeach; ?>
                </ol>
                <?php if (empty($ranking)): ?><p class="text-muted mb-0">Brak danych.</p><?php endif; ?>
            </div></div>
        </div>
    </div>
    <div class="card"><div class="card-body">
        <h6 class="text-muted">Moje wyniki</h6>
        <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Data</th><th>Zawody</th><th>Kat.</th><th>Sety</th><th>Miejsce</th></tr></thead>
            <tbody>
                <?php foreach ($results as $r): ?>
                <tr>
                    <td><?= View::e($r['competition_date']) ?></td>
                    <td><?= View::e($r['competition_name']) ?></td>
                    <td><?= View::e((string)($r['category'] ?? '—')) ?></td>
                    <td><?= (int)($r['sets_won'] ?? 0) ?>:<?= (int)($r['sets_lost'] ?? 0) ?></td>
                    <td><?= $r['placement'] !== null ? (int)$r['placement'] : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($results)): ?>
                <tr><td colspan="5" class="text-center text-muted">Brak wyników.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div></div>

<?php elseif ($key === 'squash'): ?>
    <?php $results = $results ?? []; ?>
    <div class="card"><div class="card-body">
        <h6 class="text-muted">Historia meczów</h6>
        <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Data</th><th>Zawody</th><th>Rywal</th><th>Kat.</th><th>Sety W:L</th><th>Gry (PAR)</th><th>Miejsce</th></tr></thead>
            <tbody>
                <?php foreach ($results as $r): ?>
                <tr>
                    <td><?= View::e($r['match_date']) ?></td>
                    <td><?= View::e($r['competition_name'] ?? '—') ?></td>
                    <td><?= View::e($r['opponent_name'] ?? '—') ?></td>
                    <td><?= View::e((string)$r['category']) ?></td>
                    <td><?= (int)($r['sets_won'] ?? 0) ?>:<?= (int)($r['sets_lost'] ?? 0) ?></td>
                    <td><?= View::e((string)($r['games_detail'] ?? '—')) ?></td>
                    <td><?= $r['placement'] !== null ? (int)$r['placement'] : '—' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($results)): ?>
                <tr><td colspan="7" class="text-center text-muted">Brak meczów.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div></div>

<?php elseif ($key === 'golf'): ?>
    <?php $profile = $profile ?? null; $scorecards = $scorecards ?? []; ?>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="text-muted">Handicap</h6>
                <?php if ($profile): ?>
                    <div class="display-6"><?= View::e((string)$profile['hcp']) ?></div>
                    <div class="small text-muted">Aktualizacja: <?= View::e($profile['hcp_updated_at'] ?? '—') ?></div>
                    <?php if (!empty($profile['pga_license'])): ?>
                        <div class="small">Licencja PZG: <?= View::e($profile['pga_license']) ?></div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">Profil golfowy nie został jeszcze utworzony.</p>
                <?php endif; ?>
            </div></div>
        </div>
        <div class="col-md-8">
            <div class="card h-100"><div class="card-body">
                <h6 class="text-muted">Statystyki</h6>
                <?php
                $cnt = count($scorecards);
                $verifiedCnt = 0; $avgStrokes = 0; $bestToPar = null;
                foreach ($scorecards as $s) {
                    if ((int)$s['verified']) $verifiedCnt++;
                    $avgStrokes += (int)$s['total_strokes'];
                    $tp = $s['total_to_par'];
                    if ($tp !== null && ($bestToPar === null || (int)$tp < $bestToPar)) $bestToPar = (int)$tp;
                }
                $avg = $cnt ? round($avgStrokes / $cnt, 1) : 0;
                ?>
                <div>Liczba rund: <strong><?= $cnt ?></strong> (zweryfikowanych: <?= $verifiedCnt ?>)</div>
                <div>Średnia strokes: <strong><?= $avg ?: '—' ?></strong></div>
                <div>Najlepszy to-par: <strong><?= $bestToPar !== null ? sprintf('%+d', $bestToPar) : '—' ?></strong></div>
            </div></div>
        </div>
    </div>
    <div class="card"><div class="card-body">
        <h6 class="text-muted">Moje scorecardy</h6>
        <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Data</th><th>Pole</th><th>Strokes</th><th>To Par</th><th>HCP</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($scorecards as $s): ?>
                <tr>
                    <td><?= View::e($s['played_at']) ?></td>
                    <td><?= View::e($s['course_name'] ?? '—') ?></td>
                    <td><?= $s['total_strokes'] !== null ? (int)$s['total_strokes'] : '—' ?></td>
                    <td><?= $s['total_to_par'] !== null ? sprintf('%+d', (int)$s['total_to_par']) : '—' ?></td>
                    <td><?= $s['handicap_used'] !== null ? View::e((string)$s['handicap_used']) : '—' ?></td>
                    <td>
                    <?php if ((int)$s['verified']): ?>
                        <span class="badge bg-success">Zweryfikowany</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Oczekuje</span>
                    <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($scorecards)): ?>
                <tr><td colspan="6" class="text-center text-muted">Brak scorecardów.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div></div>

<?php elseif ($key === 'padel'): ?>
    <?php $myPairs = $myPairs ?? []; ?>
    <div class="card"><div class="card-body">
        <h6 class="text-muted">Moje pary (debel)</h6>
        <?php if (empty($myPairs)): ?>
            <p class="text-muted mb-0">Nie należysz do żadnej pary. Skontaktuj się z klubem, aby utworzyć parę.</p>
        <?php else: ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($myPairs as $p): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                    <strong><?= View::e($p['pair_name'] ?? '—') ?></strong>
                    <span class="text-muted ms-2">
                        <?= View::e($p['a_last'] . ' ' . $p['a_first']) ?>
                        + <?= View::e($p['b_last'] . ' ' . $p['b_first']) ?>
                    </span>
                </span>
                <span class="badge bg-primary rounded-pill"><?= (int)$p['ranking_points'] ?> pkt</span>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div></div>

<?php elseif ($key === 'archery'): ?>
    <?php $profile = $profile ?? null; $scorecards = $scorecards ?? []; ?>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="text-muted">Profil łucznika</h6>
                <?php if ($profile): ?>
                    <div>Typ łuku: <strong><?= View::e((string)$profile['bow_type']) ?></strong></div>
                    <div>Dominujące oko: <strong><?= View::e((string)$profile['dominant_eye']) ?></strong></div>
                    <div>Ranking krajowy: <strong><?= $profile['national_rank'] !== null ? (int)$profile['national_rank'] : '—' ?></strong></div>
                <?php else: ?>
                    <p class="text-muted">Profil łucznika nie został jeszcze utworzony.</p>
                <?php endif; ?>
            </div></div>
        </div>
        <div class="col-md-8">
            <div class="card h-100"><div class="card-body">
                <h6 class="text-muted">Statystyki</h6>
                <?php
                $cnt = count($scorecards);
                $totalAll = 0; $totalTens = 0; $totalX = 0; $best = null;
                foreach ($scorecards as $s) {
                    $totalAll += (int)$s['total_score'];
                    $totalTens += (int)$s['tens'];
                    $totalX += (int)$s['x_count'];
                    if ($best === null || (int)$s['total_score'] > $best) $best = (int)$s['total_score'];
                }
                ?>
                <div>Rund: <strong><?= $cnt ?></strong></div>
                <div>Najlepszy total: <strong><?= $best ?? '—' ?></strong></div>
                <div>Łącznie 10-tek: <strong><?= $totalTens ?></strong> (w tym X: <?= $totalX ?>)</div>
            </div></div>
        </div>
    </div>
    <div class="card"><div class="card-body">
        <h6 class="text-muted">Moje scorecardy</h6>
        <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Data</th><th>Dystans</th><th>Endy × strzały</th><th>Total</th><th>10-tek/X</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($scorecards as $s): ?>
                <tr>
                    <td><?= View::e($s['shot_at']) ?></td>
                    <td><?= (int)$s['distance_m'] ?>m</td>
                    <td><?= (int)$s['total_ends'] ?> × <?= (int)$s['arrows_per_end'] ?></td>
                    <td><?= $s['total_score'] !== null ? (int)$s['total_score'] : '—' ?></td>
                    <td><?= (int)$s['tens'] ?> / <?= (int)$s['x_count'] ?></td>
                    <td>
                    <?php if ((int)$s['verified']): ?>
                        <span class="badge bg-success">Zweryfikowany</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Oczekuje</span>
                    <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($scorecards)): ?>
                <tr><td colspan="6" class="text-center text-muted">Brak scorecardów.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div></div>
<?php endif; ?>
