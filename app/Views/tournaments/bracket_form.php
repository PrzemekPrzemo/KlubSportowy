<?php use App\Helpers\View; ?>
<?php $tid = (int)($tournament['id'] ?? 0); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Generuj drabinkę — <?= View::e($tournament['name']) ?></h4>
    <a href="<?= url('tournaments/' . $tid . '/bracket') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<?php if (!empty($hasMatches)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Uwaga:</strong> Ten turniej ma już wygenerowane mecze.
        Zaznaczenie "Nadpisz" usunie istniejące mecze.
    </div>
<?php endif; ?>

<form method="POST" action="<?= url('tournaments/' . $tid . '/bracket/generate') ?>" class="card">
    <?= csrf_field() ?>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Typ drabinki</label>
                <select name="bracket_type" class="form-select" required>
                    <option value="single_elimination" <?= ($bracketCfg['bracket_type'] ?? '') === 'single_elimination' ? 'selected' : '' ?>>Single Elimination (puchar)</option>
                    <option value="double_elimination" <?= ($bracketCfg['bracket_type'] ?? '') === 'double_elimination' ? 'selected' : '' ?>>Double Elimination (podwójna eliminacja)</option>
                    <option value="round_robin" <?= ($bracketCfg['bracket_type'] ?? '') === 'round_robin' ? 'selected' : '' ?>>Round Robin (każdy z każdym)</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Metoda seedowania</label>
                <select name="seed_method" class="form-select">
                    <option value="random"  <?= ($bracketCfg['seed_method'] ?? '') === 'random'  ? 'selected' : '' ?>>Losowa</option>
                    <option value="manual"  <?= ($bracketCfg['seed_method'] ?? '') === 'manual'  ? 'selected' : '' ?>>Ręczna (z UI seedów)</option>
                    <option value="ranking" <?= ($bracketCfg['seed_method'] ?? '') === 'ranking' ? 'selected' : '' ?>>Wg rankingu</option>
                    <option value="snake"   <?= ($bracketCfg['seed_method'] ?? '') === 'snake'   ? 'selected' : '' ?>>Snake</option>
                </select>
                <small class="text-muted">Dla "Ręczna" — najpierw uzupełnij seedy w UI seedów.</small>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" name="third_place_match" id="tpm" class="form-check-input" value="1"
                        <?= !empty($bracketCfg['third_place_match']) ? 'checked' : '' ?>>
                    <label for="tpm" class="form-check-label">Generuj mecz o 3. miejsce (tylko SE)</label>
                </div>
            </div>

            <?php if (!empty($hasMatches)): ?>
            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" name="overwrite" id="ow" class="form-check-input" value="1">
                    <label for="ow" class="form-check-label text-danger">
                        Nadpisz istniejące mecze (DESTRUKCYJNE!)
                    </label>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <hr>

        <dl class="row mb-0 small">
            <dt class="col-sm-3">Uczestnicy</dt>
            <dd class="col-sm-9"><?= count($participants) ?> zawodników</dd>
            <dt class="col-sm-3">Rozmiar bracketu (SE)</dt>
            <dd class="col-sm-9"><?= (int)$bracketSize ?> slotów (bye-ów: <?= (int)$byes ?>)</dd>
        </dl>
    </div>
    <div class="card-footer text-end">
        <button type="submit" class="btn btn-warning" onclick="return confirm('Wygenerować drabinkę?')">
            <i class="bi bi-diagram-3 me-1"></i> Generuj
        </button>
    </div>
</form>
