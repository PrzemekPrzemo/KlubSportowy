<?php use App\Helpers\View;
$isEdit = !empty($match);
$action = $isEdit ? url('football/matches/' . (int)$match['id'] . '/update') : url('football/matches/store');
$md = !empty($match['match_date']) ? str_replace(' ', 'T', substr($match['match_date'], 0, 16)) : '';
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Drużyna domowa *</label>
            <select name="home_team_id" class="form-select" required>
                <option value="">—</option>
                <?php foreach ($teams as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= (int)($match['home_team_id'] ?? 0)===(int)$t['id']?'selected':'' ?>><?= View::e($t['name']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label">Przeciwnik *</label>
            <input type="text" name="away_team" value="<?= View::e($match['away_team'] ?? '') ?>" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Data i godzina *</label>
            <input type="datetime-local" name="match_date" value="<?= View::e($md) ?>" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Miejsce</label>
            <input type="text" name="location" value="<?= View::e($match['location'] ?? '') ?>" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Typ</label>
            <select name="match_type" class="form-select">
                <?php foreach (['ligowy','pucharowy','towarzyski','turniejowy'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($match['match_type'] ?? 'ligowy')===$t?'selected':'' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-2"><label class="form-label">Status</label>
            <select name="status" class="form-select">
                <?php foreach (['zaplanowany','w_trakcie','zakonczony','odwolany'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($match['status'] ?? 'zaplanowany')===$s?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-2"><label class="form-label">Kolejka</label>
            <input type="text" name="league_round" value="<?= View::e($match['league_round'] ?? '') ?>" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Sędzia</label>
            <input type="text" name="referee" value="<?= View::e($match['referee'] ?? '') ?>" class="form-control"></div>
        <div class="col-md-1"><label class="form-label">Gosp.</label>
            <input type="number" name="home_score" value="<?= View::e($match['home_score'] ?? '') ?>" min="0" class="form-control"></div>
        <div class="col-md-1"><label class="form-label">Gość</label>
            <input type="number" name="away_score" value="<?= View::e($match['away_score'] ?? '') ?>" min="0" class="form-control"></div>
        <div class="col-12"><label class="form-label">Notatki</label>
            <textarea name="notes" class="form-control" rows="2"><?= View::e($match['notes'] ?? '') ?></textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('football/matches') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
