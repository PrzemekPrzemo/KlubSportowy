<?php use App\Helpers\View; ?>

<h4 class="mb-3"><i class="bi bi-calendar-plus"></i> Nowa rezerwacja</h4>

<form method="POST" action="<?= url('bookings/store') ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Zasób *</label>
            <select name="resource_id" class="form-select" required>
                <?php foreach (($resources ?? []) as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= (int)($resourceId ?? 0) === (int)$r['id'] ? 'selected' : '' ?>>
                        <?= View::e($r['name']) ?> (<?= View::e($r['type']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Dla zawodnika (opcjonalne)</label>
            <select name="member_id" class="form-select">
                <option value="">— bez konkretnego zawodnika —</option>
                <?php foreach (($members ?? []) as $m): ?>
                    <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Tytuł *</label>
            <input name="title" class="form-control" required placeholder="np. Trening U-14">
        </div>
        <div class="col-md-6">
            <label class="form-label">Start *</label>
            <input type="datetime-local" name="start_at" class="form-control" required value="<?= View::e($start ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Koniec *</label>
            <input type="datetime-local" name="end_at" class="form-control" required value="<?= View::e($end ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label">Uczestników</label>
            <input type="number" min="1" name="participants_count" class="form-control">
        </div>
        <div class="col-md-9">
            <label class="form-label">Opis</label>
            <input name="description" class="form-control">
        </div>

        <div class="col-12">
            <details>
                <summary class="text-muted">Powtarzanie (RRULE)</summary>
                <div class="row g-2 mt-2">
                    <div class="col-md-8">
                        <label class="form-label">Wzorzec</label>
                        <input name="recurring_pattern" class="form-control" placeholder="FREQ=WEEKLY;BYDAY=MO,WE,FR">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Do dnia</label>
                        <input type="date" name="recurring_until" class="form-control">
                    </div>
                </div>
            </details>
        </div>

        <div class="col-12">
            <label class="form-label">Notatki</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
    </div>

    <div class="mt-4">
        <button class="btn btn-primary"><i class="bi bi-save"></i> Zarezerwuj</button>
        <a href="<?= url('bookings') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
