<?php use App\Helpers\View;
$isEdit = !empty($team);
$action = $isEdit ? url('volleyball/teams/' . (int)$team['id'] . '/update') : url('volleyball/teams/store');
?>
<form method="POST" action="<?= $action ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nazwa *</label>
            <input type="text" name="name" value="<?= View::e($team['name'] ?? '') ?>" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Liga</label>
            <input type="text" name="league" value="<?= View::e($team['league'] ?? '') ?>" class="form-control"></div>
    </div>
    <?php if ($isEdit): ?>
    <div class="form-check mt-3">
        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" <?= ($team['is_active'] ?? 1) ? 'checked' : '' ?>>
        <label class="form-check-label" for="is_active">Aktywna</label>
    </div>
    <?php endif; ?>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('volleyball/teams') ?>" class="btn btn-outline-secondary">Anuluj</a>
        <?php if ($isEdit): ?>
            <form method="POST" action="<?= url('volleyball/teams/' . (int)$team['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')" class="ms-auto m-0">
                <?= csrf_field() ?><button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
        <?php endif; ?>
    </div>
</form>
