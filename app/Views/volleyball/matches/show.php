<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <div class="row text-center">
        <div class="col-5"><h4><?= View::e($match['home_team_name']) ?></h4></div>
        <div class="col-2">
            <?php if ($match['home_sets'] !== null): ?>
                <h2><?= (int)$match['home_sets'] ?> : <?= (int)$match['away_sets'] ?></h2>
            <?php else: ?>
                <h2>vs</h2>
            <?php endif; ?>
            <small class="text-muted"><?= format_datetime($match['match_date']) ?></small>
        </div>
        <div class="col-5"><h4><?= View::e($match['away_team']) ?></h4></div>
    </div>

    <?php if ($match['set1_home'] !== null): ?>
    <div class="text-center mt-2">
        <table class="table table-sm table-bordered mx-auto" style="max-width:400px">
            <thead class="table-light"><tr><th></th><?php for ($i = 1; $i <= 5; $i++): ?><th>Set <?= $i ?></th><?php endfor; ?><th>Suma</th></tr></thead>
            <tbody>
                <tr>
                    <td><strong><?= View::e($match['home_team_name']) ?></strong></td>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <td><?= $match["set{$i}_home"] !== null ? (int)$match["set{$i}_home"] : '—' ?></td>
                    <?php endfor; ?>
                    <td><strong><?= $match['home_score'] !== null ? (int)$match['home_score'] : '—' ?></strong></td>
                </tr>
                <tr>
                    <td><strong><?= View::e($match['away_team']) ?></strong></td>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <td><?= $match["set{$i}_away"] !== null ? (int)$match["set{$i}_away"] : '—' ?></td>
                    <?php endfor; ?>
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
        <a href="<?= url('volleyball/matches/' . (int)$match['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edytuj</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-12">
        <div class="card p-3">
            <h5 class="mb-3">Statystyki zawodników</h5>
            <form method="POST" action="<?= url('volleyball/matches/' . (int)$match['id'] . '/stats') ?>" class="d-flex gap-2 mb-3 flex-wrap align-items-end">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label small mb-0">Zawodnik</label>
                    <select name="member_id" class="form-select form-select-sm" style="max-width:200px" required>
                        <option value="">— zawodnik —</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label class="form-label small mb-0">Ataki</label>
                    <input type="number" name="attacks" min="0" class="form-control form-control-sm" style="max-width:70px"></div>
                <div><label class="form-label small mb-0">Zabójcze</label>
                    <input type="number" name="kills" min="0" class="form-control form-control-sm" style="max-width:70px"></div>
                <div><label class="form-label small mb-0">Bloki</label>
                    <input type="number" name="blocks" min="0" class="form-control form-control-sm" style="max-width:70px"></div>
                <div><label class="form-label small mb-0">Serwisy</label>
                    <input type="number" name="serves" min="0" class="form-control form-control-sm" style="max-width:70px"></div>
                <div><label class="form-label small mb-0">Asy</label>
                    <input type="number" name="aces" min="0" class="form-control form-control-sm" style="max-width:70px"></div>
                <div><label class="form-label small mb-0">Przyjęcia</label>
                    <input type="number" name="digs" min="0" class="form-control form-control-sm" style="max-width:70px"></div>
                <div><label class="form-label small mb-0">Błędy</label>
                    <input type="number" name="errors" min="0" class="form-control form-control-sm" style="max-width:70px"></div>
                <div><label class="form-label small mb-0">Sety</label>
                    <input type="number" name="sets_played" min="0" max="5" class="form-control form-control-sm" style="max-width:60px"></div>
                <div><button class="btn btn-sm btn-primary"><i class="bi bi-plus"></i></button></div>
            </form>

            <?php if (empty($match['player_stats'])): ?>
                <div class="text-muted small">Brak statystyk.</div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Zawodnik</th><th>Ataki</th><th>Zabójcze</th><th>Bloki</th><th>Serwisy</th><th>Asy</th><th>Przyjęcia</th><th>Błędy</th><th>Sety</th></tr></thead>
                    <tbody>
                    <?php foreach ($match['player_stats'] as $ps): ?>
                        <tr>
                            <td><?= View::e($ps['last_name']) ?> <?= View::e($ps['first_name']) ?></td>
                            <td><?= $ps['attacks'] !== null ? (int)$ps['attacks'] : '—' ?></td>
                            <td><strong><?= $ps['kills'] !== null ? (int)$ps['kills'] : '—' ?></strong></td>
                            <td><?= $ps['blocks'] !== null ? (int)$ps['blocks'] : '—' ?></td>
                            <td><?= $ps['serves'] !== null ? (int)$ps['serves'] : '—' ?></td>
                            <td><?= $ps['aces'] !== null ? (int)$ps['aces'] : '—' ?></td>
                            <td><?= $ps['digs'] !== null ? (int)$ps['digs'] : '—' ?></td>
                            <td><?= $ps['errors'] !== null ? (int)$ps['errors'] : '—' ?></td>
                            <td><?= $ps['sets_played'] !== null ? (int)$ps['sets_played'] : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
