<?php use App\Helpers\View; use App\Models\CampaignModel; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-megaphone text-primary me-2"></i> <?= View::e($campaign['name']) ?></h3>
    <a href="<?= url('admin/campaigns') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card p-3 text-center">
            <small class="text-muted">Kanał</small>
            <h5 class="mb-0"><?= View::e(CampaignModel::$CHANNELS[$campaign['channel']] ?? '') ?></h5>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 text-center">
            <small class="text-muted">Odbiorcy</small>
            <h5 class="mb-0"><?= (int)$campaign['recipients_count'] ?></h5>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 text-center">
            <small class="text-muted">Wysłano</small>
            <h5 class="mb-0 text-success"><?= (int)$campaign['sent_count'] ?></h5>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3 text-center">
            <small class="text-muted">Błędy</small>
            <h5 class="mb-0 text-danger"><?= (int)$campaign['failed_count'] ?></h5>
        </div>
    </div>
</div>

<div class="card p-3 mb-4">
    <h6>Treść</h6>
    <?php if (!empty($campaign['template_subject'])): ?>
        <p><strong>Temat:</strong> <?= View::e($campaign['template_subject']) ?></p>
    <?php endif; ?>
    <pre class="bg-light p-3 rounded mb-0"><?= View::e($campaign['template_body']) ?></pre>
</div>

<div class="card">
    <div class="card-header">Odbiorcy (<?= count($recipients) ?>)</div>
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Członek</th>
                <th>Kanał</th>
                <th>Adres</th>
                <th>Status</th>
                <th>Wysłano</th>
                <th>Błąd</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recipients as $r): ?>
                <tr>
                    <td><?= View::e($r['last_name'] . ' ' . $r['first_name']) ?>
                        <?php if (!empty($r['member_number'])): ?>
                            <small class="text-muted">#<?= View::e($r['member_number']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= View::e($r['channel']) ?></td>
                    <td><small><?= View::e($r['to_address']) ?></small></td>
                    <td>
                        <?php
                        $sColors = ['queued' => 'secondary', 'sent' => 'success', 'failed' => 'danger', 'bounced' => 'warning'];
                        $sColor = $sColors[$r['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $sColor ?>"><?= View::e($r['status']) ?></span>
                    </td>
                    <td><small><?= View::e($r['sent_at'] ?? '') ?></small></td>
                    <td><small class="text-danger"><?= View::e($r['error_message'] ?? '') ?></small></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
