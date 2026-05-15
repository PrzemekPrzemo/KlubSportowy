<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-file-earmark-spreadsheet text-primary me-2"></i>
        Eksport członków (CSV / Excel)
    </h3>
    <a href="<?= url('members') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<?php if ($flash = \App\Helpers\Session::getFlash('error')): ?>
    <div class="alert alert-danger"><?= View::e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Helpers\Session::getFlash('warning')): ?>
    <div class="alert alert-warning"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="card p-4">
    <form method="POST" action="<?= url('members/export') ?>">
        <?= csrf_field() ?>

        <h5>Filtry</h5>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">Sekcja sportowa</label>
                <select name="sport_id" class="form-select">
                    <option value="">Wszystkie</option>
                    <?php foreach (($clubSports ?? []) as $cs): ?>
                        <option value="<?= (int)$cs['club_sport_id'] ?>"><?= View::e($cs['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Wszyscy</option>
                    <option value="aktywny">Aktywni</option>
                    <option value="zawieszony">Zawieszeni</option>
                    <option value="urlop">Urlop</option>
                    <option value="wykreslony">Wykreśleni</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Płeć</label>
                <select name="gender" class="form-select">
                    <option value="">Obie</option>
                    <option value="M">M</option>
                    <option value="K">K</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Składki</label>
                <select name="fees" class="form-select">
                    <option value="">Bez filtra</option>
                    <option value="paid">Opłacone (90 dni)</option>
                    <option value="overdue">Zaległe</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Wiek od</label>
                <input type="number" name="age_min" min="0" max="120" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Wiek do</label>
                <input type="number" name="age_max" min="0" max="120" class="form-control">
            </div>
        </div>

        <h5>Kolumny do eksportu</h5>
        <div class="row g-2 mb-4">
            <?php
            $cols = [
                'member_number'  => 'Nr członkowski',
                'first_name'     => 'Imię',
                'last_name'      => 'Nazwisko',
                'email'          => 'Email',
                'phone'          => 'Telefon',
                'birth_date'     => 'Data urodzenia',
                'gender'         => 'Płeć',
                'address_street' => 'Ulica',
                'address_city'   => 'Miasto',
                'address_postal' => 'Kod pocztowy',
                'join_date'      => 'Data wstąpienia',
                'status'         => 'Status',
                'notes'          => 'Uwagi',
            ];
            $defaultChecked = ['member_number','first_name','last_name','email','phone','status','join_date'];
            foreach ($cols as $key => $label): ?>
                <div class="col-md-3">
                    <label class="form-check">
                        <input type="checkbox" name="columns[]" value="<?= $key ?>"
                               class="form-check-input"
                               <?= in_array($key, $defaultChecked, true) ? 'checked' : '' ?>>
                        <span class="form-check-label"><?= View::e($label) ?></span>
                    </label>
                </div>
            <?php endforeach; ?>
            <?php if (!empty($canSensitive)): ?>
                <div class="col-md-3">
                    <label class="form-check">
                        <input type="checkbox" name="columns[]" value="pesel" class="form-check-input">
                        <span class="form-check-label text-danger">
                            <i class="bi bi-shield-exclamation"></i> PESEL (dane wrażliwe — audytowane)
                        </span>
                    </label>
                </div>
            <?php endif; ?>
        </div>

        <div class="d-flex justify-content-end">
            <button class="btn btn-primary">
                <i class="bi bi-download"></i> Pobierz CSV
            </button>
        </div>
    </form>
</div>
