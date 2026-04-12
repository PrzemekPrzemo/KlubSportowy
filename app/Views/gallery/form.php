<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('gallery/store') ?>" class="card p-4">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Tytul albumu *</label>
            <input type="text" name="title" value="" class="form-control" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Sport</label>
            <select name="sport_id" class="form-select">
                <option value="">— ogolnoklubowy —</option>
                <?php foreach ($sports as $s): ?>
                    <option value="<?= (int)$s['id'] ?>"><?= View::e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label">Wydarzenie</label>
            <select name="event_id" class="form-select">
                <option value="">— brak powiazania —</option>
                <?php foreach ($events as $e): ?>
                    <option value="<?= (int)$e['id'] ?>"><?= View::e($e['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">&nbsp;</label>
            <div class="form-check mt-2">
                <input type="checkbox" name="is_public" value="1" id="isPublic" class="form-check-input">
                <label class="form-check-label" for="isPublic">Album publiczny</label>
            </div>
        </div>
        <div class="col-12">
            <label class="form-label">Opis</label>
            <textarea name="description" class="form-control" rows="4"></textarea>
        </div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Utworz album</button>
        <a href="<?= url('gallery') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
