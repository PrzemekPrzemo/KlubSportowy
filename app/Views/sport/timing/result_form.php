<?php
use App\Helpers\View;
/** @var string $sportKey */
/** @var array $manifest */
/** @var array $members */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-stopwatch text-primary me-2"></i>
        Nowy wynik — <?= View::e($manifest['name'] ?? $sportKey) ?>
    </h3>
    <a href="<?= url('club/sport/' . $sportKey . '/results') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Wróć do listy
    </a>
</div>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= View::e($flashError) ?></div>
<?php endif; ?>

<form method="POST" action="<?= url('club/sport/' . $sportKey . '/result/store') ?>" class="card shadow-sm p-3">
    <?= csrf_field() ?>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Zawodnik *</label>
            <select name="member_id" class="form-select" required>
                <option value="">— wybierz zawodnika —</option>
                <?php foreach ($members as $m): ?>
                    <option value="<?= (int)$m['id'] ?>">
                        <?= View::e($m['last_name'] . ' ' . $m['first_name']) ?>
                        <?= !empty($m['member_number']) ? ' (' . View::e($m['member_number']) . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Data wyniku *</label>
            <input type="date" name="recorded_at" value="<?= date('Y-m-d') ?>" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Konkurencja (event_name) *</label>
            <input type="text" name="event_name" class="form-control"
                   placeholder="np. 100m freestyle / Road race U23" required>
        </div>

        <div class="col-md-3">
            <label class="form-label">Dystans [m]</label>
            <input type="number" name="distance_m" min="0" class="form-control" value="0">
        </div>

        <div class="col-md-3">
            <label class="form-label">Kategoria</label>
            <input type="text" name="category" class="form-control" placeholder="np. U23, Junior">
        </div>

        <div class="col-md-12">
            <label class="form-label">Czas wyniku *</label>
            <div class="input-group">
                <input type="number" name="time_min" min="0" placeholder="min" class="form-control">
                <span class="input-group-text">:</span>
                <input type="number" name="time_sec" min="0" max="59" placeholder="sec" class="form-control">
                <span class="input-group-text">.</span>
                <input type="number" name="time_cs" min="0" max="99" placeholder="cs" class="form-control">
            </div>
            <div class="form-text">Format mm:ss.cc — lub wprowadź czas w ms poniżej.</div>
            <input type="number" name="finish_time_ms" min="0" class="form-control mt-2" placeholder="finish_time_ms (alternatywnie)">
        </div>

        <div class="col-md-3">
            <label class="form-label">Kary [s]</label>
            <input type="number" step="0.01" name="penalties_seconds" value="0" class="form-control">
        </div>

        <div class="col-md-3">
            <label class="form-label">Miejsce (rank)</label>
            <input type="number" name="rank" min="0" class="form-control">
        </div>

        <div class="col-md-6">
            <label class="form-label">Warunki pogodowe</label>
            <input type="text" name="weather_conditions" class="form-control" placeholder="np. wiatr 2 m/s, deszcz">
        </div>

        <div class="col-md-6">
            <label class="form-label">Splity (czas per okrążenie / leg) — każdy w nowej linii</label>
            <textarea name="splits_raw" class="form-control" rows="4"
                      placeholder="0:30.45&#10;1:01.20&#10;1:32.10"></textarea>
            <div class="form-text">Format mm:ss.cc lub ms numerycznie.</div>
        </div>

        <div class="col-md-6">
            <label class="form-label">Metadata (JSON) — sport-specific</label>
            <textarea name="metadata_json" class="form-control" rows="4"
                      placeholder='{"boat_class":"K2","paddle_hand":"R"}'></textarea>
            <div class="form-text">np. boat_class, snow_style, shooting_misses.</div>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2-circle"></i> Zapisz wynik
        </button>
        <a href="<?= url('club/sport/' . $sportKey . '/results') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>
