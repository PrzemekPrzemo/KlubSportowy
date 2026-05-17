<?php use App\Helpers\Csrf; use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="bi bi-calendar-week text-primary me-2"></i>
        Klasy — <?= View::e($sportName) ?>
    </h4>
    <a href="<?= url('club/studio/' . $sport . '/schedules/new') ?>" class="btn btn-success btn-sm">
        <i class="bi bi-plus-circle"></i> Nowa klasa
    </a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Dzień</th>
                    <th>Godzina</th>
                    <th>Nazwa</th>
                    <th>Poziom</th>
                    <th>Czas</th>
                    <th>Sala</th>
                    <th>Limit</th>
                    <th>Aktywna</th>
                    <th class="text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schedules)): ?>
                    <tr><td colspan="9" class="text-muted text-center py-4">Brak klas — dodaj pierwszą.</td></tr>
                <?php endif; ?>
                <?php foreach ($schedules as $s): ?>
                    <tr>
                        <td><span class="badge bg-secondary"><?= View::e($dayLabels[(int)$s['day_of_week']] ?? '?') ?></span></td>
                        <td class="font-monospace"><?= View::e(substr((string)$s['time_start'], 0, 5)) ?></td>
                        <td>
                            <a href="<?= url('club/studio/' . $sport . '/roster/' . (int)$s['id'] . '/' . date('Y-m-d')) ?>"
                               class="text-decoration-none">
                                <strong><?= View::e($s['name']) ?></strong>
                            </a>
                        </td>
                        <td><small class="text-muted"><?= View::e($s['difficulty']) ?></small></td>
                        <td><?= (int)$s['duration_min'] ?> min</td>
                        <td><small><?= View::e($s['room'] ?? '—') ?></small></td>
                        <td><?= (int)$s['max_capacity'] ?></td>
                        <td>
                            <?php if ((int)$s['active'] === 1): ?>
                                <span class="badge bg-success">Tak</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nie</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('club/studio/' . $sport . '/schedules/' . (int)$s['id'] . '/edit') ?>"
                               class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="<?= url('club/studio/' . $sport . '/schedules/' . (int)$s['id'] . '/delete') ?>"
                                  method="POST" class="d-inline" onsubmit="return confirm('Usunąć klasę?');">
                                <?= Csrf::field() ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex gap-2 flex-wrap">
    <a href="<?= url('club/studio/' . $sport . '/pass-types') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-card-checklist"></i> Karnety
    </a>
    <a href="<?= url('club/studio/' . $sport . '/passes-report') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-graph-up"></i> Raport karnetów
    </a>
</div>
