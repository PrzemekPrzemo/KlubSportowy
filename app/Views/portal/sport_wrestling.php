<?php
use App\Helpers\View;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Zapasy</h3>
        <p class="text-muted mb-0">Moj profil i statystyki techniczne</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php
echo View::partial('portal/sport/wrestling/my_stats', [
    'profile' => $profile ?? null,
    'stats'   => $stats   ?? [],
    'styles'  => $styles  ?? [],
]);
?>

<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-trophy me-1"></i> Moje wyniki zawodow</div>
    <div class="card-body p-0">
        <?php if (empty($myResults ?? [])): ?>
            <p class="text-muted text-center py-4 mb-0">Brak wynikow.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Zawody</th><th>Styl</th><th>Kategoria</th><th>Miejsce</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($myResults as $r): ?>
                        <tr>
                            <td class="small"><?= View::e($r['competition_date']) ?></td>
                            <td><strong><?= View::e($r['competition_name']) ?></strong></td>
                            <td class="small"><?= View::e($styles[$r['style']] ?? $r['style']) ?></td>
                            <td class="small"><?= View::e($r['weight_class'] ?? '—') ?></td>
                            <td>
                                <?php if ($r['placement']): ?>
                                    <span class="badge bg-primary">#<?= (int)$r['placement'] ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
