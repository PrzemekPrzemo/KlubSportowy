<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <div class="row text-center">
        <div class="col-5"><h4><?= View::e($match['home_team_name']) ?></h4></div>
        <div class="col-2"><h2><?= $match['home_score'] !== null ? (int)$match['home_score'] . ':' . (int)$match['away_score'] : 'vs' ?></h2>
            <small class="text-muted"><?= format_datetime($match['match_date']) ?></small></div>
        <div class="col-5"><h4><?= View::e($match['away_team']) ?></h4></div>
    </div>

    <?php if ($match['q1_home'] !== null): ?>
    <div class="text-center mt-2">
        <table class="table table-sm table-bordered mx-auto" style="max-width:500px">
            <thead class="table-light">
                <tr><th></th><th>Q1</th><th>Q2</th><th>Q3</th><th>Q4</th>
                    <?php if ($match['overtime_home'] !== null): ?><th>OT</th><?php endif; ?>
                    <th><strong>Razem</strong></th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?= View::e($match['home_team_name']) ?></strong></td>
                    <td><?= (int)$match['q1_home'] ?></td><td><?= (int)$match['q2_home'] ?></td>
                    <td><?= (int)$match['q3_home'] ?></td><td><?= (int)$match['q4_home'] ?></td>
                    <?php if ($match['overtime_home'] !== null): ?><td><?= (int)$match['overtime_home'] ?></td><?php endif; ?>
                    <td><strong><?= $match['home_score'] !== null ? (int)$match['home_score'] : '—' ?></strong></td>
                </tr>
                <tr>
                    <td><strong><?= View::e($match['away_team']) ?></strong></td>
                    <td><?= (int)$match['q1_away'] ?></td><td><?= (int)$match['q2_away'] ?></td>
                    <td><?= (int)$match['q3_away'] ?></td><td><?= (int)$match['q4_away'] ?></td>
                    <?php if ($match['overtime_away'] !== null): ?><td><?= (int)$match['overtime_away'] ?></td><?php endif; ?>
                    <td><strong><?= $match['away_score'] !== null ? (int)$match['away_score'] : '—' ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="text-center small text-muted mt-1">
        <?= View::e($match['match_type']) ?> • <?= View::e($match['status']) ?>
        <?php if ($match['location']): ?> • <?= View::e($match['location']) ?><?php endif; ?>
        <?php if ($match['referee']): ?> • Sędzia: <?= View::e($match['referee']) ?><?php endif; ?>
    </div>
    <div class="text-center mt-2">
        <a href="<?= url('basketball/matches/' . (int)$match['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edytuj</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="card p-3">
            <h5 class="mb-3">Box Score — statystyki zawodników</h5>
            <form method="POST" action="<?= url('basketball/matches/' . (int)$match['id'] . '/stats') ?>" class="d-flex gap-2 mb-3 flex-wrap align-items-end">
                <?= csrf_field() ?>
                <div><label class="form-label small">Zawodnik</label>
                    <select name="member_id" class="form-select form-select-sm" style="max-width:200px" required>
                        <option value="">— zawodnik —</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div><label class="form-label small">Min</label>
                    <input type="number" name="minutes" min="0" max="60" class="form-control form-control-sm" style="max-width:60px"></div>
                <div><label class="form-label small">Pkt</label>
                    <input type="number" name="points" min="0" class="form-control form-control-sm" style="max-width:60px"></div>
                <div><label class="form-label small">Ast</label>
                    <input type="number" name="assists" min="0" class="form-control form-control-sm" style="max-width:60px"></div>
                <div><label class="form-label small">Zb</label>
                    <input type="number" name="rebounds" min="0" class="form-control form-control-sm" style="max-width:60px"></div>
                <div><label class="form-label small">Prz</label>
                    <input type="number" name="steals" min="0" class="form-control form-control-sm" style="max-width:60px"></div>
                <div><label class="form-label small">Bl</label>
                    <input type="number" name="blocks" min="0" class="form-control form-control-sm" style="max-width:60px"></div>
                <div><label class="form-label small">Str</label>
                    <input type="number" name="turnovers" min="0" class="form-control form-control-sm" style="max-width:60px"></div>
                <div><label class="form-label small">Faule</label>
                    <input type="number" name="fouls" min="0" max="6" class="form-control form-control-sm" style="max-width:60px"></div>
                <div><label class="form-label small">3pkt</label>
                    <input type="number" name="three_pointers" min="0" class="form-control form-control-sm" style="max-width:60px"></div>
                <div><label class="form-label small">RW cel</label>
                    <input type="number" name="free_throws_made" min="0" class="form-control form-control-sm" style="max-width:60px"></div>
                <div><label class="form-label small">RW prób</label>
                    <input type="number" name="free_throws_attempts" min="0" class="form-control form-control-sm" style="max-width:60px"></div>
                <div><button class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> Dodaj</button></div>
            </form>

            <?php if (empty($match['player_stats'])): ?>
                <div class="text-muted small">Brak statystyk zawodników.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Zawodnik</th><th>Min</th><th>Pkt</th><th>Ast</th><th>Zb</th>
                                <th>Prz</th><th>Bl</th><th>Str</th><th>Faule</th><th>3pkt</th>
                                <th>RW</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($match['player_stats'] as $ps): ?>
                            <tr>
                                <td><?= View::e($ps['last_name']) ?> <?= View::e($ps['first_name']) ?></td>
                                <td><?= $ps['minutes'] !== null ? (int)$ps['minutes'] : '' ?></td>
                                <td><strong><?= $ps['points'] !== null ? (int)$ps['points'] : '' ?></strong></td>
                                <td><?= $ps['assists'] !== null ? (int)$ps['assists'] : '' ?></td>
                                <td><?= $ps['rebounds'] !== null ? (int)$ps['rebounds'] : '' ?></td>
                                <td><?= $ps['steals'] !== null ? (int)$ps['steals'] : '' ?></td>
                                <td><?= $ps['blocks'] !== null ? (int)$ps['blocks'] : '' ?></td>
                                <td><?= $ps['turnovers'] !== null ? (int)$ps['turnovers'] : '' ?></td>
                                <td><?= $ps['fouls'] !== null ? (int)$ps['fouls'] : '' ?></td>
                                <td><?= $ps['three_pointers'] !== null ? (int)$ps['three_pointers'] : '' ?></td>
                                <td><?= $ps['free_throws_made'] !== null ? (int)$ps['free_throws_made'] . '/' . (int)$ps['free_throws_attempts'] : '' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
