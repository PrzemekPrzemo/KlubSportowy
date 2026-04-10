<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('events/store') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Nazwa *</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Typ</label>
            <select name="type" class="form-select">
                <option value="zawody">zawody</option>
                <option value="mecz">mecz</option>
                <option value="trening">trening</option>
                <option value="turniej">turniej</option>
                <option value="obóz">obóz</option>
                <option value="inny">inny</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Sport</label>
            <select name="sport_id" class="form-select">
                <option value="">— ogólnoklubowe —</option>
                <?php foreach ($sports as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= View::e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Data rozpoczęcia *</label>
            <input type="datetime-local" name="event_date" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Data zakończenia</label>
            <input type="datetime-local" name="end_date" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Miejsce</label>
            <input type="text" name="location" class="form-control">
        </div>
        <div class="col-12">
            <label class="form-label">Opis</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz</button>
        <a href="<?= url('events') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
