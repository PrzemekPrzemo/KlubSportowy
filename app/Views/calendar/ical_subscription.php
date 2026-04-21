<?php use App\Helpers\View; ?>
<?php $layout = 'layouts/main'; ?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-check fs-5 text-primary"></i>
                    <h5 class="mb-0"><?= View::e($title) ?></h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Skopiuj poniższy adres URL i dodaj go jako subskrypcję kalendarza
                        w Google Calendar, Apple Calendar, Outlook lub innym kliencie obsługującym format iCal.
                        Kalendarz będzie automatycznie aktualizowany.
                    </p>

                    <label class="form-label fw-semibold">URL subskrypcji (iCal/WebCal)</label>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control font-monospace" id="icalUrl"
                               value="<?= View::e($icalUrl) ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyIcalUrl()">
                            <i class="bi bi-clipboard"></i> Kopiuj
                        </button>
                    </div>
                    <div id="copyAlert" class="alert alert-success d-none py-2">
                        <i class="bi bi-check-circle"></i> Adres skopiowany do schowka.
                    </div>

                    <hr>
                    <h6 class="mb-3">Jak dodać w popularnych aplikacjach</h6>
                    <div class="accordion" id="icalHelp">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#helpGoogle">
                                    <i class="bi bi-google me-2"></i> Google Calendar
                                </button>
                            </h2>
                            <div id="helpGoogle" class="accordion-collapse collapse" data-bs-parent="#icalHelp">
                                <div class="accordion-body small">
                                    <ol class="mb-0">
                                        <li>Otwórz <strong>Google Calendar</strong> → kliknij <strong>+</strong> przy „Inne kalendarze"</li>
                                        <li>Wybierz <strong>„Z adresu URL"</strong></li>
                                        <li>Wklej skopiowany URL</li>
                                        <li>Kliknij <strong>Dodaj kalendarz</strong></li>
                                    </ol>
                                    <p class="mt-2 mb-0 text-muted">Google aktualizuje subskrypcję co ok. 24 godziny.</p>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#helpApple">
                                    <i class="bi bi-apple me-2"></i> Apple Calendar (macOS / iOS)
                                </button>
                            </h2>
                            <div id="helpApple" class="accordion-collapse collapse" data-bs-parent="#icalHelp">
                                <div class="accordion-body small">
                                    <ol class="mb-0">
                                        <li><strong>macOS:</strong> Plik → Nowy subskrybowany kalendarz → wklej URL</li>
                                        <li><strong>iOS:</strong> Ustawienia → Aplikacje → Kalendarz → Konta → Dodaj konto → Inne → Dodaj subskrybowany kalendarz</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#helpOutlook">
                                    <i class="bi bi-microsoft me-2"></i> Microsoft Outlook
                                </button>
                            </h2>
                            <div id="helpOutlook" class="accordion-collapse collapse" data-bs-parent="#icalHelp">
                                <div class="accordion-body small">
                                    <ol class="mb-0">
                                        <li>Otwórz Outlook → Kalendarz → <strong>Dodaj kalendarze</strong></li>
                                        <li>Wybierz <strong>„Subskrybuj z Internetu"</strong></li>
                                        <li>Wklej URL i kliknij <strong>Importuj</strong></li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4 mb-0 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Feed zawiera publiczne i klubowe zdarzenia z ostatnich 30 dni oraz następnych 365 dni.
                        URL jest unikalny dla Twojego klubu — nie udostępniaj go publicznie.
                    </div>
                </div>
                <div class="card-footer text-end">
                    <a href="/calendar" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Wróć do kalendarza
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyIcalUrl() {
    const input = document.getElementById('icalUrl');
    navigator.clipboard.writeText(input.value).then(() => {
        const alert = document.getElementById('copyAlert');
        alert.classList.remove('d-none');
        setTimeout(() => alert.classList.add('d-none'), 3000);
    }).catch(() => {
        input.select();
        document.execCommand('copy');
    });
}
</script>
