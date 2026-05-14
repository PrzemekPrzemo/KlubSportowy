<?php
use App\Helpers\View;
$m = $member ?? null;
$c = $config;

$prefName     = $m ? trim(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')) : '';
$prefEmail    = $m['email'] ?? '';
$prefPhone    = $m['phone'] ?? '';
$prefStreet   = $m['address_street']  ?? '';
$prefCity     = $m['address_city']    ?? '';
$prefPostCode = $m['address_postal']  ?? '';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-box-arrow-up-right text-primary me-2"></i>
        Nowa przesylka InPost
    </h3>
    <div>
        <a href="<?= url('club/shipping/shipments') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list-ul"></i> Lista przesylek
        </a>
        <a href="<?= url('club/shipping') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-gear"></i> Konfiguracja
        </a>
    </div>
</div>

<?php if (!empty($c['is_sandbox'])): ?>
    <div class="alert alert-warning small">
        <i class="bi bi-exclamation-triangle me-1"></i>
        <strong>Tryb sandbox</strong> — etykiety nie sa fakturowane, ale paczki nie zostana nadane.
    </div>
<?php endif; ?>

<form method="POST" action="<?= url('club/shipping/create') ?>" class="card p-3">
    <?= csrf_field() ?>
    <input type="hidden" name="member_id" value="<?= $m ? (int)$m['id'] : 0 ?>">

    <?php if ($m): ?>
        <div class="alert alert-info py-2 small">
            <i class="bi bi-person-circle me-1"></i>
            Przesylka powiazana z czlonkiem:
            <strong><?= View::e(($m['first_name'] ?? '') . ' ' . ($m['last_name'] ?? '')) ?></strong>
            (#<?= View::e($m['member_number'] ?? '') ?>)
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label small">Imie i nazwisko odbiorcy *</label>
            <input type="text" name="recipient_name" class="form-control" required
                   maxlength="120" value="<?= View::e($prefName) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">E-mail *</label>
            <input type="email" name="recipient_email" class="form-control" required
                   maxlength="120" value="<?= View::e($prefEmail) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Telefon (9 cyfr) *</label>
            <input type="text" name="recipient_phone" class="form-control" required
                   maxlength="20" value="<?= View::e($prefPhone) ?>">
        </div>
    </div>

    <hr>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label small">Serwis</label>
            <select name="service" class="form-select" id="service-select">
                <?php foreach ($services as $code => $label): ?>
                    <option value="<?= View::e($code) ?>"
                            <?= ($c['default_service'] ?? '') === $code ? 'selected' : '' ?>>
                        <?= View::e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Rozmiar paczki</label>
            <select name="size" class="form-select">
                <?php foreach ($sizes as $code => $label): ?>
                    <option value="<?= View::e($code) ?>"
                            <?= ($c['default_size'] ?? 'A') === $code ? 'selected' : '' ?>>
                        <?= View::e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label small">Notatka wewnetrzna (opcjonalnie)</label>
            <input type="text" name="internal_note" class="form-control" maxlength="500"
                   placeholder="np. zwrot dokumentow, zamowienie #123">
        </div>
    </div>

    <hr>

    <!-- Sekcja paczkomatu (locker) -->
    <div id="locker-section">
        <h6 class="mb-2"><i class="bi bi-geo-alt me-1"></i> Paczkomat docelowy</h6>
        <div class="row g-3 mb-2">
            <div class="col-md-3">
                <label class="form-label small">Kod pocztowy</label>
                <input type="text" id="locker-search-postcode" class="form-control"
                       placeholder="np. 00-001" maxlength="6"
                       value="<?= View::e($prefPostCode) ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="button" class="btn btn-outline-primary w-100" id="locker-search-btn">
                    <i class="bi bi-search"></i> Szukaj paczkomatow
                </button>
            </div>
            <div class="col-md-6">
                <label class="form-label small">Wybrany paczkomat (ID) *</label>
                <input type="text" name="target_locker_id" id="target-locker-id"
                       class="form-control" placeholder="np. WAW01A"
                       maxlength="20">
            </div>
        </div>
        <div id="locker-results" class="small"></div>
    </div>

    <!-- Sekcja adresu dla kuriera -->
    <div id="address-section" style="display:none;">
        <h6 class="mb-2"><i class="bi bi-house-door me-1"></i> Adres odbiorcy (kurier)</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">Ulica</label>
                <input type="text" name="recipient_street" class="form-control"
                       maxlength="120" value="<?= View::e($prefStreet) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Nr budynku</label>
                <input type="text" name="recipient_building" class="form-control" maxlength="20">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Kod pocztowy</label>
                <input type="text" name="recipient_post_code" class="form-control"
                       maxlength="10" value="<?= View::e($prefPostCode) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Miasto</label>
                <input type="text" name="recipient_city" class="form-control"
                       maxlength="80" value="<?= View::e($prefCity) ?>">
            </div>
        </div>
    </div>

    <hr>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-truck"></i> Utworz przesylke
        </button>
        <a href="<?= url('club/shipping/shipments') ?>" class="btn btn-outline-secondary">Anuluj</a>
    </div>
</form>

<script>
(function() {
    var select       = document.getElementById('service-select');
    var lockerSec    = document.getElementById('locker-section');
    var addressSec   = document.getElementById('address-section');
    var btnSearch    = document.getElementById('locker-search-btn');
    var inputPost    = document.getElementById('locker-search-postcode');
    var inputLocker  = document.getElementById('target-locker-id');
    var resultsBox   = document.getElementById('locker-results');

    function toggleSections() {
        var isLocker = select.value.indexOf('locker') !== -1;
        lockerSec.style.display  = isLocker ? '' : 'none';
        addressSec.style.display = isLocker ? 'none' : '';
        inputLocker.required     = isLocker;
    }
    select.addEventListener('change', toggleSections);
    toggleSections();

    // Locker search — uderza we wlasny endpoint nie istnieje, wiec instrukcja manualna.
    // (InPost wymaga ID paczkomatu jak WAW01A; user-friendly picker mozna dorobic pozniej.)
    btnSearch.addEventListener('click', function() {
        var pc = (inputPost.value || '').trim();
        if (!pc) { resultsBox.innerHTML = '<span class="text-danger">Podaj kod pocztowy.</span>'; return; }
        resultsBox.innerHTML =
            '<div class="alert alert-secondary small mb-0">' +
            'Aby znalezc paczkomat: <a href="https://inpost.pl/znajdz-paczkomat" target="_blank">inpost.pl/znajdz-paczkomat</a>' +
            ' (kod ' + pc + '). Skopiuj ID paczkomatu (np. WAW01A) i wklej powyzej.' +
            '</div>';
    });
})();
</script>
