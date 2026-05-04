<?php use App\Helpers\View; ?>

<?php if (empty($licenses)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>Brak zarejestrowanych licencji.
</div>
<?php else: ?>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Numer licencji</th>
                <th>Typ</th>
                <th>Dyscyplina</th>
                <th>Federacja</th>
                <th>Ważna od</th>
                <th>Ważna do</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($licenses as $l):
            $days = (int)($l['days_remaining'] ?? 999);
            $statusBadge = match($l['status']) {
                'aktywna'    => 'success',
                'wygasla'    => 'secondary',
                'zawieszona' => 'warning',
                default      => 'secondary',
            };
            $statusLabel = match($l['status']) {
                'aktywna'    => 'Aktywna',
                'wygasla'    => 'Wygasła',
                'zawieszona' => 'Zawieszona',
                default      => $l['status'],
            };
        ?>
            <tr>
                <td>
                    <code><?= View::e($l['license_number']) ?></code>
                    <?php if (!empty($l['qr_code'])): ?>
                        <a href="<?= View::e($l['qr_code']) ?>" target="_blank" class="ms-1" title="QR">
                            <i class="bi bi-qr-code-scan"></i>
                        </a>
                    <?php endif; ?>
                </td>
                <td><?= View::e($l['license_type']) ?></td>
                <td><?= View::e($l['sport_name'] ?? '—') ?></td>
                <td><?= View::e($l['federation_code'] ?? '—') ?></td>
                <td><?= View::e($l['issue_date'] ?? '—') ?></td>
                <td>
                    <?= View::e($l['valid_until']) ?>
                    <?php if ($days >= 0 && $days <= 60 && $l['status'] === 'aktywna'): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $days ?> dni</span>
                    <?php endif; ?>
                </td>
                <td><span class="badge bg-<?= $statusBadge ?>"><?= $statusLabel ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php
$expiring = array_filter($licenses, fn($l) => ($l['status'] ?? '') === 'aktywna' && (int)($l['days_remaining'] ?? 999) <= 60 && (int)($l['days_remaining'] ?? 999) >= 0);
if (!empty($expiring)):
?>
<div class="alert alert-warning mt-3">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Uwaga!</strong> Jedna lub więcej Twoich licencji wygasa w ciągu 60 dni. Skontaktuj się z sekretariatem klubu w celu odnowienia.
</div>
<?php endif; ?>
