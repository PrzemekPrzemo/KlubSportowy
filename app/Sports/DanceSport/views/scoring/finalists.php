<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-award me-1"></i> Finaliści — Skating system</h4>
    <a href="<?= url('dance_sport/scoring') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Powrót</a>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Promocja do finału na podstawie najlepszego (najniższego) miejsca w występach klubowych.
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Pozycja</th><th>Tancerz</th><th class="text-end">Najlepsze miejsce</th><th class="text-end">Suma punktów</th><th class="text-end">Liczba startów</th></tr>
            </thead>
            <tbody>
            <?php if (empty($finalists)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Brak danych do wyłonienia finalistów.</td></tr>
            <?php else: foreach ($finalists as $idx => $f): ?>
                <tr class="<?= $idx === 0 ? 'table-warning' : '' ?>">
                    <td><strong><?= $idx + 1 ?></strong></td>
                    <td><?= View::e($f['last_name']) ?> <?= View::e($f['first_name']) ?></td>
                    <td class="text-end"><?= $f['best_rank'] === PHP_INT_MAX ? '—' : View::e($f['best_rank']) ?></td>
                    <td class="text-end"><?= View::e(number_format($f['total_score'], 2)) ?></td>
                    <td class="text-end"><?= View::e($f['starts']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
