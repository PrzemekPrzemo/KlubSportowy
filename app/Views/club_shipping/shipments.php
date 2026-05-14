<?php
use App\Helpers\View;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-list-ul text-primary me-2"></i>
        Przesylki InPost
    </h3>
    <div>
        <a href="<?= url('club/shipping/create') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Nowa przesylka
        </a>
        <a href="<?= url('club/shipping') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-gear"></i> Konfiguracja
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Data</th>
                    <th>Czlonek</th>
                    <th>Odbiorca</th>
                    <th>Paczkomat / adres</th>
                    <th>Tracking</th>
                    <th>Rozmiar</th>
                    <th>Status</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($shipments)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            Brak przesylek. <a href="<?= url('club/shipping/create') ?>">Utworz pierwsza</a>.
                        </td>
                    </tr>
                <?php else: foreach ($shipments as $s): ?>
                    <tr>
                        <td><?= (int)$s['id'] ?></td>
                        <td class="small"><?= View::e((string)$s['created_at']) ?></td>
                        <td>
                            <?php if (!empty($s['member_id'])): ?>
                                <a href="<?= url('members/' . (int)$s['member_id']) ?>" class="small">
                                    #<?= (int)$s['member_id'] ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= View::e((string)($s['recipient_name'] ?? '—')) ?>
                            <?php if (!empty($s['recipient_email'])): ?>
                                <div class="small text-muted"><?= View::e($s['recipient_email']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <?php if (!empty($s['target_locker_id'])): ?>
                                <code><?= View::e($s['target_locker_id']) ?></code>
                            <?php else: ?>
                                <span class="text-muted">kurier</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($s['tracking_number'])): ?>
                                <code><?= View::e($s['tracking_number']) ?></code>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= View::e((string)($s['size'] ?? '')) ?></td>
                        <td><span class="badge bg-secondary"><?= View::e((string)$s['status']) ?></span></td>
                        <td>
                            <?php if (!empty($s['external_id'])): ?>
                                <a href="<?= url('club/shipping/label/' . (int)$s['id']) ?>"
                                   class="btn btn-outline-primary btn-sm"
                                   title="Pobierz etykiete PDF">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
