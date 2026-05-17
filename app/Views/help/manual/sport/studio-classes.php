<?php /** sport / studio-classes — joga, fitness, pilates */ ?>
<p class="lead">
    Moduł studio (joga, fitness, pilates) obsługuje zajęcia grupowe z karnetami wielokrotnego użycia
    i samodzielnym zapisem zawodników przez portal. Instruktor wykonuje check-in z grida obecności,
    a system automatycznie zarządza listą rezerwową (waitlist) i zwrotami niewykorzystanych wejść.
</p>

<h2>Konfiguracja — krok po kroku</h2>
<ol>
    <li><strong>Dodaj klasy</strong> — Klub → Klasy studio (yoga/fitness/pilates) → <em>Nowa klasa</em>.
        Określ nazwę (np. „Yoga Vinyasa"), dzień tygodnia, godzinę startu, czas (min) i limit miejsc (sala).</li>
    <li><strong>Skonfiguruj karnety</strong> — Klasy → Karnety. Standardowe typy:
        <em>single (1 wejście)</em>, <em>multi_class (4/8 wejść)</em>, <em>unlimited_period (open miesiąc)</em>.
        Ustaw cenę w PLN i ważność w dniach.</li>
    <li><strong>Sprawdź portal zawodnika</strong> — Portal → Zajęcia studio → Katalog klas.
        Zawodnik widzi kafelki klas pogrupowane po dniach z guzikiem „Zapisz".</li>
    <li><strong>Check-in na zajęciach</strong> — Klub → Klasy → kliknij nazwę klasy
        (przekierowuje na roster z domyślną datą dzisiaj). Dla każdego zapisanego kliknij
        <em>Check-in</em> lub <em>No-show</em> po zakończeniu.</li>
    <li><strong>Monitoruj sprzedaż</strong> — Klasy → Raport karnetów. Pokazuje liczbę aktywnych /
        wyczerpanych / wygasłych karnetów i przychód brutto.</li>
</ol>

<h2>Logika karnetu (consume / refund)</h2>
<ul>
    <li>Każdy zapis na zajęcia <strong>zużywa 1 wejście</strong> (atomowo w transakcji DB).</li>
    <li>Karnet <em>unlimited_period</em> nie dekrementuje wejść — tylko sprawdza datę ważności.</li>
    <li>Anulacja w oknie <strong>12h przed startem</strong> → wejście wraca na karnet.</li>
    <li>Anulacja po tym oknie → wejście przepadło (bez refundu).</li>
    <li>Klasa pełna → status <code>waitlist</code>; przy anulacji innego zawodnika system
        automatycznie promuje pierwszego z kolejki (i zużywa jego pass).</li>
</ul>

<h2>Multi-tenant + bezpieczeństwo</h2>
<ul>
    <li>Wszystkie zapytania filtrują po <code>club_id</code> z <code>ClubContext</code>.</li>
    <li>Karnet zawodnika klubu A <strong>nie może</strong> zostać użyty w klubie B
        (kontrola w <code>StudioMemberPassModel::consumeOne()</code>).</li>
    <li>UNIQUE <code>(schedule_id, member_id, class_date)</code> gwarantuje idempotency zapisu
        (double-submit nie tworzy duplikatu).</li>
    <li>Wszystkie POST wymagają tokenu CSRF.</li>
</ul>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span></span><span></span><span></span></span>app.clubdesk.pl/club/studio/yoga/roster</div>
    <div class="manual-mockup-content">
        <div class="d-flex justify-content-between mb-3">
            <div><strong>Yoga Vinyasa</strong> — 2026-05-17, 18:00 · Sala duża<br>
                <small class="text-muted">Limit 15 · zapisanych 9 · waitlist 2</small></div>
            <button class="btn btn-sm btn-outline-secondary">← Klasy</button>
        </div>
        <table class="table table-hover">
            <thead class="table-light"><tr><th>#</th><th>Zawodnik</th><th>Karnet</th><th>Status</th><th>Akcja</th></tr></thead>
            <tbody>
                <tr><td>1</td><td>Anna Kowalska</td><td>#41</td><td><span class="badge bg-success">✓ Obecna</span></td><td>—</td></tr>
                <tr><td>2</td><td>Piotr Nowak</td><td>#42</td><td><span class="badge bg-primary">Zapisany</span></td>
                    <td><button class="btn btn-sm btn-success">Check-in</button></td></tr>
                <tr><td>3</td><td>Marta Wiśniewska</td><td>#44</td><td><span class="badge bg-warning text-dark">⏳ Waitlist</span></td><td>—</td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Grid check-in z badge'ami statusów + jednoklikowy Check-in.</div>
</div>
