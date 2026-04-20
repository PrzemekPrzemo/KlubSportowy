<?php use App\Helpers\View; ?>

<div class="mb-3 d-flex gap-2">
    <a href="<?= url('admin/clubs') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Wróć do klubów</a>
    <a href="<?= url('admin/clubs/' . (int)$club['id'] . '/config') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-sliders"></i> Konfiguracja</a>
    <a href="<?= url('admin/clubs/' . (int)$club['id'] . '/features') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-toggles"></i> Feature flags</a>
</div>

<?php if (empty($sportKey)): ?>
<!-- ── Przegląd wszystkich sekcji sportowych ──────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-grid-3x3-gap"></i> Konfiguracja sekcji sportowych — <?= View::e($club['name'] ?? '') ?></h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($sports)): ?>
            <div class="p-4 text-muted">Brak aktywnych sekcji sportowych dla tego klubu.</div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Sport</th>
                        <th>Klucz federacyjny</th>
                        <th>Zawodnicy</th>
                        <th>Kategorie wiekowe</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sports as $s): ?>
                    <tr>
                        <td>
                            <strong><?= View::e($s['name']) ?></strong>
                            <small class="text-muted d-block"><?= View::e($s['key']) ?></small>
                        </td>
                        <td>
                            <?php if (!empty($s['federation_id'])): ?>
                                <code><?= View::e($s['federation_id']) ?></code>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-secondary"><?= (int)($s['member_count'] ?? 0) ?></span></td>
                        <td>
                            <?php
                            $cats = $s['age_categories'] ?? [];
                            if (is_string($cats)) $cats = json_decode($cats, true) ?: [];
                            ?>
                            <?php if (!empty($cats)): ?>
                                <span class="text-muted small"><?= count($cats) ?> kat.</span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('admin/clubs/' . (int)$club['id'] . '/sports/' . urlencode($s['key'])) ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-gear"></i> Ustawienia
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- ── Ustawienia konkretnego sportu ─────────────────────────────────────── -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-gear"></i>
            Ustawienia sekcji: <strong><?= View::e($manifest['name'] ?? $sportKey) ?></strong>
            <small class="text-muted"> — <?= View::e($club['name'] ?? '') ?></small>
        </h5>
        <a href="<?= url('admin/clubs/' . (int)$club['id'] . '/sports') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-grid-3x3-gap"></i> Wszystkie sporty
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= url('admin/clubs/' . (int)$club['id'] . '/sports/' . urlencode($sportKey) . '/save') ?>">
            <?= csrf_field() ?>

            <div class="mb-4">
                <h6 class="text-muted"><i class="bi bi-building-check me-1"></i>Federacja</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">ID / numer federacyjny klubu</label>
                        <input type="text" name="federation_id" class="form-control"
                               value="<?= View::e($currentSettings['federation_id'] ?? '') ?>"
                               placeholder="np. PZJ-1234">
                        <div class="form-text">Numer rejestracyjny klubu w federacji <?= View::e($manifest['federation'] ?? '') ?>.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Federacja</label>
                        <input type="text" class="form-control" value="<?= View::e($manifest['federation'] ?? '—') ?>" disabled>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="text-muted"><i class="bi bi-people me-1"></i>Kategorie wiekowe</h6>
                <div class="mb-2">
                    <label class="form-label">Kategorie (JSON) <small class="text-muted">— tablica obiektów z kluczami name, min_age, max_age</small></label>
                    <textarea name="age_categories" class="form-control font-monospace" rows="5"
                              placeholder='[{"name":"Junior","min_age":15,"max_age":17},{"name":"Senior","min_age":18,"max_age":null}]'><?= View::e($currentSettings['age_categories'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="text-muted"><i class="bi bi-list-ul me-1"></i>Niestandardowe pola</h6>
                <div class="mb-2">
                    <label class="form-label">Pola własne (JSON) <small class="text-muted">— tablica obiektów z kluczami key, label, type</small></label>
                    <textarea name="custom_fields" class="form-control font-monospace" rows="5"
                              placeholder='[{"key":"coach_license","label":"Numer licencji trenera","type":"text"}]'><?= View::e($currentSettings['custom_fields'] ?? '') ?></textarea>
                </div>
            </div>

            <?php if (!empty($manifest['features'])): ?>
            <div class="mb-4">
                <h6 class="text-muted"><i class="bi bi-check2-square me-1"></i>Aktywne funkcjonalności modułu</h6>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($manifest['features'] as $feat): ?>
                        <span class="badge bg-success"><?= View::e($feat) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="form-text mt-1">Funkcjonalności są konfigurowane w manifeście sportu i nie można ich zmienić tutaj.</div>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Zapisz ustawienia</button>
                <a href="<?= url('admin/clubs/' . (int)$club['id'] . '/sports') ?>" class="btn btn-outline-secondary">Anuluj</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
