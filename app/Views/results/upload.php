<?php use App\Helpers\View; ?>

<div class="container-fluid py-4">
    <h2><i class="bi bi-upload"></i> Dodaj zdjecie wyniku</h2>

    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="<?= url('results/upload') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="image" class="form-label">Zdjecie / skan wyniku *</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                            <small class="text-muted">Dozwolone formaty: JPG, PNG, GIF, WebP, BMP</small>
                        </div>

                        <div class="mb-3">
                            <label for="event_id" class="form-label">Wydarzenie (opcjonalnie)</label>
                            <select class="form-select" id="event_id" name="event_id">
                                <option value="">-- brak --</option>
                                <?php foreach ($events as $ev): ?>
                                    <option value="<?= (int)$ev['id'] ?>">
                                        <?= View::e($ev['name']) ?> (<?= format_date($ev['event_date'] ?? '') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="member_id" class="form-label">Zawodnik (opcjonalnie)</label>
                            <select class="form-select" id="member_id" name="member_id">
                                <option value="">-- brak --</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>">
                                        <?= View::e($m['last_name'] . ' ' . $m['first_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="sport_id" class="form-label">Sport (opcjonalnie)</label>
                            <select class="form-select" id="sport_id" name="sport_id">
                                <option value="">-- brak --</option>
                                <?php foreach ($sports as $sp): ?>
                                    <option value="<?= (int)$sp['id'] ?>">
                                        <?= View::e($sp['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Przeslij
                    </button>
                    <a href="<?= url('results') ?>" class="btn btn-secondary">Anuluj</a>
                </div>
            </form>
        </div>
    </div>
</div>
