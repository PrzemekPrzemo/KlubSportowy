<?php
/** @var array<int,array<string,mixed>> $clubs */
/** @var array<int,array<string,mixed>> $sportsAll */
/** @var string|null $filterCity */
/** @var string|null $filterSport */
/** @var string $jsonUrl */
/** @var string $metaDescription */
/** @var string $title */
use App\Helpers\View;

$GLOBALS['__pageHead'] = '<meta name="description" content="' . View::e($metaDescription) . '">'
    . '<meta property="og:title" content="' . View::e($title) . '">'
    . '<meta property="og:description" content="' . View::e($metaDescription) . '">'
    . '<meta property="og:type" content="website">'
    . '<link rel="canonical" href="' . View::e(url('discover')) . '">'
    . '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />'
    . '<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />'
    . '<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />';
?>

<section class="pub-hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h1 class="display-5 mb-2">Znajdz klub sportowy w okolicy</h1>
                <p class="lead mb-3">
                    Katalog klubow uzywajacych ClubDesk — judo, pilka nozna, koszykowka, strzelectwo
                    i wiele innych. Sprawdz oferte i zapisz dziecko.
                </p>
                <form method="GET" action="<?= url('discover') ?>" class="row g-2">
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
            <div class="col-lg-5 d-none d-lg-block text-center">
                <i class="bi bi-geo-alt-fill" style="font-size: 7rem; opacity: .35;"></i>
            </div>
        </div>
    </div>
</section>

<section class="container py-4">
    <?php if (!empty($sportsAll)): ?>
    <div class="mb-4">
        <h6 class="text-uppercase text-muted small mb-2">Wybierz sport</h6>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= url('discover') ?>"
               class="btn btn-sm <?= $filterSport === null ? 'btn-dark' : 'btn-outline-secondary' ?>">
                Wszystkie
            </a>
            <?php foreach ($sportsAll as $sp): ?>
                <a href="<?= url('discover/' . rawurlencode((string)$sp['key'])) ?>"
                   class="btn btn-sm <?= ($filterSport === $sp['key']) ? 'btn-dark' : 'btn-outline-secondary' ?>"
                   style="border-color: <?= View::e($sp['color'] ?? '#666') ?>;">
                    <?= View::e($sp['name']) ?>
                    <span class="badge bg-light text-dark ms-1"><?= (int)$sp['clubs_count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-6 order-2 order-lg-1">
            <h5 class="mb-3">
                Klubów: <span class="badge bg-primary"><?= count($clubs) ?></span>
                <?php if ($filterCity): ?>w "<?= View::e($filterCity) ?>"<?php endif; ?>
            </h5>

            <?php if (empty($clubs)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Brak klubow spelniajacych kryteria.
                    Sprobuj rozszerzyc wyszukiwanie.
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2" style="max-height: 70vh; overflow-y: auto;">
                    <?php foreach ($clubs as $c): ?>
                        <a href="<?= url('discover/club/' . rawurlencode((string)($c['public_slug'] ?? ''))) ?>"
                           class="card text-decoration-none text-reset p-3 club-card"
                           data-club-id="<?= (int)$c['id'] ?>">
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
                            <?php
                            $sportsList = [];
                            if (!empty($c['sports_offered_json'])) {
                                $decoded = json_decode((string)$c['sports_offered_json'], true);
                                if (is_array($decoded)) $sportsList = array_slice($decoded, 0, 5);
                            }
                            ?>
                            <?php if (!empty($sportsList)): ?>
                                <div class="mt-2">
                                    <?php foreach ($sportsList as $s): ?>
                                        <span class="sport-badge"
                                              style="background: <?= View::e($s['color'] ?? '#6c757d') ?>;">
                                            <?= View::e($s['name'] ?? '') ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
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
            if (!Array.isArray(clubs) || clubs.length === 0) return;
            var bounds = [];
            clubs.forEach(function (c) {
                if (!c.lat || !c.lng) return;
                var marker = L.marker([c.lat, c.lng]);
                var popup = '<strong>' + escapeHtml(c.name) + '</strong>';
                if (c.city) popup += '<br><small>' + escapeHtml(c.city) + '</small>';
                if (c.sports && c.sports.length) {
                    popup += '<br>' + c.sports.slice(0, 3).map(escapeHtml).join(', ');
                }
                popup += '<br><a href="' + escapeHtml(c.url) + '">Zobacz klub &rarr;</a>';
                marker.bindPopup(popup);
                cluster.addLayer(marker);
                bounds.push([c.lat, c.lng]);
            });
            map.addLayer(cluster);
            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [30, 30], maxZoom: 12 });
            }
        })
        .catch(function () { /* silent */ });

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
        });
    }
})();
</script>
