<?php
/** @var array<string,mixed> $sport */
/** @var array<int,array<string,mixed>> $clubs */
/** @var array<int,array<string,mixed>> $sportsAll */
/** @var string|null $filterCity */
/** @var string $filterSport */
/** @var string $jsonUrl */
/** @var string $metaDescription */
/** @var string $title */
use App\Helpers\View;

$sportName  = (string)($sport['name'] ?? $filterSport);
$sportColor = (string)($sport['color'] ?? '#0d6efd');
$canonical  = url('discover/' . rawurlencode($filterSport));

// JSON-LD Schema.org dla SEO (CollectionPage with SportsClub items)
$ldItems = [];
$pos = 1;
foreach ($clubs as $c) {
    $item = [
        '@type'    => 'SportsClub',
        'name'     => (string)$c['name'],
        'url'      => url('discover/club/' . ($c['public_slug'] ?? '')),
    ];
    if (!empty($c['city'])) {
        $item['address'] = [
            '@type'           => 'PostalAddress',
            'addressLocality' => (string)$c['city'],
            'addressCountry'  => 'PL',
        ];
    }
    if (!empty($c['latitude']) && !empty($c['longitude'])) {
        $item['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float)$c['latitude'],
            'longitude' => (float)$c['longitude'],
        ];
    }
    $ldItems[] = [
        '@type'    => 'ListItem',
        'position' => $pos++,
        'item'     => $item,
    ];
}
$jsonLd = [
    '@context'        => 'https://schema.org',
    '@type'           => 'CollectionPage',
    'name'            => $title,
    'description'     => $metaDescription,
    'url'             => $canonical,
    'mainEntity'      => [
        '@type'           => 'ItemList',
        'itemListElement' => $ldItems,
    ],
];

$GLOBALS['__pageHead'] = '<meta name="description" content="' . View::e($metaDescription) . '">'
    . '<meta property="og:title" content="' . View::e($title) . '">'
    . '<meta property="og:description" content="' . View::e($metaDescription) . '">'
    . '<meta property="og:type" content="website">'
    . '<link rel="canonical" href="' . View::e($canonical) . '">'
    . '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />'
    . '<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />'
    . '<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />'
    . '<script type="application/ld+json">' . json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
?>

<section class="pub-hero" style="background: <?= View::e($sportColor) ?>;">
    <div class="container">
        <h1 class="display-5 mb-2">Kluby <?= View::e($sportName) ?> w Polsce</h1>
        <p class="lead mb-3">
            Znaleziono <strong><?= count($clubs) ?></strong>
            <?= count($clubs) === 1 ? 'klub' : 'klubow' ?> oferujacych ten sport.
        </p>
        <form method="GET" action="<?= url('discover/' . rawurlencode($filterSport)) ?>" class="row g-2">
            <div class="col-sm-8">
                <input type="text" name="city" class="form-control form-control-lg"
                       placeholder="Miasto (np. Warszawa)" value="<?= View::e($filterCity ?? '') ?>">
            </div>
            <div class="col-sm-4">
                <button type="submit" class="btn btn-light btn-lg w-100">
                    <i class="bi bi-search"></i> Szukaj
                </button>
            </div>
        </form>
    </div>
</section>

<section class="container py-4">
    <div class="mb-3">
        <a href="<?= url('discover') ?>" class="btn btn-outline-secondary btn-sm">
            &larr; Wszystkie sporty
        </a>
        <?php if (!empty($sportsAll)): ?>
            <span class="text-muted mx-2">|</span>
            <?php foreach (array_slice($sportsAll, 0, 8) as $sp): ?>
                <?php if ($sp['key'] !== $filterSport): ?>
                    <a href="<?= url('discover/' . rawurlencode((string)$sp['key'])) ?>"
                       class="btn btn-link btn-sm p-0 me-2 text-decoration-none">
                        <?= View::e($sp['name']) ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="row g-3">
        <div class="col-lg-6 order-2 order-lg-1">
            <?php if (empty($clubs)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Brak klubow oferujacych
                    <?= View::e($sportName) ?><?php if ($filterCity): ?> w "<?= View::e($filterCity) ?>"<?php endif; ?>.
                    <a href="<?= url('discover/' . rawurlencode($filterSport)) ?>" class="alert-link">Zobacz wszystkie miasta</a>.
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2" style="max-height: 70vh; overflow-y: auto;">
                    <?php foreach ($clubs as $c): ?>
                        <a href="<?= url('discover/club/' . rawurlencode((string)($c['public_slug'] ?? ''))) ?>"
                           class="card text-decoration-none text-reset p-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?= View::e($c['name']) ?></strong>
                                    <?php if (!empty($c['city'])): ?>
                                        <div class="small text-muted">
                                            <i class="bi bi-geo-alt"></i> <?= View::e($c['city']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <i class="bi bi-chevron-right text-muted align-self-center"></i>
                            </div>
                            <?php if (!empty($c['description_short'])): ?>
                                <p class="small text-muted mt-2 mb-0">
                                    <?= View::e(mb_substr((string)$c['description_short'], 0, 150)) ?>
                                </p>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-6 order-1 order-lg-2">
            <div id="map" style="height: 70vh; min-height: 400px; border-radius: 8px; background:#eaeaea;"></div>
        </div>
    </div>
</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
(function () {
    var map = L.map('map').setView([52.0, 19.0], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);
    var cluster = L.markerClusterGroup();
    fetch(<?= json_encode($jsonUrl, JSON_UNESCAPED_SLASHES) ?>)
        .then(function (r) { return r.json(); })
        .then(function (clubs) {
            if (!Array.isArray(clubs)) return;
            var bounds = [];
            clubs.forEach(function (c) {
                if (!c.lat || !c.lng) return;
                var m = L.marker([c.lat, c.lng]).bindPopup(
                    '<strong>' + escapeHtml(c.name) + '</strong><br>'
                    + (c.city ? '<small>' + escapeHtml(c.city) + '</small><br>' : '')
                    + '<a href="' + escapeHtml(c.url) + '">Zobacz klub &rarr;</a>'
                );
                cluster.addLayer(m);
                bounds.push([c.lat, c.lng]);
            });
            map.addLayer(cluster);
            if (bounds.length > 0) map.fitBounds(bounds, { padding: [30, 30], maxZoom: 12 });
        }).catch(function () {});
    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
        });
    }
})();
</script>
