<?php use App\Helpers\View; ?>

<div class="card">
    <div class="card-body p-4">
        <h4 class="mb-1"><i class="bi bi-people"></i> Dodaj zawodników</h4>
        <p class="text-muted mb-4">Zaimportuj zawodników z pliku CSV lub dodaj pierwszego ręcznie.</p>

        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="csv-tab" data-bs-toggle="tab"
                        data-bs-target="#csv-pane" type="button" role="tab">
                    <i class="bi bi-file-earmark-arrow-up"></i> Import CSV
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="manual-tab" data-bs-toggle="tab"
                        data-bs-target="#manual-pane" type="button" role="tab">
                    <i class="bi bi-person-plus"></i> Dodaj ręcznie
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- CSV Import Tab -->
            <div class="tab-pane fade show active" id="csv-pane" role="tabpanel">
                <form method="POST" action="<?= url('onboarding/step4') ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="mode" value="csv">

                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Plik CSV</label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file"
                               accept=".csv,.txt" required>
                        <div class="form-text">
                            Kolumny rozpoznawane automatycznie: imię, nazwisko, email, telefon, PESEL, data urodzenia, adres i inne.
                            Separator: średnik (;) lub przecinek (,).
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= url('onboarding/step3') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Wstecz
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Importuj i dalej <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                <div class="text-center mt-3"><a href="<?= url('onboarding/skip') ?>" class="text-muted small">Dokończ później &rarr;</a></div>
</form>
            </div>

            <!-- Manual Entry Tab -->
            <div class="tab-pane fade" id="manual-pane" role="tabpanel">
                <form method="POST" action="<?= url('onboarding/step4') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="mode" value="manual">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="first_name" class="form-label">Imię <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name" class="form-label">Nazwisko <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="manual_email" class="form-label">E-mail</label>
                            <input type="email" class="form-control" id="manual_email" name="email">
                        </div>
                        <div class="col-md-6">
                            <label for="manual_phone" class="form-label">Telefon</label>
                            <input type="text" class="form-control" id="manual_phone" name="phone">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="join_date" class="form-label">Data dołączenia</label>
                        <input type="date" class="form-control" id="join_date" name="join_date"
                               value="<?= date('Y-m-d') ?>" style="max-width:220px;">
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="<?= url('onboarding/step3') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Wstecz
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Dodaj i dalej <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                <div class="text-center mt-3"><a href="<?= url('onboarding/skip') ?>" class="text-muted small">Dokończ później &rarr;</a></div>
</form>
            </div>
        </div>
    </div>
</div>
