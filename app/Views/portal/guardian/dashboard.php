<?php
use App\Helpers\View;
?>
<div class="gp-card">
    <h2 class="h5 mb-1">Witaj, <?= View::e($guardian['first_name'] ?? $guardian['email']) ?>!</h2>
    <p class="text-muted small mb-0">
        Klub: <strong><?= View::e($club['name'] ?? '—') ?></strong>
    </p>
</div>

<?php if (empty($children)): ?>
    <div class="gp-card text-center">
        <i class="bi bi-info-circle text-muted fs-1"></i>
        <p class="mt-2">Nie masz jeszcze przypisanych podopiecznych. Skontaktuj sie z klubem.</p>
    </div>
<?php else: ?>
    <h3 class="h6 text-muted text-uppercase mt-3 mb-2">Moi podopieczni</h3>
    <?php foreach ($children as $row): ?>
        <?php $ch = $row['link']; ?>
        <a href="<?= View::e(url('portal/guardian/child/' . (int)$ch['member_id'])) ?>"
           class="gp-card d-block text-decoration-none text-reset">
            <div class="gp-child-tile">
                <div class="gp-avatar">
                    <?= View::e(mb_strtoupper(mb_substr((string)($ch['first_name'] ?? '?'), 0, 1))) ?>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold">
                        <?= View::e(($ch['first_name'] ?? '') . ' ' . ($ch['last_name'] ?? '')) ?>
                    </div>
                    <div class="small text-muted">
                        Nr: <?= View::e($ch['member_number'] ?? '—') ?>
                        <?php if (!empty($ch['birth_date'])): ?>
                            &middot; ur. <?= View::e($ch['birth_date']) ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($row['unpaid_count'] > 0): ?>
                        <div class="small text-danger mt-1">
                            <i class="bi bi-exclamation-circle"></i>
                            <?= (int)$row['unpaid_count'] ?> zaleg.
                            (<?= number_format($row['unpaid_total'], 2, ',', ' ') ?> zl)
                        </div>
                    <?php endif; ?>
                </div>
                <i class="bi bi-chevron-right text-muted"></i>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<div class="gp-card">
    <h3 class="h6 mb-2"><i class="bi bi-shield-check"></i> Zgody RODO (art. 8)</h3>
    <p class="small text-muted mb-2">
        Jako opiekun masz prawo zarzadzac zgodami na przetwarzanie danych dziecka.
        Mozesz je w kazdej chwili udzielic lub odwolac.
    </p>
    <a href="<?= View::e(url('portal/guardian/children')) ?>" class="btn btn-sm btn-outline-primary">
        Zarzadzaj zgodami
    </a>
</div>
