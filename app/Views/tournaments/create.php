<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-trophy me-2"></i>Nowy turniej</h4>
    <a href="<?= url('tournaments') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<div class="card">
    <div class="card-body" style="max-width:600px">
        <form method="POST" action="<?= url('tournaments/store') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Nazwa turnieju *</label>
                <input type="text" name="name" class="form-control" required
                       placeholder="np. Mistrzostwa Klubu 2026"
                       value="<?= View::e(old('name')) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Sport *</label>
                <select name="sport_key" class="form-select" required>
                    <option value="">— wybierz sport —</option>
                    <?php foreach ($sports as $key => $s): ?>
                        <option value="<?= View::e($key) ?>" <?= old('sport_key') === $key ? 'selected' : '' ?>>
                            <?= View::e($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Format *</label>
                <select name="format" class="form-select" required>
                    <option value="single_elimination" <?= old('format', 'single_elimination') === 'single_elimination' ? 'selected' : '' ?>>
                        Puchar (drabinka)
                    </option>
                    <option value="round_robin" <?= old('format') === 'round_robin' ? 'selected' : '' ?>>
                        Każdy z każdym
                    </option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Data rozpoczęcia *</label>
                <input type="date" name="date_start" class="form-control" required
                       value="<?= View::e(old('date_start', date('Y-m-d'))) ?>">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-trophy me-1"></i> Utwórz turniej
                </button>
                <a href="<?= url('tournaments') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
