<?php use App\Helpers\View; use App\Models\CampaignModel; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-megaphone text-primary me-2"></i>
        Kampanie email / SMS
    </h3>
    <a href="<?= url('admin/campaigns/new') ?>" class="btn btn-success">
        <i class="bi bi-plus"></i> Nowa kampania
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('success')): ?>
    <div class="alert alert-success"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Nazwa</th>
                <th>Kanał</th>
                <th>Odbiorcy</th>
                <th>Wysłano / Błędy</th>
                <th>Status</th>
                <th>Utworzona</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($campaigns)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Brak kampanii. <a href="<?= url('admin/campaigns/new') ?>">Utwórz pierwszą</a>.</td></tr>
            <?php else: ?>
                <?php foreach ($campaigns as $c): ?>
                    <tr>
                        <td><strong><?= View::e($c['name']) ?></strong></td>
                        <td><span class="badge bg-secondary"><?= View::e(CampaignModel::$CHANNELS[$c['channel']] ?? $c['channel']) ?></span></td>
                        <td><?= (int)$c['recipients_count'] ?></td>
                        <td>
                            <span class="text-success"><?= (int)$c['sent_count'] ?></span> /
                            <span class="text-danger"><?= (int)$c['failed_count'] ?></span>
                        </td>
                        <td>
                            <?php
                            $statusColors = [
                                'draft' => 'secondary', 'scheduled' => 'info',
                                'sending' => 'warning', 'sent' => 'success', 'failed' => 'danger',
                            ];
                            $color = $statusColors[$c['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $color ?>"><?= View::e(CampaignModel::$STATUSES[$c['status']] ?? $c['status']) ?></span>
                        </td>
                        <td><?= View::e($c['created_at']) ?></td>
                        <td>
                            <a href="<?= url('admin/campaigns/' . (int)$c['id']) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
