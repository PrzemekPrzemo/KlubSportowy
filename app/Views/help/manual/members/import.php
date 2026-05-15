<?php /** members / import */ ?>
<p class="lead">Import członków z pliku CSV lub Excel pozwala wprowadzić setki osób w kilka minut. ClubDesk obsługuje mapowanie kolumn, walidację danych przed importem i dry-run (próbny import) bez zapisu do bazy.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Przygotuj plik CSV (UTF-8, separator średnik) lub XLSX. Pobierz szablon w <strong>Członkowie → Import → Szablon</strong>.</li>
    <li>Wymagane kolumny: <code>imie</code>, <code>nazwisko</code>, <code>data_urodzenia</code>, <code>sekcja</code>. Opcjonalne: <code>email</code>, <code>telefon</code>, <code>pesel</code>, <code>adres</code>, <code>kategoria</code>, <code>numer_legitymacji</code>.</li>
    <li>Wejdź w <strong>Członkowie → Import</strong> i przeciągnij plik (lub kliknij <em>Wybierz plik</em>).</li>
    <li>Na ekranie mapowania połącz kolumny z pliku z polami w systemie — system spróbuje dopasować je automatycznie po nazwie.</li>
    <li>Sprawdź podgląd pierwszych 10 rekordów. Błędy walidacji są zaznaczone na czerwono.</li>
    <li>Włącz <em>Tryb próbny (dry-run)</em> aby zobaczyć raport bez zapisu danych.</li>
    <li>Po weryfikacji kliknij <strong>Importuj</strong>. Operacja działa w tle — otrzymasz powiadomienie po zakończeniu.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span></span><span></span><span></span></span>app.clubdesk.pl/members/import</div>
    <div class="manual-mockup-content">
                <div class="mb-3"><div class="border-3 border-dashed border rounded p-4 text-center bg-light"><i class="bi bi-cloud-arrow-up fs-1 text-muted"></i><div>Przeciągnij plik CSV/XLSX lub <a href="#">wybierz z dysku</a></div><small class="text-muted">Maksymalnie 5 MB, do 5000 wierszy</small></div></div>
                <h6 class="mt-3 mb-2">Mapowanie kolumn</h6>
                <table class="table table-sm">
                    <thead class="table-light"><tr><th>Kolumna w pliku</th><th>→</th><th>Pole w ClubDesk</th><th>Próbka</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr><td>imie</td><td><i class="bi bi-arrow-right"></i></td><td><select class="form-select form-select-sm"><option>Imię (member.first_name)</option></select></td><td><code>Jan</code></td><td><i class="bi bi-check-circle-fill text-success"></i></td></tr>
                        <tr><td>nazwisko</td><td><i class="bi bi-arrow-right"></i></td><td><select class="form-select form-select-sm"><option>Nazwisko</option></select></td><td><code>Kowalski</code></td><td><i class="bi bi-check-circle-fill text-success"></i></td></tr>
                        <tr><td>data_ur</td><td><i class="bi bi-arrow-right"></i></td><td><select class="form-select form-select-sm"><option>Data urodzenia</option></select></td><td><code>2010-03-15</code></td><td><i class="bi bi-check-circle-fill text-success"></i></td></tr>
                        <tr><td>e-mail</td><td><i class="bi bi-arrow-right"></i></td><td><select class="form-select form-select-sm"><option>E-mail kontaktowy</option></select></td><td><code>jan@example.com</code></td><td><i class="bi bi-check-circle-fill text-success"></i></td></tr>
                        <tr><td>uwagi</td><td><i class="bi bi-arrow-right"></i></td><td><select class="form-select form-select-sm"><option>— Pomiń —</option></select></td><td><code>VIP klubowy</code></td><td><i class="bi bi-dash-circle text-muted"></i></td></tr>
                    </tbody>
                </table>
                <div class="alert alert-info py-2 small"><i class="bi bi-info-circle"></i> Wykryto 247 wierszy. 245 gotowych do importu, 2 z błędami (kolumna <em>data_urodzenia</em>: nieprawidłowy format).</div>
                <div class="form-check"><input class="form-check-input" type="checkbox" checked><label class="form-check-label small">Wysłać e-maile aktywacyjne po imporcie</label></div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox"><label class="form-check-label small">Tryb próbny (nie zapisuj do bazy)</label></div>
                <button class="btn btn-primary"><i class="bi bi-upload"></i> Rozpocznij import</button>
                <button class="btn btn-outline-secondary">Anuluj</button>
            </div>
    <div class="manual-mockup-caption">Mapowanie kolumn z dynamiczną walidacją i podglądem na żywo.</div>
</div>

<div class="manual-callout manual-callout-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Duplikaty.</strong> System wykrywa duplikaty po kombinacji <em>imię + nazwisko + data urodzenia</em>. Jeśli rekord już istnieje, możesz wybrać: <em>Pomiń</em>, <em>Zaktualizuj</em> lub <em>Dodaj jako nowy</em> (z numerem porządkowym).
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Jaki kodowanie pliku?</summary>
        <div class="faq-body">Wyłącznie UTF-8. Eksportując z Excela, wybierz <em>CSV UTF-8 (rozdzielany przecinkami)</em> w opcjach zapisu. Pliki z polskimi znakami w innym kodowaniu (Windows-1250) dadzą krzaki — system ostrzeże przed importem.</div>
    </details>
    <details>
        <summary>Ile rekordów na raz?</summary>
        <div class="faq-body">Maksymalnie 5000 wierszy w jednym pliku. Dla większych baz podziel na kilka plików lub użyj API.</div>
    </details>
    <details>
        <summary>Co z hasłami członków?</summary>
        <div class="faq-body">Hasła nie są importowane. Każdy zaimportowany członek otrzymuje e-mail z linkiem do utworzenia własnego hasła.</div>
    </details>
</div>
