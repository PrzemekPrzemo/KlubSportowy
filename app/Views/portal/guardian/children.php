<?php
use App\Helpers\View;
?>
<h2 class="h5 mb-3">Moi podopieczni</h2>
<?php if (empty($children)): ?>
    <div class="gp-card">Brak przypisanych dzieci.</div>
<?php else: ?>
    <?php foreach ($children as $ch): ?>
        <a href="<?= View::e(url('portal/guardian/child/' . (int)$ch['member_id'])) ?>" class="gp-card d-block text-decoration-none text-reset">
            <div class="gp-child-tile">
                <div class="gp-avatar">
                    <?= View::e(mb_strtoupper(mb_substr((string)($ch['first_name'] ?? '?'), 0, 1))) ?>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold">
                        <?= View::e(($ch['first_name'] ?? '') . ' ' . ($ch['last_name'] ?? '')) ?>
                    </div>
                    <div class="small text-muted">
                        <?= View::e(ucfirst((string)($ch['relationship'] ?? 'parent'))) ?>
                        <?php if (!empty($ch['primary_guardian'])): ?>
                            &middot; <span class="badge bg-primary">Glowny opiekun</span>
                        <?php endif; ?>
                    </div>
                </div>
                <i class="bi bi-chevron-right text-muted"></i>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>
