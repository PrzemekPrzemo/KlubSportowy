<?php use App\Helpers\View; ?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="bi bi-upload"></i> Import zawodników z pliku CSV</h5>
                <p class="text-muted">
                    Prześlij plik CSV lub TXT z danymi zawodników. System automatycznie rozpozna separator
                    (średnik, przecinek, tabulacja) oraz zaproponuje dopasowanie kolumn.
                </p>

                <form method="POST" action="<?= url('import/upload') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Plik CSV / TXT</label>
                        <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv,.txt" required>
                        <div class="form-text">
                            Dozwolone formaty: <code>.csv</code>, <code>.txt</code>.
                            Pierwszy wiersz powinien zawierać nazwy kolumn.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cloud-arrow-up"></i> Prześlij i podejrzyj
                        </button>
                        <a href="<?= url('members') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Powrót do listy
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title"><i class="bi bi-info-circle"></i> Wskazówki</h6>
                <ul class="mb-0 small text-muted">
                    <li>Obsługiwane kolumny: imię, nazwisko, email, PESEL, data urodzenia, telefon, płeć, ulica, miasto, kod pocztowy, numer członkowski, data dołączenia, status, notatki.</li>
                    <li>Nazwy kolumn mogą być po polsku lub angielsku — system automatycznie je dopasuje.</li>
                    <li>Wiersze z duplikatem numeru członkowskiego zostaną pominięte.</li>
                    <li>Jeśli numer członkowski jest pusty, zostanie wygenerowany automatycznie.</li>
                    <li>Domyślny status nowego zawodnika: <strong>aktywny</strong>.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
