<?php use App\Helpers\View; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-image"></i> Zdjecie wyniku #<?= (int)$image['id'] ?></h2>
        <a href="<?= url('results') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Powrot do listy
        </a>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <?php
    $statusColors = ['uploaded' => 'secondary', 'processed' => 'warning', 'verified' => 'success'];
    $statusLabels = ['uploaded' => 'Przeslane', 'processed' => 'Przetworzono', 'verified' => 'Zweryfikowane'];
    $extracted = !empty($image['extracted_data']) ? json_decode($image['extracted_data'], true) : [];
    ?>

    <div class="row g-4">
        <!-- Left: Image -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><?= View::e($image['original_filename']) ?></strong>
                    <span class="badge bg-<?= $statusColors[$image['status']] ?? 'secondary' ?>">
                        <?= View::e($statusLabels[$image['status']] ?? $image['status']) ?>
                    </span>
                </div>
                <div class="card-body text-center">
                    <img src="<?= url($image['image_path']) ?>" alt="" class="img-fluid" style="max-height:600px;">
                </div>
                <div class="card-footer text-muted small">
                    Przeslano: <?= format_datetime($image['created_at']) ?>
                    <?php if (!empty($image['uploader_name'])): ?>
                        przez <?= View::e($image['uploader_name']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: Manual data entry form -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><strong>Dane wyniku (wprowadzanie reczne)</strong></div>
                <div class="card-body">
                    <form method="POST" action="<?= url('results/' . $image['id'] . '/save') ?>">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="event_id" class="form-label">Wydarzenie</label>
                            <select class="form-select" id="event_id" name="event_id">
                                <option value="">-- brak --</option>
                                <?php foreach ($events as $ev): ?>
                                    <option value="<?= (int)$ev['id'] ?>" <?= ((int)($image['event_id'] ?? 0) === (int)$ev['id']) ? 'selected' : '' ?>>
                                        <?= View::e($ev['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="member_id" class="form-label">Zawodnik</label>
                            <select class="form-select" id="member_id" name="member_id">
                                <option value="">-- brak --</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>" <?= ((int)($image['member_id'] ?? 0) === (int)$m['id']) ? 'selected' : '' ?>>
                                        <?= View::e($m['last_name'] . ' ' . $m['first_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="sport_id" class="form-label">Sport</label>
                            <select class="form-select" id="sport_id" name="sport_id">
                                <option value="">-- brak --</option>
                                <?php foreach ($sports as $sp): ?>
                                    <option value="<?= (int)$sp['id'] ?>" <?= ((int)($image['sport_id'] ?? 0) === (int)$sp['id']) ? 'selected' : '' ?>>
                                        <?= View::e($sp['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <hr>
                        <h6>Wyniki / dane ze zdjecia</h6>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="position" class="form-label">Pozycja / miejsce</label>
                                <input type="text" class="form-control" id="position" name="position"
                                       value="<?= View::e($extracted['position'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="scores" class="form-label">Wynik / punkty</label>
                                <input type="text" class="form-control" id="scores" name="scores"
                                       value="<?= View::e($extracted['scores'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="time_result" class="form-label">Czas</label>
                                <input type="text" class="form-control" id="time_result" name="time_result"
                                       placeholder="np. 12:34.56"
                                       value="<?= View::e($extracted['time_result'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="distance" class="form-label">Dystans / odleglosc</label>
                                <input type="text" class="form-control" id="distance" name="distance"
                                       placeholder="np. 5.67 m"
                                       value="<?= View::e($extracted['distance'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="points" class="form-label">Punkty (klasyfikacja)</label>
                            <input type="text" class="form-control" id="points" name="points"
                                   value="<?= View::e($extracted['points'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notatki</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?= View::e($extracted['notes'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="uploaded" <?= $image['status'] === 'uploaded' ? 'selected' : '' ?>>Przeslane</option>
                                <option value="processed" <?= $image['status'] === 'processed' ? 'selected' : '' ?>>Przetworzono</option>
                                <option value="verified" <?= $image['status'] === 'verified' ? 'selected' : '' ?>>Zweryfikowane</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-lg"></i> Zapisz dane
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
