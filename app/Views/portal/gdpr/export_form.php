<?php use App\Helpers\View; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0 text-primary"><i class="bi bi-download me-2"></i>Eksport moich danych (art. 20 RODO)</h4>
            <a href="<?= url('portal/gdpr') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Powrot
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <p>
                    Mozesz pobrac wszystkie swoje dane zgromadzone w klubie w jednym pliku ZIP.
                    Plik bedzie zawieral nastepujace sekcje (w formacie JSON):
                </p>
                <ul>
                    <li><code>profile.json</code> — dane osobowe (imie, nazwisko, kontakt, adres)</li>
                    <li><code>payments.json</code> — historia platnosci skladek</li>
                    <li><code>events.json</code> — udzial w wydarzeniach klubu</li>
                    <li><code>trainings.json</code> — frekwencja na treningach</li>
                    <li><code>consents.json</code> — udzielone zgody RODO</li>
                    <li><code>medical.json</code> — badania lekarskie</li>
                    <li><code>licenses.json</code> — licencje sportowe</li>
                    <li><code>rankings.json</code> — wyniki i rankingi</li>
                    <li><code>notification_prefs.json</code> — preferencje powiadomien</li>
                    <li><code>body_metrics.json</code> — pomiary ciala (jesli wprowadzone)</li>
                    <li><code>README.txt</code> — opis archiwum</li>
                </ul>

                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-2"></i>
                    Po zlozeniu prosby otrzymasz e-mail z linkiem potwierdzajacym (wazny 24h).
                    Po potwierdzeniu plik zostanie wygenerowany automatycznie. Link do pobrania
                    bedzie wazny przez <strong>7 dni</strong>.
                </div>

                <form method="POST" action="<?= url('portal/gdpr/export') ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-envelope-paper me-1"></i> Zamow eksport (wyslij link potwierdzajacy)
                    </button>
                    <a href="<?= url('portal/gdpr') ?>" class="btn btn-outline-secondary">Anuluj</a>
                </form>
            </div>
        </div>
    </div>
</div>
