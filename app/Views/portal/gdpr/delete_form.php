<?php use App\Helpers\View; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0 text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Usuniecie konta (art. 17 RODO)</h4>
            <a href="<?= url('portal/gdpr') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Powrot
            </a>
        </div>

        <div class="alert alert-danger">
            <h6 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Uwaga — operacja nieodwracalna</h6>
            <ul class="mb-0 small">
                <li>Twoje dane osobowe (imie, nazwisko, PESEL, email, telefon, adres, data urodzenia) zostana zanonimizowane.</li>
                <li>Stracisz dostep do portalu zawodnika — logowanie nie bedzie mozliwe.</li>
                <li>Twoja historia (frekwencja, wyniki, statystyki klubu) zostanie zachowana w formie anonimowej dla celow agregatow.</li>
                <li>Operacja jest <strong>nieodwracalna</strong> — nie bedzie mozliwosci przywrocenia konta.</li>
                <li>Nie wplywa to na legalnosc przetwarzania danych przed usunieciem.</li>
            </ul>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= url('portal/gdpr/delete-account') ?>">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label">Powod prosby (opcjonalnie)</label>
                        <textarea name="reason" class="form-control" rows="3"
                                  maxlength="500"
                                  placeholder="Krotko opisz powod — pomoze to klubowi udoskonalic obsluge (opcjonalne)."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Potwierdz e-mail konta <span class="text-danger">*</span></label>
                        <input type="email" name="email_confirm" class="form-control" required
                               placeholder="Wpisz adres e-mail przypisany do Twojego konta">
                        <div class="form-text">Wymagane dla potwierdzenia, ze jestes wlascicielem konta.</div>
                    </div>

                    <div class="form-check mb-4">
                        <input type="checkbox" name="understood" id="understood" class="form-check-input" required value="1">
                        <label for="understood" class="form-check-label">
                            <strong>Rozumiem, ze utrace dostep do konta i historii, oraz ze operacja jest nieodwracalna.</strong>
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="<?= url('portal/gdpr') ?>" class="btn btn-secondary">Anuluj</a>
                        <button class="btn btn-danger" type="submit"
                                onclick="return confirm('Na pewno chcesz zlozyc prosbe o usuniecie konta? Otrzymasz e-mail z linkiem potwierdzajacym.');">
                            <i class="bi bi-trash3 me-1"></i> Zloz prosbe o usuniecie
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="alert alert-info mt-3 small">
            <i class="bi bi-info-circle me-2"></i>
            Po zlozeniu prosby otrzymasz e-mail z linkiem potwierdzajacym. Operacja zostanie wykonana
            dopiero po kliknieciu linku (wazny 24h). To 2-step verification zgodne z najlepszymi praktykami.
        </div>
    </div>
</div>
