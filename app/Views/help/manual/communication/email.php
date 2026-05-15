<?php /** communication / email */ ?>
<p class="lead">Kampanie email pozwalają wysyłać masowe, spersonalizowane wiadomości do dowolnej grupy członków. ClubDesk dostarcza WYSIWYG editor, szablony, listy mailingowe i pełne statystyki (open rate, click rate).</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Wejdź w <strong>Komunikacja → Email → + Nowa kampania</strong>.</li>
    <li>Wybierz szablon (lub <em>Pusty</em> dla od zera).</li>
    <li>Wpisz temat (A/B testing dostępny — porównaj 2 wersje na próbce 10%).</li>
    <li>Skomponuj treść — drag & drop sekcji (nagłówek, obrazek, tekst, przycisk CTA, stopka).</li>
    <li>Użyj zmiennych personalizujących: <code>{{imię}}</code>, <code>{{sekcja}}</code>, <code>{{kwota_zaległości}}</code>.</li>
    <li>Wybierz odbiorców (cała baza, segment, lista zapisanych).</li>
    <li>Wyślij od razu lub zaplanuj na konkretną datę.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span></span><span></span><span></span></span>app.clubdesk.pl/communication/email/new</div>
    <div class="manual-mockup-content">
                <div class="row g-3">
                    <div class="col-md-7">
                        <div class="mb-2"><label class="form-label small">Nazwa kampanii (wewnętrzna)</label><input class="form-control form-control-sm" value="Letni obóz 2026 — zapisy"></div>
                        <div class="mb-2"><label class="form-label small">Temat</label><input class="form-control form-control-sm" value="🏖️ Letni obóz w Górach — zapisz dziecko już dziś!"></div>
                        <div class="mb-2"><label class="form-label small">Nadawca</label><input class="form-control form-control-sm" value="KS Orły &lt;info@orly-warszawa.pl&gt;"></div>
                        <label class="form-label small">Treść</label>
                        <div class="border rounded p-3 bg-white">
                            <div class="text-center mb-2"><strong>KS Orły Warszawa</strong></div>
                            <p class="small">Cześć {{imię}},</p>
                            <p class="small">Mamy świetną wiadomość — zapisy na <strong>Letni obóz w Górach Bieszczadach</strong> wystartowały! 🎉</p>
                            <p class="small">Termin: 1-14 lipca 2026 · Cena: 2400 zł · Liczba miejsc: 30</p>
                            <div class="text-center"><button class="btn btn-primary btn-sm">Zapisz dziecko</button></div>
                            <hr><small class="text-muted">Aby zrezygnować z newslettera, kliknij <a href="#">tutaj</a>.</small>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="card"><div class="card-body small">
                            <h6>Odbiorcy</h6>
                            <select class="form-select form-select-sm mb-2"><option>Segment: Rodzice U-12 i U-14</option></select>
                            <div class="alert alert-info py-2"><i class="bi bi-people"></i> <strong>156</strong> odbiorców</div>
                            <h6 class="mt-3">Personalizacja</h6>
                            <ul class="list-unstyled">
                                <li><code>{{imię}}</code> — imię odbiorcy</li>
                                <li><code>{{sekcja}}</code> — sekcja dziecka</li>
                                <li><code>{{klub}}</code> — nazwa klubu</li>
                            </ul>
                            <h6 class="mt-3">Wysyłka</h6>
                            <div class="form-check"><input class="form-check-input" type="radio" name="when" checked><label class="form-check-label">Wyślij teraz</label></div>
                            <div class="form-check"><input class="form-check-input" type="radio" name="when"><label class="form-check-label">Zaplanuj</label></div>
                        </div></div>
                        <button class="btn btn-primary w-100 mt-2"><i class="bi bi-send"></i> Wyślij kampanię</button>
                        <button class="btn btn-outline-secondary w-100 mt-1">Wyślij test do siebie</button>
                    </div>
                </div>
            </div>
    <div class="manual-mockup-caption">Kreator kampanii email z podglądem, segmentacją odbiorców i personalizacją.</div>
</div>

<div class="manual-callout manual-callout-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Deliverability.</strong> Skonfiguruj SPF, DKIM i DMARC dla domeny klubu, aby maile nie trafiały do spamu. Instrukcje w <em>Komunikacja → Email → Deliverability</em>.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Ile maili dziennie?</summary>
        <div class="faq-body">Plan Starter: 1000/dzień. Pro: 10000/dzień. Enterprise: bez limitu.</div>
    </details>
    <details>
        <summary>Czy używamy własnego SMTP?</summary>
        <div class="faq-body">Domyślnie SMTP ClubDesk (SendGrid). Możesz włączyć custom SMTP (Mailgun, AWS SES, własny serwer) w <em>Komunikacja → SMTP</em>.</div>
    </details>
    <details>
        <summary>Co z RODO i listą wypisów?</summary>
        <div class="faq-body">Każdy mail ma automatyczny link „Wypisz się". System honoruje wypisy bezterminowo.</div>
    </details>
</div>
