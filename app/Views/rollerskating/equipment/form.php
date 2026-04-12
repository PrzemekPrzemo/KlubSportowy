<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('rollerskating/equipment/store') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Typ</label>
            <select name="type" class="form-select">
                <?php foreach (['wrotki','ochraniacze','kask','buty','kombinezon','inne'] as $t): ?>
                    <option value="<?= $t ?>"><?= $t ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-3"><label class="form-label">Marka</label><input type="text" name="brand" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Model</label><input type="text" name="model" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Rozmiar</label><input type="text" name="size" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Stan</label>
            <select name="condition_state" class="form-select">
                <?php foreach (['nowy','dobry','uzytkowy','do_serwisu','wycofany'] as $c): ?>
                    <option value="<?= $c ?>"><?= $c ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label">Przypisany do</label>
            <select name="member_id" class="form-select">
                <option value="">— klubowy —</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?></option>
                <?php endforeach; ?>
            </select></div>
        <div class="col-md-4"><label class="form-label">Data zakupu</label><input type="date" name="purchase_date" class="form-control"></div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('rollerskating/equipment') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
