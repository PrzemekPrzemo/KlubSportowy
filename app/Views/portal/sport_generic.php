<?php
use App\Helpers\View;
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-trophy text-primary me-2"></i><?= View::e($title ?? 'Mój profil') ?></h3>
        <p class="text-muted mb-0">Twoja aktywność w tej sekcji sportu</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if (empty($sections)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        Brak danych w tej sekcji sportowej. Dane pojawią się gdy klub zarejestruje wyniki, treningi lub osiągnięcia.
    </div>
<?php else: foreach ($sections as $section): ?>
    <div class="card mb-3 shadow-sm">
        <div class="card-header bg-light">
            <strong><?= View::e($section['label']) ?></strong>
            <span class="badge bg-secondary ms-2"><?= count($section['rows']) ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($section['rows'])): ?>
                <p class="text-muted text-center py-3 mb-0 small">Brak wpisów</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <?php foreach ($section['columns'] as $col): ?>
                                    <th class="small text-muted text-uppercase" style="font-size:0.75rem">
                                        <?= View::e(str_replace('_', ' ', $col)) ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($section['rows'] as $row): ?>
                                <tr>
                                    <?php foreach ($section['columns'] as $col): ?>
                                        <td class="small">
                                            <?php
                                                $val = $row[$col] ?? null;
                                                if ($val === null || $val === '') {
                                                    echo '<span class="text-muted">—</span>';
                                                } elseif (is_bool($val) || in_array($val, ['0','1'], true) && in_array($col, ['rx','scaled','active','dnf','dns','retired','is_personal_best','is_active','is_permanent','paid','indoor'], true)) {
                                                    echo $val ? '<i class="bi bi-check2 text-success"></i>' : '<span class="text-muted">—</span>';
                                                } elseif (is_numeric($val) && str_contains($col, '_ms')) {
                                                    $secs = (int)$val / 1000;
                                                    echo '<span class="font-monospace">' . sprintf('%d:%05.2f', (int)($secs / 60), $secs - 60 * (int)($secs / 60)) . '</span>';
                                                } else {
                                                    echo View::e((string)$val);
                                                }
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; endif; ?>
