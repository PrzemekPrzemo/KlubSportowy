<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Moje dane (RODO)</h4>
        <small class="text-muted">Self-service: eksport danych (art. 20) oraz prawo do bycia zapomnianym (art. 17).</small>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100 border-primary">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <i class="bi bi-download fs-1 text-primary me-3"></i>
                    <div>
                        <h5>Eksport moich danych</h5>
                        <p class="text-muted small mb-3">
                            Pobierz wszystkie swoje dane w formacie ZIP (JSON): profil, platnosci, frekwencja,
                            wyniki, zgody. Zgodne z art. 20 RODO (prawo do przenoszenia danych).
                        </p>
                        <a href="<?= url('portal/gdpr/export') ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-box-arrow-down me-1"></i> Zamow eksport
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100 border-danger">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <i class="bi bi-trash3 fs-1 text-danger me-3"></i>
                    <div>
                        <h5>Usuniecie konta</h5>
                        <p class="text-muted small mb-3">
                            Zazadaj usuniecia (anonimizacji) swoich danych osobowych zgodnie z art. 17 RODO
                            (prawo do bycia zapomnianym). Operacja jest nieodwracalna.
                        </p>
                        <a href="<?= url('portal/gdpr/delete-account') ?>" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-exclamation-triangle me-1"></i> Zazadaj usuniecia
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Historia moich prosb GDPR</h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($requests)): ?>
            <div class="p-4 text-center text-muted">
                <i class="bi bi-inbox fs-3"></i>
                <div class="small mt-2">Nie zlozyles jeszcze zadnej prosby GDPR.</div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Typ</th>
                            <th>Status</th>
                            <th>Zlozono</th>
                            <th>Zrealizowano</th>
                            <th>Akcja</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>#<?= (int)$r['id'] ?></td>
                            <td><?= View::e($types[$r['request_type']] ?? $r['request_type']) ?></td>
                            <td>
                                <?php
                                $statusClass = match($r['status']) {
                                    'completed'   => 'success',
                                    'in_progress' => 'info',
                                    'pending'     => 'warning',
                                    'rejected'    => 'danger',
                                    default       => 'secondary',
                                };
                                ?>
                                <span class="badge bg-<?= $statusClass ?>"><?= View::e($statuses[$r['status']] ?? $r['status']) ?></span>
                            </td>
                            <td class="small"><?= View::e($r['requested_at']) ?></td>
                            <td class="small"><?= View::e($r['processed_at'] ?? '—') ?></td>
                            <td>
                                <?php if ($r['request_type'] === 'export' && $r['status'] === 'completed'): ?>
                                    <?php
                                    $hasFile = !empty($r['export_file_path']);
                                    $expired = !$hasFile
                                        || (!empty($r['export_file_expires_at'])
                                            && strtotime($r['export_file_expires_at']) < time());
                                    $daysLeft = null;
                                    if ($hasFile && !$expired && !empty($r['export_file_expires_at'])) {
                                        $secsLeft = strtotime($r['export_file_expires_at']) - time();
                                        $daysLeft = max(0, (int)ceil($secsLeft / 86400));
                                    }
                                    ?>
                                    <?php if ($expired): ?>
                                        <span class="text-muted small d-block">
                                            <i class="bi bi-clock"></i> Plik wygasl
                                        </span>
                                        <a href="<?= url('portal/gdpr/export') ?>" class="btn btn-sm btn-outline-primary mt-1">
                                            <i class="bi bi-arrow-repeat"></i> Zloz nowa prosbe
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= url('portal/gdpr/export/' . (int)$r['id'] . '/download') ?>"
                                           class="btn btn-sm btn-success">
                                            <i class="bi bi-download"></i> Pobierz ZIP
                                        </a>
                                        <?php if ($daysLeft !== null): ?>
                                            <div class="small text-muted">
                                                wygasa za <?= (int)$daysLeft ?> <?= $daysLeft === 1 ? 'dzien' : 'dni' ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="small text-muted">do <?= View::e($r['export_file_expires_at']) ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
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

<div class="alert alert-info mt-4 small">
    <i class="bi bi-info-circle me-2"></i>
    Po zlozeniu prosby otrzymasz e-mail z linkiem potwierdzajacym (wazny 24h).
    Operacja zostanie wykonana dopiero po kliknieciu linku — to gwarantuje, ze prosbe
    zlozyl rzeczywiscie wlasciciel konta.
</div>
