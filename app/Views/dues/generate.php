<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-magic text-primary me-2"></i>
        Wygeneruj należności
    </h3>
    <a href="<?= url('fees/dues') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<div class="card p-4">
    <p class="text-muted">
        Generator automatycznie tworzy należności (<code>payment_dues</code>) dla wszystkich
        <strong>aktywnych subskrypcji</strong> w klubie. Należności są obliczane z uwzględnieniem
        zniżek przypisanych do każdej subskrypcji.
    </p>
    <p class="text-muted small">
        Duplikaty (ta sama stawka + ten sam okres) są pomijane (UNIQUE constraint w DB).
        Możesz odpalić generator wielokrotnie — bezpieczne.
    </p>

    <form method="POST" action="<?= url('fees/dues/generate') ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Rok *</label>
                <input type="number" name="period_year" class="form-control"
                       value="<?= (int)$year ?>" min="2020" max="2099" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Miesiąc</label>
                <select name="period_month" class="form-select">
                    <option value="">— rocznie —</option>
                    <?php foreach (range(1, 12) as $m): ?>
                        <?php $monthName = strftime('%B', mktime(0,0,0,$m,1)); // PL locale needed ?>
                        <option value="<?= $m ?>" <?= $m === (int)$month ? 'selected' : '' ?>>
                            <?= str_pad((string)$m, 2, '0', STR_PAD_LEFT) ?> — <?= ucfirst($monthName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Pusty = roczna należność</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">Termin płatności (+dni)</label>
                <input type="number" name="due_date_offset_days" class="form-control"
                       value="14" min="0" max="60">
                <small class="text-muted">Liczba dni od dzisiaj</small>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" name="dry_run" value="1" id="dryRunChk" class="form-check-input" checked>
                    <label class="form-check-label small" for="dryRunChk">
                        <strong>Symulacja</strong>
                        <span class="d-block text-muted">tylko podgląd</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-3 small">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Ważne:</strong>
            <ul class="mb-0 mt-1">
                <li>Tylko subskrypcje ze statusem <code>active</code> i ważne w danym okresie są brane pod uwagę.</li>
                <li>Przy <strong>Symulacja</strong> NIE są tworzone żadne wpisy — pokazujemy raport ile by się wygenerowało.</li>
                <li>Po wyłączeniu Symulacji generator zapisze rekordy do DB.</li>
                <li>Termin płatności = <em>data wykonania generatora</em> + N dni.</li>
            </ul>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="<?= url('fees/dues') ?>" class="btn btn-outline-secondary">Anuluj</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-magic me-1"></i> Wykonaj
            </button>
        </div>
    </form>
</div>
