<?php /** Brand klubu — logo, kolory, motto */ ?>
<p class="lead">Brand klubu jest widoczny dla wszystkich członków, trenerów i zewnętrznych odbiorców komunikacji (faktury, e-maile, portal członka). Spójna identyfikacja wizualna buduje profesjonalny wizerunek i pomaga w marketingu.</p>

<h2>Co możesz dostosować</h2>
<ul>
    <li><strong>Logo</strong> — wersja pełna (nagłówek) i kwadratowa (favicon, awatar w aplikacji mobilnej).</li>
    <li><strong>Kolor główny</strong> — używany w przyciskach, linkach, badge'ach i nagłówku.</li>
    <li><strong>Kolor akcentu</strong> — pojawia się w wykresach i elementach dekoracyjnych.</li>
    <li><strong>Motto / hasło klubu</strong> — wyświetlane w portalu członka i na ekranie logowania.</li>
    <li><strong>Custom domain</strong> — np. <code>panel.naszklub.pl</code> zamiast <code>nazwaklubu.clubdesk.pl</code> (plan Pro+).</li>
</ul>

<h2>Krok po kroku</h2>
<ol>
    <li>Przejdź do <strong>Ustawienia → Wygląd i branding</strong>.</li>
    <li>Wgraj logo główne w formacie PNG lub SVG, minimum 240×80 px, tło transparentne.</li>
    <li>Wgraj wersję kwadratową 512×512 px (favicon będzie z niej wygenerowany automatycznie).</li>
    <li>Wybierz kolor główny z palety lub wpisz wartość HEX (np. <code>#EE2C28</code>).</li>
    <li>Sprawdź podgląd na żywo — wszystkie zmiany widoczne są w panelu po lewej stronie.</li>
    <li>Kliknij <strong>Zapisz</strong>. Zmiany propagują się natychmiast dla wszystkich użytkowników klubu.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span></span><span></span><span></span></span>app.clubdesk.pl/settings/branding</div>
    <div class="manual-mockup-content">
        <div class="row g-3">
            <div class="col-md-7">
                <h6 class="mb-3"><i class="bi bi-palette"></i> Branding klubu</h6>
                <div class="mb-3">
                    <label class="form-label small">Logo główne (nagłówek)</label>
                    <div class="border rounded p-3 d-flex align-items-center gap-3">
                        <div class="bg-light p-2 rounded" style="width:140px;height:50px;display:flex;align-items:center;justify-content:center;color:#888;">LOGO 240×80</div>
                        <button class="btn btn-sm btn-outline-secondary">Zmień</button>
                        <button class="btn btn-sm btn-outline-danger">Usuń</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Kolor główny</label>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="d-inline-block rounded" style="width:32px;height:32px;background:#EE2C28;border:1px solid #ccc;"></span>
                        <input class="form-control form-control-sm" style="max-width:120px;" value="#EE2C28">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Kolor akcentu</label>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="d-inline-block rounded" style="width:32px;height:32px;background:#0d6efd;border:1px solid #ccc;"></span>
                        <input class="form-control form-control-sm" style="max-width:120px;" value="#0D6EFD">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Motto klubu</label>
                    <input class="form-control" value="Siła zespołu, duma klubu">
                </div>
                <button class="btn btn-primary">Zapisz zmiany</button>
            </div>
            <div class="col-md-5">
                <label class="form-label small">Podgląd</label>
                <div class="border rounded">
                    <div style="background:#EE2C28;color:#fff;padding:.5rem .75rem;font-weight:600;">KS Orły Warszawa</div>
                    <div class="p-2 small">
                        <span class="badge" style="background:#EE2C28;">Aktywny</span>
                        <span class="badge" style="background:#0D6EFD;">Nowość</span>
                        <p class="text-muted mt-2 mb-0">Siła zespołu, duma klubu</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Ekran Ustawienia → Branding z podglądem zmian na żywo.</div>
</div>

<h2>Dobre praktyki</h2>
<div class="manual-callout manual-callout-tip">
    <strong>Kontrast i dostępność.</strong> Wybierając kolor główny, upewnij się że biały tekst pozostaje czytelny na jego tle (kontrast WCAG AA wymaga współczynnika min. 4.5:1). System automatycznie ostrzeże Cię o niewystarczającym kontraście.
</div>
<ul>
    <li>Logo SVG skaluje się idealnie na wszystkich ekranach — preferuj ten format zamiast PNG.</li>
    <li>Trzymaj logo w jednej wersji kolorystycznej na białym tle — wersja na ciemne tło jest generowana automatycznie.</li>
    <li>Unikaj kolorów neonowych jako głównych — w masowej komunikacji męczą wzrok.</li>
</ul>

<h2>Custom domain (plan Pro+)</h2>
<p>Aby skonfigurować własną domenę, wejdź w <em>Ustawienia → Domena własna</em> i postępuj zgodnie z instrukcją: dodaj rekord CNAME w panelu DNS swojego dostawcy (np. <code>panel.naszklub.pl → app.clubdesk.pl</code>), poczekaj na propagację (5-30 minut) i kliknij <em>Zweryfikuj</em>. Certyfikat SSL Let's Encrypt zostanie wygenerowany automatycznie.</p>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy mogę mieć dwa logo — jasne i ciemne?</summary>
        <div class="faq-body">Tak. W sekcji <em>Logo zaawansowane</em> wgraj wariant dla trybu ciemnego. System użyje go automatycznie u członków z włączonym dark mode.</div>
    </details>
    <details>
        <summary>Co z brandingiem na fakturach?</summary>
        <div class="faq-body">Logo i kolor główny są automatycznie używane w generowanych fakturach PDF. Dodatkowe dane (NIP, REGON, adres) konfigurujesz w <em>Ustawienia → Dane do faktur</em>.</div>
    </details>
    <details>
        <summary>Czy zmiana brandingu wpłynie na link członków?</summary>
        <div class="faq-body">Nie. URL pozostaje bez zmian, modyfikowany jest wyłącznie wygląd. Custom domain wymaga osobnej konfiguracji DNS.</div>
    </details>
</div>
