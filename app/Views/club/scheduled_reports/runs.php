<?php
use App\Helpers\View;

$statusBadge = [
    'generated' => 'bg-info',
    'sent'      => 'bg-success',
    'failed'    => 'bg-danger',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Historia: <?= View::e($report['name']) ?></h3>
        <small class="text-muted">Ostatnie 10 uruchomien tego raportu.</small>
    </div>
    <a href="<?= url('club/scheduled-reports') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Powrot</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($runs)): ?>
            <div class="p-4 text-center text-muted">
                Brak uruchomien — cron jeszcze nie zdazyl wygenerowac tego raportu.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Wygenerowano</th>
                            <th>Wyslano</th>
                            <th>Adresaci</th>
                            <th>Rozmiar PDF</th>
                            <th>Status</th>
                            <th>Blad</th>
                            <th class="text-end">PDF</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($runs as $run): ?>
                        <tr>
                            <td class="small"><?= View::e($run['generated_at']) ?></td>
                            <td class="small"><?= $run['sent_at'] ? View::e($run['sent_at']) : '<span class="text-muted">—</span>' ?></td>
                            <td><?= $run['recipients_count'] !== null ? (int)$run['recipients_count'] : '—' ?></td>
                            <td>
                                <?php if ($run['pdf_size_bytes']): ?>
                                    <?= number_format((int)$run['pdf_size_bytes'] / 1024, 1, ',', ' ') ?> KB
                                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= View::e($statusBadge[$run['status']] ?? 'bg-secondary') ?>">
                                    <?= View::e($run['status']) ?>
                                </span>
                            </td>
                            <td class="small text-danger">
                                <?= !empty($run['error_message']) ? View::e(substr((string)$run['error_message'], 0, 120)) : '' ?>
                            </td>
                            <td class="text-end">
                                <?php if (!empty($run['pdf_path'])): ?>
                                    <a href="<?= url('club/scheduled-reports/runs/' . (int)$run['id'] . '/download') ?>"
                                       class="btn btn-sm btn-outline-primary" title="Pobierz PDF">
                                        <i class="bi bi-download"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
