<?php /** compliance / gdpr-portal */ ?>
<p class="lead">Portal samoobsługowy GDPR (<code>/portal/gdpr</code>) pozwala członkom samodzielnie realizować swoje prawa z RODO — przeglądać zgody, eksportować dane, wnioskować o anonimizację — bez angażowania administratora. Spełnia art. 12 RODO o przejrzystej komunikacji.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Włącz portal w <strong>Ustawienia → RODO → Portal samoobsługowy</strong> (domyślnie ON).</li>
    <li>Członek loguje się i wchodzi w <em>Mój profil → RODO</em>.</li>
    <li>Widzi listę aktywnych zgód i może każdą cofnąć kliknięciem.</li>
    <li>Może wygenerować eksport swoich danych (link aktywny 7 dni).</li>
    <li>Może złożyć wniosek o anonimizację — wymaga potwierdzenia mailem.</li>
    <li>Wszystkie akcje są logowane i widoczne w panelu administratora.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span></span><span></span><span></span></span>app.clubdesk.pl/portal/gdpr</div>
    <div class="manual-mockup-content">
                <h6><i class="bi bi-shield-check"></i> Moje prawa RODO</h6>
                <div class="row g-3">
                    <div class="col-md-6"><div class="card"><div class="card-body"><h6>Aktywne zgody</h6>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" checked><label class="form-check-label">Przetwarzanie danych podstawowych <small class="text-muted d-block">Wymagane — nie można cofnąć podczas członkostwa</small></label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" checked><label class="form-check-label">Zgoda na wizerunek (zdjęcia z meczów)</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox"><label class="form-check-label">Newsletter klubowy</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" checked><label class="form-check-label">Komunikacja SMS</label></div>
                    </div></div></div>
                    <div class="col-md-6"><div class="card"><div class="card-body"><h6>Moje prawa</h6>
                        <button class="btn btn-sm btn-outline-primary w-100 mb-2"><i class="bi bi-download"></i> Pobierz moje dane (RODO art. 20)</button>
                        <button class="btn btn-sm btn-outline-secondary w-100 mb-2"><i class="bi bi-pencil"></i> Sprostuj moje dane (art. 16)</button>
                        <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash"></i> Wnioskuj o usunięcie (art. 17)</button>
                        <small class="text-muted mt-2 d-block">Odpowiedź w ciągu 30 dni zgodnie z art. 12 ust. 3 RODO.</small>
                    </div></div></div>
                </div>
            </div>
    <div class="manual-mockup-caption">Portal samoobsługowy GDPR — zgody i prawa członka w jednym ekranie.</div>
</div>

<div class="manual-callout manual-callout-tip">
    <strong><i class="bi bi-lightbulb"></i> Pełna zgodność.</strong> Portal spełnia art. 7, 12-22 RODO. Po wdrożeniu eliminujesz większość pisemnych wniosków członków — i nie tracisz na zgodności.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy portal działa bez logowania?</summary>
        <div class="faq-body">Cofnięcie zgody marketingowej — tak (przez link w mailu). Eksport danych i usunięcie — tylko po zalogowaniu.</div>
    </details>
    <details>
        <summary>Co jeśli członek cofnie zgodę kluczową?</summary>
        <div class="faq-body">Niektóre zgody są wymagane do trwania umowy (np. dane podstawowe). System wyświetla wyjaśnienie i nie pozwala cofnąć.</div>
    </details>
    <details>
        <summary>Jak długo przechowujemy logi zgód?</summary>
        <div class="faq-body">Bezterminowo — to dowód realizacji obowiązku informacyjnego (art. 7 ust. 1 RODO).</div>
    </details>
</div>
