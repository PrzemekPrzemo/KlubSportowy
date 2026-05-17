<?php
/** @var array<string,mixed> $club */
/** @var array<int,array<string,mixed>> $sports */
/** @var string $membersApprox */
/** @var int|null $yearsActive */
/** @var string $mapJsonUrl */
/** @var string $canonicalUrl */
/** @var string $metaDescription */
/** @var string $title */
use App\Helpers\View;

$hasGeo = !empty($club['latitude']) && !empty($club['longitude']);

// JSON-LD SportsClub for SEO
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type'    => 'SportsClub',
    'name'     => (string)$club['name'],
    'url'      => $canonicalUrl,
];
if (!empty($club['description_short'])) {
    $jsonLd['description'] = (string)$club['description_short'];
}
if (!empty($club['city']) || !empty($club['address'])) {
    $jsonLd['address'] = [
        '@type'           => 'PostalAddress',
        'addressLocality' => (string)($club['city'] ?? ''),
        'streetAddress'   => (string)($club['address'] ?? ''),
        'addressCountry'  => 'PL',
    ];
}
if ($hasGeo) {
    $jsonLd['geo'] = [
        '@type'     => 'GeoCoordinates',
        'latitude'  => (float)$club['latitude'],
        'longitude' => (float)$club['longitude'],
    ];
}
if (!empty($club['contact_phone'])) {
    $jsonLd['telephone'] = (string)$club['contact_phone'];
}
if (!empty($club['website_url']) || !empty($club['website'])) {
    $jsonLd['sameAs'] = [(string)($club['website_url'] ?? $club['website'])];
}
if (!empty($club['email'])) {
    $jsonLd['email'] = (string)$club['email'];
}

$GLOBALS['__pageHead'] = '<meta name="description" content="' . View::e($metaDescription) . '">'
    . '<meta property="og:title" content="' . View::e($title) . '">'
    . '<meta property="og:description" content="' . View::e($metaDescription) . '">'
    . '<meta property="og:type" content="website">'
    . '<meta property="og:url" content="' . View::e($canonicalUrl) . '">'
    . '<link rel="canonical" href="' . View::e($canonicalUrl) . '">'
    . ($hasGeo ? '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />' : '')
    . '<script type="application/ld+json">' . json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
?>

<section class="pub-hero">
    <div class="container">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center"
                 style="width: 80px; height: 80px; font-size: 2rem; font-weight: 700;">
                <?= View::e(mb_substr((string)$club['name'], 0, 1)) ?>
            </div>
            <div>
                <h1 class="mb-1"><?= View::e($club['name']) ?></h1>
                <?php if (!empty($club['city'])): ?>
                    <div><i class="bi bi-geo-alt"></i> <?= View::e($club['city']) ?></div>
                <?php endif; ?>
                <span class="badge bg-success mt-2">
                    <i class="bi bi-check-circle"></i> Zweryfikowany w ClubDesk
                </span>
            </div>
        </div>
    </div>
</section>

<section class="container py-4">
    <div class="row g-4">
        <div class="col-lg-8">
            <?php if (!empty($club['description_short'])): ?>
                <div class="card p-4 mb-3">
                    <h4>O klubie</h4>
                    <p class="mb-0"><?= nl2br(View::e($club['description_short'])) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($sports)): ?>
                <div class="card p-4 mb-3">
                    <h4>Oferowane sporty</h4>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <?php foreach ($sports as $s): ?>
                            <a href="<?= url('discover/' . rawurlencode((string)($s['key'] ?? ''))) ?>"
                               class="text-decoration-none">
                                <span class="sport-badge"
                                      style="background: <?= View::e($s['color'] ?? '#6c757d') ?>; font-size: .9rem; padding: .35rem .7rem;">
                                    <?= View::e($s['name'] ?? '') ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($hasGeo): ?>
                <div class="card p-3 mb-3">
                    <h4>Lokalizacja</h4>
                    <?php if (!empty($club['address'])): ?>
                        <p class="text-muted small mb-2">
                            <i class="bi bi-pin-map"></i> <?= View::e($club['address']) ?>
                        </p>
                    <?php endif; ?>
                    <div id="club-map" style="height: 320px; border-radius: 6px;"></div>
                </div>
            <?php endif; ?>
        </div>

        <aside class="col-lg-4">
            <div class="card p-4 mb-3">
                <h5>Kontakt</h5>
                <?php if (!empty($club['contact_phone'])): ?>
                    <div class="mb-2">
                        <i class="bi bi-telephone"></i>
                        <a href="tel:<?= View::e(preg_replace('/[^+0-9]/', '', (string)$club['contact_phone'])) ?>">
                            <?= View::e($club['contact_phone']) ?>
                        </a>
                    </div>
                <?php endif; ?>
                <?php if (!empty($club['email'])): ?>
                    <div class="mb-2">
                        <i class="bi bi-envelope"></i>
                        <a href="mailto:<?= View::e($club['email']) ?>"><?= View::e($club['email']) ?></a>
                    </div>
                <?php endif; ?>
                <?php
                $url = (string)($club['website_url'] ?? $club['website'] ?? '');
                if ($url !== ''):
                    if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
                ?>
                    <div class="mb-2">
                        <i class="bi bi-globe"></i>
                        <a href="<?= View::e($url) ?>" target="_blank" rel="noopener nofollow">
                            <?= View::e(preg_replace('#^https?://#i', '', $url)) ?>
                        </a>
                    </div>
                <?php endif; ?>
                <?php if (!empty($club['email'])): ?>
                    <a href="mailto:<?= View::e($club['email']) ?>?subject=<?= rawurlencode('Zapis dziecka do klubu') ?>"
                       class="btn btn-primary w-100 mt-2">
                        <i class="bi bi-person-plus"></i> Zapisz dziecko
                    </a>
                <?php endif; ?>
            </div>

            <div class="card p-4 mb-3">
                <h5>Statystyki</h5>
                <div class="row text-center">
                    <div class="col-6">
                        <div class="h3 mb-0"><?= View::e($membersApprox) ?></div>
                        <small class="text-muted">członków</small>
                    </div>
                    <?php if ($yearsActive !== null): ?>
                    <div class="col-6">
                        <div class="h3 mb-0"><?= (int)$yearsActive ?></div>
                        <small class="text-muted">lat działalności</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="text-center small text-muted">
                <i class="bi bi-shield-check"></i>
                Powered by <a href="<?= url('') ?>"><strong>ClubDesk</strong></a>
            </div>
        </aside>
    </div>
</section>

<?php if ($hasGeo): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    var lat = <?= (float)$club['latitude'] ?>;
    var lng = <?= (float)$club['longitude'] ?>;
    var map = L.map('club-map').setView([lat, lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);
    L.marker([lat, lng]).addTo(map)
      .bindPopup(<?= json_encode((string)$club['name'], JSON_UNESCAPED_UNICODE) ?>).openPopup();
})();
</script>
<?php endif; ?>
