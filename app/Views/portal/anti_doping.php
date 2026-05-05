<?php
use App\Helpers\View;

$days = $current ? (int)((strtotime($current['valid_until']) - time()) / 86400) : null;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1"><i class="bi bi-shield-fill-exclamation text-warning me-2"></i>Deklaracja anti-doping</h3>
        <p class="text-muted mb-0">WADA / POLADA — zgoda na regulamin i kontrole antydopingowe</p>
    </div>
    <a href="<?= url('portal/dashboard') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Dashboard
    </a>
</div>

<?php if ($current): ?>
    <div class="card shadow-sm mb-4 <?= $days !== null && $days < 30 ? 'border-warning' : 'border-success' ?>">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-2">
                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                        Aktualna deklaracja: <?= View::e($current['declaration_type']) ?>
                    </h5>
                    <ul class="list-unstyled small text-muted mb-0">
                        <li><strong>Podpisana:</strong> <?= View::e($current['signed_date']) ?></li>
                        <li><strong>Ważna do:</strong> <?= View::e($current['valid_until']) ?>
                            <?php if ($days !== null): ?>
                                <?php if ($days < 0): ?>
                                    <span class="badge bg-danger ms-1">Wygasla <?= abs($days) ?> dni temu</span>
                                <?php elseif ($days < 30): ?>
                                    <span class="badge bg-warning text-dark ms-1">Zostalo <?= $days ?> dni</span>
                                <?php else: ?>
                                    <span class="badge bg-success ms-1">Aktywna (<?= $days ?> dni)</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </li>
                        <?php if (!empty($current['signed_ip'])): ?>
                            <li><strong>Podpisana z IP:</strong> <code><?= View::e($current['signed_ip']) ?></code></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php if ($days !== null && $days < 30): ?>
        <div class="alert alert-warning small mb-3">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Twoja deklaracja wygasa za <?= max(0, $days) ?> dni. Mozesz ja odnowic ponizej.
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="alert alert-warning small mb-3">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Nie masz jeszcze waznej deklaracji anti-doping. Wymagana w sportach kontrolowanych przez WADA
        (podnoszenie ciezarow, boks, plywanie, taekwondo, kolarstwo, zapasy, judo i inne).
    </div>
<?php endif; ?>

<form method="POST" action="<?= url('portal/anti-doping') ?>" class="card shadow-sm">
    <?= csrf_field() ?>
    <div class="card-header">
        <i class="bi bi-pen me-1"></i>
        <?= $current ? 'Odnow deklaracje' : 'Zloz nowa deklaracje' ?>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label">Typ deklaracji</label>
            <select name="declaration_type" class="form-select form-select-sm">
                <?php foreach ($declarationTypes as $key => $label): ?>
                    <option value="<?= View::e($key) ?>" <?= $key === 'WADA' ? 'selected' : '' ?>>
                        <?= View::e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text small">
                Domyslnie WADA — uniwersalny standard. Dla pewnych dyscyplin federacja moze
                wymagac specyficznej deklaracji (IWF, UCI, FINA, WTF).
            </div>
        </div>

        <div class="bg-light p-3 small rounded mb-3" style="max-height:240px; overflow-y:auto;">
            <strong>Oswiadczenie:</strong>
            <ol class="mb-0 mt-2">
                <li>Zapoznalem/am sie z regulaminem WADA Code i lista substancji zabronionych
                    publikowana przez Polska Agencje Antydopingowa (POLADA).</li>
                <li>Wyrazam zgode na poddanie sie kontroli antydopingowej (zawody, treningi,
                    out-of-competition) i pobieranie probek krwi/moczu.</li>
                <li>Oswiadczam, ze nie stosowalem/am, nie stosuje i nie bede stosowal/a
                    zadnych substancji ani metod wymienionych na liscie WADA Prohibited List.</li>
                <li>W przypadku stosowania lekow wymagajacych TUE (Therapeutic Use Exemption)
                    zlozylem/am odpowiedni wniosek do POLADA.</li>
                <li>Rozumiem, ze deklaracja jest wazna 12 miesiecy i wymaga okresowego odnowienia.</li>
            </ol>
        </div>

        <div class="form-check mb-2">
            <input type="checkbox" name="confirm_read" id="cb_read" class="form-check-input" value="1" required>
            <label for="cb_read" class="form-check-label">
                Zapoznalem/am sie z regulaminem WADA / POLADA i akceptuje jego tresc.
            </label>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" name="confirm_truthful" id="cb_true" class="form-check-input" value="1" required>
            <label for="cb_true" class="form-check-label">
                Oswiadczam, ze powyzsze informacje sa zgodne z prawda.
            </label>
        </div>

        <div class="alert alert-info small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            Po podpisaniu data, IP i typ deklaracji zostana zapisane (audytowalnosc RODO art. 30).
            Deklaracja bedzie wazna do <strong><?= date('Y-m-d', strtotime('+1 year')) ?></strong>.
        </div>
    </div>
    <div class="card-footer text-end bg-white">
        <button type="submit" class="btn btn-warning">
            <i class="bi bi-pen-fill me-1"></i> Podpisz deklaracje
        </button>
    </div>
</form>
