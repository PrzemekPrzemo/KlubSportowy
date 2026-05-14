<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('football/leagues/store') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Nazwa ligi *</label>
            <input type="text" name="name" class="form-control" required placeholder="np. Liga Okręgowa Seniorów">
        </div>
        <div class="col-md-4">
            <label class="form-label">Sezon *</label>
            <input type="text" name="season" class="form-control" required placeholder="np. 2024/25" maxlength="10">
        </div>
        <div class="col-md-6">
            <label class="form-label">Data rozpoczęcia</label>
            <input type="date" name="start_date" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Data zakończenia</label>
            <input type="date" name="end_date" class="form-control">
        </div>
        <?php if (!empty($teams)): ?>
        <div class="col-12">
            <label class="form-label">Drużyny w lidze</label>
            <div class="row row-cols-1 row-cols-md-3 g-2">
                <?php foreach ($teams as $t): ?>
                    <div class="col">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="team_ids[]" value="<?= (int)$t['id'] ?>" id="team_<?= (int)$t['id'] ?>">
                            <label class="form-check-label" for="team_<?= (int)$t['id'] ?>"><?= View::e($t['name']) ?></label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Utwórz ligę</button>
        <a href="<?= url('football/leagues') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
