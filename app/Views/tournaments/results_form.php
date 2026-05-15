<?php
use App\Helpers\View;

/** @var array $tournament */
/** @var array $participants */
/** @var array $matches */
/** @var array $sport */
/** @var string $sportType */
/** @var array $sports */
/** @var int $asyncThreshold */

$statusLabels = [
    'draft'    => ['label' => 'Szkic',      'class' => 'bg-secondary'],
    'active'   => ['label' => 'Aktywny',    'class' => 'bg-success'],
    'finished' => ['label' => 'Zakończony', 'class' => 'bg-primary'],
];
$st = $statusLabels[$tournament['status']] ?? ['label' => View::e($tournament['status']), 'class' => 'bg-secondary'];
$sportName = $sport['name'] ?? ($sports[$tournament['sport_key']]['name'] ?? $tournament['sport_key']);

$participantCount = count($participants);
$bigTournament    = $participantCount > $asyncThreshold;

// Helper: format time (ms → mm:ss.SSS)
$fmtTime = static function ($ms): string {
    if ($ms === null || $ms === '') return '';
    $ms = (int)$ms;
    $minutes = intdiv($ms, 60000);
    $rest    = $ms % 60000;
    $seconds = intdiv($rest, 1000);
    $millis  = $rest % 1000;
    return sprintf('%02d:%02d.%03d', $minutes, $seconds, $millis);
};
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1"><i class="bi bi-clipboard-check me-2"></i>Wyniki turnieju</h4>
        <div class="text-muted small">
            <?= View::e($tournament['name']) ?>
            · Sport: <strong><?= View::e($sportName) ?></strong>
            · Typ: <span class="badge bg-info text-dark"><?= View::e($sportType) ?></span>
            · <span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('tournaments/' . (int)$tournament['id']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Powrót
        </a>
        <a href="<?= url('tournaments/' . (int)$tournament['id'] . '/protocol-pdf') ?>" class="btn btn-outline-primary btn-sm" target="_blank">
            <i class="bi bi-file-earmark-pdf"></i> Protokół PDF
        </a>
    </div>
</div>

<?php if ($bigTournament): ?>
<div class="alert alert-warning small">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Duży turniej (<?= $participantCount ?> uczestników). Przeliczenie rankingu po zapisaniu wyników może zająć kilka sekund.
</div>
<?php endif; ?>

<form method="POST" action="<?= url('tournaments/' . (int)$tournament['id'] . '/results/save') ?>" id="results-form">
    <?= csrf_field() ?>

    <?php if ($sportType === 'time'): ?>
        <!-- ─────────── SPORTY CZASOWE (best_time) ─────────── -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-stopwatch me-1"></i>Wyniki czasowe</strong>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-sort-by-time">
                    <i class="bi bi-sort-numeric-down"></i> Posortuj wg czasu (auto-place)
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" id="time-results-table">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Zawodnik</th>
                            <th style="width:160px">Czas (mm:ss.SSS)</th>
                            <th style="width:80px">Czas (ms)</th>
                            <th style="width:80px">Miejsce</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($participants as $i => $p): ?>
                        <?php $mid = (int)$p['member_id']; ?>
                        <tr data-member-id="<?= $mid ?>">
                            <td><?= $i + 1 ?></td>
                            <td>
                                <?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?>
                                <span class="text-muted small d-block"><?= View::e($p['member_number'] ?? '') ?></span>
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm time-display"
                                       placeholder="00:00.000"
                                       pattern="^[0-9]{1,3}:[0-5][0-9]\.[0-9]{3}$"
                                       value="">
                            </td>
                            <td>
                                <input type="number" name="participants[<?= $mid ?>][time_ms]"
                                       class="form-control form-control-sm time-ms"
                                       min="0" step="1"
                                       value="">
                            </td>
                            <td>
                                <input type="number" name="participants[<?= $mid ?>][place]"
                                       class="form-control form-control-sm place-input"
                                       min="1" step="1"
                                       value="<?= isset($p['place']) ? (int)$p['place'] : '' ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($sportType === 'fight' || $sportType === 'mind'): ?>
        <!-- ─────────── SPORTY WALKI / MIND (drabinki / mecze 1v1) ─────────── -->
        <?php if (empty($matches)): ?>
            <div class="alert alert-info">
                Brak meczy w turnieju. Wygeneruj najpierw drabinkę w widoku turnieju.
            </div>
        <?php else: ?>
            <?php
                // Grupowanie po rundzie
                $byRound = [];
                foreach ($matches as $m) { $byRound[(int)$m['round']][] = $m; }
            ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-diagram-3 me-1"></i>Mecze w drabince</strong>
                    <?php if ($sportType === 'fight'): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-bracket-fill">
                        <i class="bi bi-trophy"></i> Bracket: auto-fill winners (z najwyższym score)
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php foreach ($byRound as $round => $matchesInRound): ?>
                        <h6 class="text-muted text-uppercase small mt-2 mb-2">Runda <?= (int)$round ?></h6>
                        <table class="table table-sm align-middle mb-3">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:80px">#</th>
                                    <th>Zawodnik 1</th>
                                    <th style="width:100px">Score 1</th>
                                    <th>Zawodnik 2</th>
                                    <th style="width:100px">Score 2</th>
                                    <th style="width:180px">Zwycięzca</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($matchesInRound as $match): ?>
                                <?php
                                    $mid = (int)$match['id'];
                                    $isBye = $match['player1_id'] === null || $match['player2_id'] === null;
                                ?>
                                <tr<?= $isBye ? ' class="text-muted"' : '' ?>>
                                    <td>#<?= (int)$match['match_number'] ?></td>
                                    <td>
                                        <?= $match['player1_id'] ? View::e($match['player1_name']) : '<em>BYE</em>' ?>
                                    </td>
                                    <td>
                                        <input type="text" name="matches[<?= $mid ?>][score1]"
                                               class="form-control form-control-sm score1-input"
                                               value="<?= View::e($match['score1'] ?? '') ?>"
                                               maxlength="20"
                                               <?= $isBye ? 'disabled' : '' ?>>
                                    </td>
                                    <td>
                                        <?= $match['player2_id'] ? View::e($match['player2_name']) : '<em>BYE</em>' ?>
                                    </td>
                                    <td>
                                        <input type="text" name="matches[<?= $mid ?>][score2]"
                                               class="form-control form-control-sm score2-input"
                                               value="<?= View::e($match['score2'] ?? '') ?>"
                                               maxlength="20"
                                               <?= $isBye ? 'disabled' : '' ?>>
                                    </td>
                                    <td>
                                        <select name="matches[<?= $mid ?>][winner_id]"
                                                class="form-select form-select-sm winner-select"
                                                <?= $isBye ? 'disabled' : '' ?>>
                                            <option value="">— wybierz —</option>
                                            <?php if ($match['player1_id']): ?>
                                                <option value="<?= (int)$match['player1_id'] ?>"
                                                    <?= ((int)$match['winner_id'] === (int)$match['player1_id']) ? 'selected' : '' ?>>
                                                    <?= View::e($match['player1_name']) ?>
                                                </option>
                                            <?php endif; ?>
                                            <?php if ($match['player2_id']): ?>
                                                <option value="<?= (int)$match['player2_id'] ?>"
                                                    <?= ((int)$match['winner_id'] === (int)$match['player2_id']) ? 'selected' : '' ?>>
                                                    <?= View::e($match['player2_name']) ?>
                                                </option>
                                            <?php endif; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php elseif ($sportType === 'team'): ?>
        <!-- ─────────── SPORTY DRUŻYNOWE (mecze + score) ─────────── -->
        <?php if (empty($matches)): ?>
            <div class="alert alert-info">Brak meczy w turnieju.</div>
        <?php else: ?>
            <?php $byRound = []; foreach ($matches as $m) { $byRound[(int)$m['round']][] = $m; } ?>
            <div class="card mb-3">
                <div class="card-header">
                    <strong><i class="bi bi-people-fill me-1"></i>Mecze drużynowe</strong>
                </div>
                <div class="card-body">
                    <?php foreach ($byRound as $round => $matchesInRound): ?>
                        <h6 class="text-muted text-uppercase small mt-2 mb-2">Runda <?= (int)$round ?></h6>
                        <?php foreach ($matchesInRound as $match): ?>
                            <?php $mid = (int)$match['id']; $isBye = $match['player1_id'] === null || $match['player2_id'] === null; ?>
                            <div class="row g-2 align-items-center mb-2<?= $isBye ? ' text-muted' : '' ?>">
                                <div class="col-md-3 text-end">
                                    <?= $match['player1_id'] ? View::e($match['player1_name']) : '<em>BYE</em>' ?>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" min="0" step="1"
                                           name="matches[<?= $mid ?>][score1]"
                                           class="form-control form-control-sm text-center"
                                           placeholder="Gole/punkty"
                                           value="<?= View::e($match['score1'] ?? '') ?>"
                                           <?= $isBye ? 'disabled' : '' ?>>
                                </div>
                                <div class="col-md-auto text-center"><strong>:</strong></div>
                                <div class="col-md-2">
                                    <input type="number" min="0" step="1"
                                           name="matches[<?= $mid ?>][score2]"
                                           class="form-control form-control-sm text-center"
                                           placeholder="Gole/punkty"
                                           value="<?= View::e($match['score2'] ?? '') ?>"
                                           <?= $isBye ? 'disabled' : '' ?>>
                                </div>
                                <div class="col-md-3">
                                    <?= $match['player2_id'] ? View::e($match['player2_name']) : '<em>BYE</em>' ?>
                                </div>
                                <div class="col-md-12 col-lg-auto">
                                    <select name="matches[<?= $mid ?>][winner_id]"
                                            class="form-select form-select-sm"
                                            <?= $isBye ? 'disabled' : '' ?>>
                                        <option value="">— remis / brak —</option>
                                        <?php if ($match['player1_id']): ?>
                                            <option value="<?= (int)$match['player1_id'] ?>"
                                                <?= ((int)$match['winner_id'] === (int)$match['player1_id']) ? 'selected' : '' ?>>
                                                Wygrał: <?= View::e($match['player1_name']) ?>
                                            </option>
                                        <?php endif; ?>
                                        <?php if ($match['player2_id']): ?>
                                            <option value="<?= (int)$match['player2_id'] ?>"
                                                <?= ((int)$match['winner_id'] === (int)$match['player2_id']) ? 'selected' : '' ?>>
                                                Wygrał: <?= View::e($match['player2_name']) ?>
                                            </option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- ─────────── GENERIC: score + miejsce per uczestnik ─────────── -->
        <div class="card mb-3">
            <div class="card-header"><strong><i class="bi bi-list-ol me-1"></i>Wyniki uczestników</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Zawodnik</th>
                            <th style="width:120px">Score</th>
                            <th style="width:80px">Miejsce</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($participants as $i => $p): ?>
                        <?php $mid = (int)$p['member_id']; ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></td>
                            <td>
                                <input type="number" step="0.01" name="participants[<?= $mid ?>][score]"
                                       class="form-control form-control-sm" value="">
                            </td>
                            <td>
                                <input type="number" min="1" step="1" name="participants[<?= $mid ?>][place]"
                                       class="form-control form-control-sm" value="">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="alert alert-info small">
            <i class="bi bi-info-circle me-1"></i>
            TODO: dedykowany formularz dla sportu „<?= View::e($tournament['sport_key']) ?>". Na razie używamy generycznego score + miejsce.
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="mark_finished" id="mark_finished" value="1"
                       <?= $tournament['status'] === 'finished' ? 'checked' : '' ?>>
                <label class="form-check-label" for="mark_finished">
                    Oznacz turniej jako <strong>zakończony</strong> (wyśle powiadomienia uczestnikom)
                </label>
            </div>
            <div class="ms-auto">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-save me-1"></i> Zapisz wyniki i przelicz ranking
                </button>
            </div>
        </div>
    </div>
</form>

<?php if ($sportType === 'time' || $sportType === 'fight'): ?>
<script>
(function(){
    // Time: konwersja mm:ss.SSS ↔ ms.
    function parseTime(str) {
        var m = /^(\d{1,3}):([0-5]\d)\.(\d{3})$/.exec((str||'').trim());
        if (!m) return null;
        return (parseInt(m[1],10)*60000) + (parseInt(m[2],10)*1000) + parseInt(m[3],10);
    }
    function fmtTime(ms) {
        ms = parseInt(ms||0, 10);
        if (isNaN(ms) || ms < 0) return '';
        var mm = Math.floor(ms/60000);
        var ss = Math.floor((ms%60000)/1000);
        var SS = ms%1000;
        return String(mm).padStart(2,'0') + ':' + String(ss).padStart(2,'0') + '.' + String(SS).padStart(3,'0');
    }

    document.querySelectorAll('input.time-display').forEach(function(input){
        input.addEventListener('input', function(){
            var row = input.closest('tr');
            var msInput = row.querySelector('input.time-ms');
            var ms = parseTime(input.value);
            if (msInput) msInput.value = ms === null ? '' : ms;
        });
    });
    document.querySelectorAll('input.time-ms').forEach(function(input){
        // initialize display from ms
        var row = input.closest('tr');
        var display = row && row.querySelector('input.time-display');
        if (display && input.value) display.value = fmtTime(input.value);
    });

    var sortBtn = document.getElementById('btn-sort-by-time');
    if (sortBtn) {
        sortBtn.addEventListener('click', function(){
            var rows = Array.from(document.querySelectorAll('#time-results-table tbody tr'));
            var withTime = rows.filter(function(r){
                var v = r.querySelector('input.time-ms');
                return v && v.value !== '' && !isNaN(parseInt(v.value, 10));
            });
            withTime.sort(function(a,b){
                return parseInt(a.querySelector('input.time-ms').value,10)
                     - parseInt(b.querySelector('input.time-ms').value,10);
            });
            withTime.forEach(function(r, idx){
                var p = r.querySelector('input.place-input');
                if (p) p.value = idx + 1;
            });
        });
    }

    // Fight: auto-fill winner based on numeric score.
    var fillBtn = document.getElementById('btn-bracket-fill');
    if (fillBtn) {
        fillBtn.addEventListener('click', function(){
            document.querySelectorAll('select.winner-select').forEach(function(sel){
                if (sel.disabled || sel.value) return;
                var row = sel.closest('tr');
                if (!row) return;
                var s1 = parseFloat(row.querySelector('input.score1-input').value);
                var s2 = parseFloat(row.querySelector('input.score2-input').value);
                if (isNaN(s1) || isNaN(s2)) return;
                var opts = sel.querySelectorAll('option');
                if (opts.length < 3) return;
                if (s1 > s2) sel.value = opts[1].value;
                else if (s2 > s1) sel.value = opts[2].value;
            });
        });
    }
})();
</script>
<?php endif; ?>
