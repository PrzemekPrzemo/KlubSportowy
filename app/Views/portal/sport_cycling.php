<?php
use App\Helpers\View;
use App\Sports\Cycling\Models\CyclingFtpModel;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-bicycle text-primary me-2"></i>Kolarstwo</h3>
        <p class="text-muted mb-0">Moje FTP i historia wyników</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<!-- FTP card -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm h-100 border-warning">
            <div class="card-body text-center">
                <div class="text-muted small"><i class="bi bi-lightning-fill"></i> Aktualny FTP</div>
                <h1 class="display-4 mb-0 text-warning">
                    <?php if ($latestFtp): ?>
                        <?= (int)$latestFtp['ftp_watts'] ?>
                        <small class="fs-5 text-muted">W</small>
                    <?php else: ?>
                        <small class="fs-5 text-muted">—</small>
                    <?php endif; ?>
                </h1>
                <?php if ($latestFtp && $latestFtp['weight_kg']):
                    $wpkg = CyclingFtpModel::wattsPerKg((int)$latestFtp['ftp_watts'], $latestFtp['weight_kg']);
                    $cat  = $wpkg ? CyclingFtpModel::fitnessCategory($wpkg) : null;
                ?>
                    <div class="mt-2">
                        <span class="badge bg-dark fs-6"><?= number_format($wpkg, 2) ?> W/kg</span>
                        <?php if ($cat): ?>
                            <div class="mt-2"><span class="badge bg-primary"><?= View::e($cat) ?></span></div>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted d-block mt-2">Test: <?= View::e($latestFtp['test_date']) ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-graph-up me-1"></i> Historia testów FTP</div>
            <div class="card-body p-0">
                <?php if (empty($ftpHistory)): ?>
                    <p class="text-muted text-center py-4 mb-0">Brak testów FTP.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>Data</th><th>FTP (W)</th><th>W/kg</th><th>Protokół</th></tr>
                            </thead>
                            <tbody>
                            <?php foreach ($ftpHistory as $t):
                                $wpkg = CyclingFtpModel::wattsPerKg((int)$t['ftp_watts'], $t['weight_kg']);
                            ?>
                                <tr>
                                    <td class="small"><?= View::e($t['test_date']) ?></td>
                                    <td class="font-monospace fw-bold"><?= (int)$t['ftp_watts'] ?></td>
                                    <td class="font-monospace"><?= $wpkg ? number_format($wpkg, 2) : '—' ?></td>
                                    <td><small class="text-muted"><?= View::e(CyclingFtpModel::$PROTOCOLS[$t['protocol']] ?? $t['protocol']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent results -->
<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-trophy me-1"></i> Ostatnie wyniki zawodów</div>
    <div class="card-body p-0">
        <?php if (empty($results)): ?>
            <p class="text-muted text-center py-4 mb-0">Brak wyników.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Zawody</th><th>Miejsce</th><th>Typ</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $r): ?>
                            <tr>
                                <td class="small"><?= View::e($r['competition_date']) ?></td>
                                <td><strong><?= View::e($r['competition_name']) ?></strong></td>
                                <td>
                                    <?php if ($r['placement']): ?>
                                        <span class="badge bg-primary">#<?= (int)$r['placement'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-muted"><?= View::e($r['race_type'] ?? '—') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
