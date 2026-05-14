<?php
use App\Helpers\View;
$statusBadge = [
    'new'         => 'primary',
    'in_progress' => 'info',
    'resolved'    => 'success',
    'wont_fix'    => 'secondary',
    'duplicate'   => 'secondary',
];
$typeLabels = [
    'bug' => 'Blad', 'feature' => 'Propozycja', 'question' => 'Pytanie', 'other' => 'Inne',
];
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-list-check"></i> Moje zgloszenia</h1>
        <a href="<?= url('support/report') ?>" class="btn btn-primary">
            <i class="bi bi-plus"></i> Nowe zgloszenie
        </a>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Typ</th>
                    <th>Tytul</th>
                    <th>Status</th>
                    <th>Data</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($reports)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Brak zgloszen.</td></tr>
                <?php else: foreach ($reports as $r): ?>
                    <tr>
                        <td>#<?= (int)$r['id'] ?></td>
                        <td><span class="badge bg-secondary"><?= View::e($typeLabels[$r['type']] ?? $r['type']) ?></span></td>
                        <td><?= View::e($r['title']) ?></td>
                        <td><span class="badge bg-<?= View::e($statusBadge[$r['status']] ?? 'secondary') ?>"><?= View::e($r['status']) ?></span></td>
                        <td><small><?= View::e($r['created_at']) ?></small></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
