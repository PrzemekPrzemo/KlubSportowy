<?php use App\Helpers\View; ?>

<h4 class="mb-3"><i class="bi bi-calendar-plus"></i> Nowa rezerwacja</h4>

<form method="POST" action="<?= url('portal/bookings/store') ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label">Zasób *</label>
            <select name="resource_id" class="form-select" required>
                <?php foreach (($resources ?? []) as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= (int)($resourceId ?? 0) === (int)$r['id'] ? 'selected' : '' ?>>
                        <?= View::e($r['name']) ?> (<?= View::e($r['type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Tytuł *</label>
            <input name="title" class="form-control" required placeholder="np. Indywidualny trening">
        </div>
        <div class="col-md-6">
            <label class="form-label">Start *</label>
            <input type="datetime-local" name="start_at" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Koniec *</label>
            <input type="datetime-local" name="end_at" class="form-control" required>
        </div>
        <div class="col-12">
            <label class="form-label">Opis</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
    </div>
    <div class="mt-4">
        <button class="btn btn-primary"><i class="bi bi-save"></i> Zarezerwuj</button>
        <a href="<?= url('portal/bookings') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
