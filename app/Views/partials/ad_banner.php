<?php
/**
 * Ad banner partial.
 * Usage: View::partial('partials/ad_banner', ['target' => 'club_panel', 'position' => 'top_banner', 'clubId' => $clubId])
 */

use App\Helpers\View;
use App\Models\AdModel;

$target   = $target ?? 'club_panel';
$position = $position ?? 'top_banner';
$clubId   = $clubId ?? null;

try {
    $adModel = new AdModel();
    $ads = $adModel->activeForTarget($target, $clubId);

    // Filter by position
    $ads = array_filter($ads, fn($a) => $a['position'] === $position);

    if (empty($ads)) {
        return;
    }

    // Pick a random ad to display
    $ad = $ads[array_rand($ads)];

    // Record impression
    $adModel->recordImpression((int)$ad['id']);
} catch (\Throwable) {
    return;
}
?>
<div class="ad-banner ad-<?= View::e($position) ?> mb-3">
    <?php if ($ad['link_url']): ?>
        <a href="<?= View::e($ad['link_url']) ?>" target="_blank" rel="noopener" class="text-decoration-none">
    <?php endif; ?>

    <?php if ($ad['image_path']): ?>
        <img src="<?= View::e($ad['image_path']) ?>" alt="<?= View::e($ad['title']) ?>" class="img-fluid w-100 rounded">
    <?php else: ?>
        <div class="alert alert-info text-center mb-0">
            <strong><?= View::e($ad['title']) ?></strong>
        </div>
    <?php endif; ?>

    <?php if ($ad['link_url']): ?>
        </a>
    <?php endif; ?>
</div>
