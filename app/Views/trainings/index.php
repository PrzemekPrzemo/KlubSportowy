<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <select name="sport" class="form-select">
                <option value="">— wszystkie sekcje —</option>
                <?php foreach ($sports as $cs): ?>
                    <option value="<?= (int)$cs['club_sport_id'] ?>" <?= (int)($sportFilter ?? 0) === (int)$cs['club_sport_id'] ? 'selected' : '' ?>>
                        <?= View::e($cs['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" name="from" value="<?= View::e($_GET['from'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Filtruj</button></div>
        <div class="col-md-2"><a href="<?= url('trainings/create') ?>" class="btn btn-success w-100"><i class="bi bi-plus"></i> Nowy</a></div>
    </form>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Data i czas</th><th>Nazwa</th><th>Sport</th><th>Miejsce</th><th>Obecni</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($pagination['data'])): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Brak treningów.</td></tr>
        <?php else: ?>
            <?php foreach ($pagination['data'] as $t): ?>
                <tr>
                    <td><small><?= format_datetime($t['start_time']) ?></small></td>
                    <td><a href="<?= url('trainings/' . (int)$t['id']) ?>"><?= View::e($t['name']) ?></a></td>
                    <td>
                        <?php if (!empty($t['sport_name'])): ?>
                            <span class="sport-badge" style="background: <?= View::e($t['sport_color']) ?>"><?= View::e($t['sport_name']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= View::e($t['location'] ?? '') ?></td>
                    <td><span class="badge bg-secondary"><?= (int)$t['attendees_count'] ?><?= $t['max_participants'] ? ' / ' . (int)$t['max_participants'] : '' ?></span></td>
                    <td><small><?= View::e($t['status']) ?></small></td>
                    <td class="text-end d-flex gap-1 justify-content-end">
                        <a href="<?= url('ics/training/' . (int)$t['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Pobierz .ics"><i class="bi bi-calendar-plus"></i></a>
                        <a href="<?= url('trainings/' . (int)$t['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
