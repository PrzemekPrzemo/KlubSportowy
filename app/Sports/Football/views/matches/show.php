<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <div class="row text-center">
        <div class="col-5"><h4><?= View::e($match['home_team_name']) ?></h4></div>
        <div class="col-2"><h2><?= $match['home_score'] !== null ? (int)$match['home_score'] . ':' . (int)$match['away_score'] : 'vs' ?></h2>
            <small class="text-muted"><?= format_datetime($match['match_date']) ?></small></div>
        <div class="col-5"><h4><?= View::e($match['away_team']) ?></h4></div>
    </div>
    <div class="text-center small text-muted mt-1">
        <?= View::e($match['match_type']) ?> • <?= View::e($match['status']) ?>
        <?php if ($match['location']): ?> • <?= View::e($match['location']) ?><?php endif; ?>
        <?php if ($match['referee']): ?> • Sędzia: <?= View::e($match['referee']) ?><?php endif; ?>
    </div>
    <div class="text-center mt-2">
        <a href="<?= url('football/matches/' . (int)$match['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edytuj</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-3">Skład</h5>
            <form method="POST" action="<?= url('football/matches/' . (int)$match['id'] . '/lineup') ?>" class="d-flex gap-2 mb-3 flex-wrap">
                <?= csrf_field() ?>
                <select name="member_id" class="form-select form-select-sm" style="max-width:200px" required>
                    <option value="">— zawodnik —</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="team_id" value="<?= (int)$match['home_team_id'] ?>">
                <select name="position" class="form-select form-select-sm" style="max-width:80px">
                    <option value="">poz.</option>
                    <?php foreach (['BR','OB','PM','NA','SR','LS','PS','LO','PO','SO','N'] as $p): ?>
                        <option value="<?= $p ?>"><?= $p ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="jersey_number" class="form-control form-control-sm" style="max-width:60px" placeholder="Nr">
                <label class="form-check-label d-flex align-items-center gap-1">
                    <input type="checkbox" name="is_starter" value="1" checked class="form-check-input"> Start
                </label>
                <button class="btn btn-sm btn-primary"><i class="bi bi-plus"></i></button>
            </form>
            <?php if (empty($match['lineup'])): ?>
                <div class="text-muted small">Brak składu.</div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Zawodnik</th><th>Poz.</th><th>Nr</th><th>Start</th></tr></thead>
                    <tbody>
                    <?php foreach ($match['lineup'] as $l): ?>
                        <tr>
                            <td><?= View::e($l['last_name']) ?> <?= View::e($l['first_name']) ?></td>
                            <td><span class="badge bg-secondary"><?= View::e($l['position'] ?? '') ?></span></td>
                            <td><?= $l['jersey_number'] ? (int)$l['jersey_number'] : '' ?></td>
                            <td><?= $l['is_starter'] ? '<i class="bi bi-check text-success"></i>' : '<small class="text-muted">rezerwa</small>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-3">Timeline meczu</h5>
            <form method="POST" action="<?= url('football/matches/' . (int)$match['id'] . '/event') ?>" class="d-flex gap-2 mb-3 flex-wrap">
                <?= csrf_field() ?>
                <select name="member_id" class="form-select form-select-sm" style="max-width:180px" required>
                    <option value="">— zawodnik —</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="minute" min="1" max="120" class="form-control form-control-sm" style="max-width:60px" placeholder="min">
                <select name="type" class="form-select form-select-sm" style="max-width:140px">
                    <?php foreach (['gol','asysta','zolta_kartka','czerwona_kartka','zmiana_wejscie','zmiana_zejscie','kontuzja'] as $t): ?>
                        <option value="<?= $t ?>"><?= str_replace('_', ' ', $t) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-sm btn-primary"><i class="bi bi-plus"></i></button>
            </form>
            <?php if (empty($match['events'])): ?>
                <div class="text-muted small">Brak wydarzeń.</div>
            <?php else: ?>
                <?php foreach ($match['events'] as $e):
                    $icon = match($e['type']) {
                        'gol' => 'bi-check-circle text-success',
                        'asysta' => 'bi-arrow-right text-info',
                        'zolta_kartka' => 'bi-square-fill text-warning',
                        'czerwona_kartka' => 'bi-square-fill text-danger',
                        'zmiana_wejscie' => 'bi-arrow-up text-success',
                        'zmiana_zejscie' => 'bi-arrow-down text-secondary',
                        'kontuzja' => 'bi-bandaid text-danger',
                        default => 'bi-dot',
                    };
                ?>
                    <div class="d-flex gap-2 align-items-center border-bottom py-1">
                        <span class="badge bg-light text-dark" style="min-width:35px"><?= $e['minute'] ? $e['minute'] . "'" : '' ?></span>
                        <i class="bi <?= $icon ?>"></i>
                        <span><?= View::e($e['last_name']) ?> <?= View::e($e['first_name']) ?></span>
                        <small class="text-muted ms-auto"><?= str_replace('_', ' ', View::e($e['type'])) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Drag & Drop lineup editor -->
<div class="card p-3 mt-3">
    <h5><i class="bi bi-arrows-move"></i> Edytor składu (drag & drop)</h5>
    <p class="small text-muted">Kliknij pozycję na boisku, potem wybierz zawodnika z listy poniżej.</p>
    <?php
    $memberJson = json_encode(array_map(fn($m) => [
        'id' => (int)$m['id'],
        'name' => $m['last_name'] . ' ' . $m['first_name'],
        'number' => '',
    ], $members), JSON_UNESCAPED_UNICODE);
    $lineupJson = json_encode(array_map(fn($l) => [
        'member_id' => (int)$l['member_id'],
        'position' => $l['position'] ?? '',
        'name' => ($l['last_name'] ?? '') . ' ' . ($l['first_name'] ?? ''),
        'jersey_number' => $l['jersey_number'] ?? '',
    ], $match['lineup'] ?? []), JSON_UNESCAPED_UNICODE);
    ?>
    <div id="lineup-editor"
         data-sport="football"
         data-match-id="<?= (int)$match['id'] ?>"
         data-team-id="<?= (int)$match['home_team_id'] ?>"
         data-members='<?= View::e($memberJson) ?>'
         data-lineup='<?= View::e($lineupJson) ?>'
         data-save-url="<?= url('football/matches/' . (int)$match['id'] . '/lineup-save') ?>"
         data-csrf="<?= csrf_token() ?>">
    </div>
</div>
<script src="<?= url('js/lineup-editor.js') ?>"></script>
