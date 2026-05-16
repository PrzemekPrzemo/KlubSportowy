<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-file-earmark-medical me-2"></i>Prosba GDPR #<?= (int)$request['id'] ?></h4>
        <small class="text-muted"><?= View::e($types[$request['request_type']] ?? $request['request_type']) ?></small>
    </div>
    <a href="<?= url('admin/gdpr') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Powrot
    </a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Szczegoly prosby</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Czlonek</dt>
                    <dd class="col-sm-8">
                        <?= View::e($request['first_name'] . ' ' . $request['last_name']) ?>
                        <?php if (!empty($request['member_number'])): ?>
                            <span class="text-muted">(#<?= View::e($request['member_number']) ?>)</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">E-mail</dt>
                    <dd class="col-sm-8"><?= View::e($request['email'] ?? '—') ?></dd>

                    <dt class="col-sm-4">Typ prosby</dt>
                    <dd class="col-sm-8"><?= View::e($types[$request['request_type']] ?? $request['request_type']) ?></dd>

                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <?php
                        $statusClass = match($request['status']) {
                            'completed'   => 'success',
                            'in_progress' => 'info',
                            'pending'     => 'warning',
                            'rejected'    => 'danger',
                            default       => 'secondary',
                        };
                        ?>
                        <span class="badge bg-<?= $statusClass ?>"><?= View::e($statuses[$request['status']] ?? $request['status']) ?></span>
                    </dd>

                    <dt class="col-sm-4">Zlozono</dt>
                    <dd class="col-sm-8"><?= View::e($request['requested_at']) ?></dd>

                    <dt class="col-sm-4">Potwierdzono</dt>
                    <dd class="col-sm-8"><?= View::e($request['confirmed_at'] ?? '— (oczekuje na klikniecie linku)') ?></dd>

                    <dt class="col-sm-4">Zrealizowano</dt>
                    <dd class="col-sm-8"><?= View::e($request['processed_at'] ?? '—') ?></dd>

                    <dt class="col-sm-4">Powod (od czlonka)</dt>
                    <dd class="col-sm-8"><?= View::e($request['reason'] ?? '— (nie podano)') ?></dd>

                    <dt class="col-sm-4">Notatki admina</dt>
                    <dd class="col-sm-8"><?= View::e($request['notes'] ?? '—') ?></dd>

                    <dt class="col-sm-4">IP</dt>
                    <dd class="col-sm-8"><code><?= View::e($request['ip_address'] ?? '—') ?></code></dd>

                    <dt class="col-sm-4">User agent</dt>
                    <dd class="col-sm-8"><small class="text-muted"><?= View::e($request['user_agent'] ?? '—') ?></small></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <?php if ($request['status'] === 'pending' || $request['status'] === 'in_progress'): ?>
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="bi bi-gear me-1"></i>Akcja administratora</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('admin/gdpr/' . (int)$request['id'] . '/process') ?>">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label small">Notatki (widoczne dla czlonka)</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="3" maxlength="500"></textarea>
                        </div>
                        <button type="submit" name="action" value="approve" class="btn btn-success w-100 mb-2"
                                onclick="return confirm('Wykonac prosbe? Operacja moze byc nieodwracalna (dla delete).');">
                            <i class="bi bi-check-lg me-1"></i> Zatwierdz i wykonaj
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-outline-danger w-100"
                                onclick="return confirm('Odrzucic prosbe?');">
                            <i class="bi bi-x-lg me-1"></i> Odrzuc
                        </button>
                    </form>
                </div>
            </div>
        <?php elseif ($request['status'] === 'completed' && $request['request_type'] === 'export' && !empty($request['export_file_path'])): ?>
            <div class="card border-success">
                <div class="card-body">
                    <h6><i class="bi bi-file-zip me-1"></i>Plik eksportu ZIP</h6>
                    <?php
                    $path = (string)$request['export_file_path'];
                    $exists = is_file($path);
                    $size   = $exists ? @filesize($path) : false;
                    $sizeKB = $size !== false ? number_format($size / 1024, 1, ',', ' ') . ' kB' : '—';
                    $expired = !empty($request['export_file_expires_at'])
                        && strtotime($request['export_file_expires_at']) < time();
                    $generationSeconds = null;
                    if (!empty($request['processed_at']) && !empty($request['confirmed_at'])) {
                        $generationSeconds = max(0,
                            strtotime((string)$request['processed_at'])
                            - strtotime((string)$request['confirmed_at']));
                    }
                    ?>
                    <dl class="row small mb-2">
                        <dt class="col-5">Plik</dt>
                        <dd class="col-7"><code><?= View::e(basename($path)) ?></code></dd>
                        <dt class="col-5">Rozmiar</dt>
                        <dd class="col-7"><?= View::e($sizeKB) ?></dd>
                        <dt class="col-5">Czas generacji</dt>
                        <dd class="col-7"><?= $generationSeconds !== null ? (int)$generationSeconds . ' s' : '—' ?></dd>
                        <dt class="col-5">Wygasa</dt>
                        <dd class="col-7">
                            <?= View::e($request['export_file_expires_at'] ?? '—') ?>
                            <?php if ($expired): ?>
                                <span class="badge bg-warning text-dark ms-1">wygasl</span>
                            <?php endif; ?>
                        </dd>
                        <dt class="col-5">Plik na dysku</dt>
                        <dd class="col-7">
                            <?php if ($exists): ?>
                                <span class="text-success">istnieje</span>
                            <?php else: ?>
                                <span class="text-danger">brak (cleanup?)</span>
                            <?php endif; ?>
                        </dd>
                    </dl>

                    <form method="POST"
                          action="<?= url('admin/gdpr/' . (int)$request['id'] . '/regenerate') ?>"
                          onsubmit="return confirm('Usunac istniejacy ZIP i wygenerowac od nowa? Czlonek otrzyma nowy plik (waznosc 7 dni).');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-warning w-100">
                            <i class="bi bi-arrow-clockwise me-1"></i> Force regenerate
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
