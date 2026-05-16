<?php
/**
 * Sponsor logo rotation widget — używany w portalu zawodnika.
 *
 * Wymaga $currentClubId w zakresie (typowo z BaseController::render()).
 * Loaduje aktywne sponsory (display_in_portal=1, kontrakt aktywny dziś)
 * i renderuje 3-5 logo z weight-aware sortowaniem + RAND() w SQL.
 *
 * Ekspozycja jest rejestrowana server-side przy renderze (best-effort).
 *
 * Bezpieczny dla kontekstów bez kluba — renderuje nic.
 */

use App\Helpers\View;
use App\Models\SponsorModel;

if (empty($currentClubId)) {
    return;
}

try {
    $sponsorModel    = new SponsorModel();
    $widgetSponsors  = $sponsorModel->activeForClub((int)$currentClubId, 'portal', 5);
} catch (\Throwable $e) {
    error_log('sponsors_widget load failed: ' . $e->getMessage());
    return;
}

if (empty($widgetSponsors)) {
    return;
}

// Rejestruj ekspozycje (best-effort)
$memberIdForExposure = null;
try {
    if (class_exists('\\App\\Helpers\\MemberAuth')) {
        $memberIdForExposure = \App\Helpers\MemberAuth::id();
    }
} catch (\Throwable) {}

foreach ($widgetSponsors as $sw) {
    $sponsorModel->recordExposure((int)$sw['id'], 'portal_view', $memberIdForExposure);
}
?>
<div class="card mt-3 p-3" data-widget="sponsors">
    <h6 class="text-muted mb-3 text-uppercase small">
        <i class="bi bi-award me-1"></i> Nasi sponsorzy
    </h6>
    <div class="d-flex flex-wrap align-items-center justify-content-center gap-4">
        <?php foreach ($widgetSponsors as $sp): ?>
            <?php
                $hasLogo  = !empty($sp['logo_path']);
                $hasLink  = !empty($sp['website']);
                $altName  = View::e($sp['name']);
            ?>
            <?php if ($hasLink): ?>
                <a href="<?= View::e($sp['website']) ?>" target="_blank" rel="noopener sponsored"
                   class="text-decoration-none text-dark text-center" title="<?= $altName ?>">
            <?php else: ?>
                <div class="text-center" title="<?= $altName ?>">
            <?php endif; ?>
                <?php if ($hasLogo): ?>
                    <img src="<?= url($sp['logo_path']) ?>" alt="<?= $altName ?>"
                         style="max-height:50px;max-width:120px;object-fit:contain;filter:grayscale(0.2);opacity:0.9;">
                <?php else: ?>
                    <div class="px-3 py-2 border rounded bg-light">
                        <strong class="small"><?= $altName ?></strong>
                    </div>
                <?php endif; ?>
            <?php if ($hasLink): ?>
                </a>
            <?php else: ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
