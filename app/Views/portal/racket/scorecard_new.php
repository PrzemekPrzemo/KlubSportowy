<?php
use App\Helpers\View;
$key = $sportKey ?? '';
?>
<h4 class="mb-3"><i class="bi bi-plus-square me-2"></i><?= View::e($title ?? 'Nowy scorecard') ?></h4>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Wynik zostanie zapisany jako <strong>oczekujący na weryfikację</strong>.
    Klub potwierdzi go po sprawdzeniu.
</div>

<?php if ($key === 'golf'): ?>
<form method="POST" action="<?= url('portal/sport/golf/scorecard/store') ?>" class="card">
    <?= csrf_field() ?>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Data gry</label>
                <input type="date" name="played_at" value="<?= date('Y-m-d') ?>" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Pole golfowe</label>
                <select name="course_id" class="form-select">
                    <option value="">— wybierz pole —</option>
                    <?php foreach (($courses ?? []) as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= View::e($c['name']) ?>
                            (Par <?= (int)$c['par_total'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Użyty HCP</label>
                <input type="number" step="0.1" min="-5" max="54" name="handicap_used" class="form-control">
            </div>
        </div>

        <label class="form-label">Wyniki per dołek (1..18)</label>
        <div class="row g-2 mb-3">
            <?php for ($i = 1; $i <= 18; $i++): ?>
            <div class="col-2 col-md-1">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><?= $i ?></span>
                    <input type="number" min="1" max="15" name="hole_scores[]" class="form-control text-center">
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <div class="mb-3">
            <label class="form-label">Notatki</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
    </div>
    <div class="card-footer text-end">
        <a href="<?= url('portal/sport/golf/my_record') ?>" class="btn btn-link">Anuluj</a>
        <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Zapisz</button>
    </div>
</form>
<?php endif; ?>

<?php if ($key === 'archery'):
    $distances = $distances ?? [18, 70, 90];
?>
<form method="POST" action="<?= url('portal/sport/archery/scorecard/store') ?>" class="card">
    <?= csrf_field() ?>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">Data strzelania</label>
                <input type="date" name="shot_at" value="<?= date('Y-m-d') ?>" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Dystans (m)</label>
                <select name="distance_m" class="form-select" required>
                    <?php foreach ($distances as $d): ?>
                        <option value="<?= (int)$d ?>"><?= (int)$d ?>m</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Strzał/end</label>
                <input type="number" min="1" max="12" name="arrows_per_end" value="6" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Liczba endów</label>
                <input type="number" min="1" max="20" name="total_ends" value="6" class="form-control" id="endsCount">
            </div>
        </div>

        <label class="form-label">Wyniki per end (0..10 lub X)</label>
        <div id="endsBox" class="mb-3">
            <?php for ($e = 0; $e < 6; $e++): ?>
            <div class="row g-2 mb-2 align-items-center">
                <div class="col-1 text-end"><strong>End <?= $e + 1 ?>:</strong></div>
                <?php for ($a = 0; $a < 6; $a++): ?>
                <div class="col-1">
                    <input type="text" maxlength="2" name="ends[<?= $e ?>][]" class="form-control form-control-sm text-center" placeholder="—">
                </div>
                <?php endfor; ?>
            </div>
            <?php endfor; ?>
        </div>

        <div class="mb-3">
            <label class="form-label">Notatki</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
    </div>
    <div class="card-footer text-end">
        <a href="<?= url('portal/sport/archery/my_record') ?>" class="btn btn-link">Anuluj</a>
        <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Zapisz</button>
    </div>
</form>
<?php endif; ?>
