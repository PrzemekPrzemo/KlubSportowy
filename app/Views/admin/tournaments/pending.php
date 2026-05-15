<?php
use App\Helpers\View;

/** @var array $tournaments */
/** @var array $sports */
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Turnieje oczekujące na wpisanie wyników</h4>
    <a href="<?= url('tournaments') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Wszystkie turnieje
    </a>
</div>

<p class="text-muted small">
    Lista turniejów aktywnych i zakończonych, w których pozostają mecze bez wpisanego zwycięzcy.
    Po wpisaniu wyników ranking sportowy zostanie automatycznie przeliczony.
</p>

<div class="card">
    <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th>Turniej</th>
                <th>Sport</th>
                <th>Data</th>
                <th>Status</th>
                <th class="text-end">Uczestnicy</th>
                <th class="text-end">Mecze otwarte</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($tournaments)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">
                <i class="bi bi-check-circle me-1"></i> Wszystkie turnieje mają komplet wyników.
            </td></tr>
        <?php else: ?>
            <?php foreach ($tournaments as $t):
                $sportName = $sports[$t['sport_key']]['name'] ?? $t['sport_key'];
                $statusLabels = [
                    'draft'    => ['Szkic',      'bg-secondary'],
                    'active'   => ['Aktywny',    'bg-success'],
                    'finished' => ['Zakończony', 'bg-primary'],
                ];
                $st = $statusLabels[$t['status']] ?? [$t['status'], 'bg-secondary'];
                $open = (int)($t['open_matches'] ?? 0);
            ?>
                <tr<?= $open > 0 ? ' class="table-warning"' : '' ?>>
                    <td>
                        <a href="<?= url('tournaments/' . (int)$t['id']) ?>"><?= View::e($t['name']) ?></a>
                    </td>
                    <td><?= View::e($sportName) ?></td>
                    <td><?= format_date($t['date_start']) ?></td>
                    <td><span class="badge <?= $st[1] ?>"><?= View::e($st[0]) ?></span></td>
                    <td class="text-end"><?= (int)$t['participants_total'] ?></td>
                    <td class="text-end">
                        <?php if ($open > 0): ?>
                            <span class="badge bg-warning text-dark"><?= $open ?></span>
                        <?php else: ?>
                            <span class="badge bg-success">0</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= url('tournaments/' . (int)$t['id'] . '/results') ?>"
                           class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil-square"></i> Wpisz wyniki
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
