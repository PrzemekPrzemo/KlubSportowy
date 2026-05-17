<?php use App\Helpers\Csrf; use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">
        <i class="bi bi-card-checklist text-primary me-2"></i>
        Karnety — <?= View::e($sportName) ?>
    </h4>
    <a href="<?= url('club/studio/' . $sport . '/pass-types/new') ?>" class="btn btn-success btn-sm">
        <i class="bi bi-plus-circle"></i> Nowy typ
    </a>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Kod</th>
                    <th>Nazwa</th>
                    <th>Typ</th>
                    <th>Wejść</th>
                    <th>Ważność</th>
                    <th>Cena</th>
                    <th>Aktywny</th>
                    <th class="text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($types)): ?>
                    <tr><td colspan="8" class="text-muted text-center py-4">Brak typów karnetów.</td></tr>
                <?php endif; ?>
                <?php foreach ($types as $t): ?>
                    <tr>
                        <td class="font-monospace small"><?= View::e($t['code']) ?></td>
                        <td><strong><?= View::e($t['name']) ?></strong></td>
                        <td><span class="badge bg-secondary"><?= View::e($t['type']) ?></span></td>
                        <td><?= $t['classes_count'] !== null ? (int)$t['classes_count'] : '∞' ?></td>
                        <td><?= (int)$t['validity_days'] ?> dni</td>
                        <td><?= number_format($t['price_cents'] / 100, 2, ',', ' ') ?> <?= View::e($t['currency']) ?></td>
                        <td>
                            <?php if ((int)$t['active'] === 1): ?>
                                <span class="badge bg-success">Tak</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Nie</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= url('club/studio/' . $sport . '/pass-types/' . (int)$t['id'] . '/edit') ?>"
                               class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="<?= url('club/studio/' . $sport . '/pass-types/' . (int)$t['id'] . '/delete') ?>"
                                  method="POST" class="d-inline"
                                  onsubmit="return confirm('Usunąć typ karnetu (jeśli ma sprzedane karnety zostanie tylko deaktywowany)?');">
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

<div class="mt-3">
    <a href="<?= url('club/studio/' . $sport . '/schedules') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-calendar-week"></i> Wróć do klas
    </a>
</div>
