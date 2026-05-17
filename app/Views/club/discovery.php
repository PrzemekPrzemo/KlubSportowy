<?php
/** @var array<string,mixed>|null $club */
/** @var array<int,array<string,mixed>> $sports */
use App\Helpers\View;

$enabled    = (int)($club['public_discovery_enabled'] ?? 0) === 1;
$slug       = (string)($club['public_slug'] ?? '');
$publicUrl  = $slug !== '' ? url('discover/club/' . $slug) : null;
$descShort  = (string)($club['description_short'] ?? '');
$lat        = $club['latitude']  ?? '';
$lng        = $club['longitude'] ?? '';
$contactTel = (string)($club['contact_phone'] ?? '');
$websiteUrl = (string)($club['website_url']   ?? '');
?>

<h2 class="mb-3"><i class="bi bi-globe"></i> Publiczna prezentacja klubu</h2>
<p class="text-muted">
    Wlacz publiczny profil klubu, aby pojawil sie w katalogu
    <a href="<?= url('discover') ?>" target="_blank"><code>/discover</code></a>.
    Rodzice szukajacy klubu dla dziecka znajdą Ciebie w Google.
</p>

<?php if ($enabled && $publicUrl): ?>
    <div class="alert alert-success">
        <strong><i class="bi bi-check-circle"></i> Klub widoczny publicznie:</strong>
        <a href="<?= View::e($publicUrl) ?>" target="_blank"><?= View::e($publicUrl) ?></a>
    </div>
<?php endif; ?>

<form method="POST" action="<?= url('club/settings/discovery') ?>" class="card p-4">
    <?= csrf_field() ?>

    <div class="form-check form-switch mb-3">
        <input type="checkbox" name="public_discovery_enabled" value="1" id="enabled"
               class="form-check-input" <?= $enabled ? 'checked' : '' ?>>
        <label class="form-check-label fw-bold" for="enabled">
            Pokazuj klub w katalogu publicznym ClubDesk
        </label>
        <div class="form-text">
            Po wlaczeniu klub bedzie widoczny dla wszystkich osob bez logowania
            (lat/lng/nazwa/sporty/kontakt). Czlonkowie pozostaja prywatni.
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Krotki opis klubu (max 500 znakow)</label>
        <textarea name="description_short" class="form-control" rows="4"
                  maxlength="500"
                  placeholder="Np. Klub sportowy dla dzieci od 6 roku zycia, treningi na obiekcie X..."><?= View::e($descShort) ?></textarea>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label">Telefon kontaktowy (publiczny)</label>
            <input type="text" name="contact_phone" value="<?= View::e($contactTel) ?>"
                   class="form-control" placeholder="+48 ...">
        </div>
        <div class="col-md-6">
            <label class="form-label">Strona WWW</label>
            <input type="text" name="website_url" value="<?= View::e($websiteUrl) ?>"
                   class="form-control" placeholder="https://example.pl">
        </div>
    </div>

    <h5 class="mt-3">Lokalizacja na mapie</h5>
    <p class="text-muted small">
        Adres klubu w bazie: <strong><?= View::e($club['address'] ?? '—') ?>, <?= View::e($club['city'] ?? '') ?></strong>.
        Mozesz wpisac wspolrzedne recznie lub kliknac "Pobierz koordynaty z adresu".
    </p>

    <div class="row g-3 mb-3">
        <div class="col-md-5">
            <label class="form-label">Szerokosc (lat)</label>
            <input type="text" name="latitude" value="<?= View::e((string)$lat) ?>"
                   class="form-control" placeholder="np. 52.229676">
        </div>
        <div class="col-md-5">
            <label class="form-label">Dlugosc (lng)</label>
            <input type="text" name="longitude" value="<?= View::e((string)$lng) ?>"
                   class="form-control" placeholder="np. 21.012229">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check">
                <input type="checkbox" name="do_geocode" value="1" id="do_geocode" class="form-check-input">
                <label class="form-check-label small" for="do_geocode">
                    Auto&nbsp;z&nbsp;adresu
                </label>
            </div>
        </div>
    </div>

    <h5 class="mt-3">Sporty oferowane</h5>
    <p class="text-muted small">
        Lista budowana automatycznie z aktywnych sekcji
        (<a href="<?= url('sports') ?>">/sports</a>):
    </p>
    <div class="mb-3">
        <?php if (empty($sports)): ?>
            <em class="text-muted">Brak aktywnych sportow — najpierw aktywuj sport w /sports.</em>
        <?php else: ?>
            <?php foreach ($sports as $s): ?>
                <span class="badge me-1" style="background: <?= View::e($s['color'] ?? '#6c757d') ?>;">
                    <?= View::e($s['name']) ?>
                </span>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <hr>

    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-check2"></i> Zapisz
        </button>
        <a href="<?= url('club/settings') ?>" class="btn btn-outline-secondary">Powrot</a>
    </div>
</form>

<div class="alert alert-info mt-4 small">
    <strong>Co jest publicznie pokazywane:</strong> nazwa klubu, miasto, adres, telefon, email,
    strona, oferowane sporty, lokalizacja na mapie, przyblizona liczba czlonkow (np. "100+").<br>
    <strong>Co NIE jest udostepniane:</strong> imiona/nazwiska czlonkow, dane finansowe,
    wewnetrzne komunikaty, statystyki indywidualne.
</div>
