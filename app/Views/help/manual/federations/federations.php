<?php /** federations / federations */ ?>
<p class="lead">ClubDesk integruje się z 9 polskimi federacjami sportowymi (PZPN, PZSS, PZLA, PZHL, PZPS, PZJ, PZW, PZKosz, PZTS), pozwalając zarządzać licencjami zawodników, zgłaszać turnieje i synchronizować wyniki z systemami federacyjnymi.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Otwórz <strong>Klub → Federacje → + Połącz federację</strong>.</li>
    <li>Wybierz federację z listy.</li>
    <li>Wprowadź numer identyfikacyjny klubu w danej federacji (np. nr klubu PZPN) i klucz API (wydawany przez federację).</li>
    <li>Autoryzuj połączenie — system zweryfikuje uprawnienia.</li>
    <li>Skonfiguruj synchronizację: <em>jednokierunkowa</em> (federacja → klub) lub <em>dwukierunkowa</em>.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span></span><span></span><span></span></span>app.clubdesk.pl/club/federations</div>
    <div class="manual-mockup-content">
                <h6 class="mb-3"><i class="bi bi-diagram-3"></i> Federacje sportowe</h6>
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>Federacja</th><th>Dyscyplina</th><th>Numer klubu</th><th>Status</th><th>Ost. sync</th><th></th></tr></thead>
                    <tbody>
                        <tr><td><strong>PZPN</strong> — Polski Związek Piłki Nożnej</td><td>⚽ Football</td><td>1234567</td><td><span class="badge bg-success">Aktywne</span></td><td>14 maj 12:30</td><td><a href="#" class="btn btn-sm btn-outline-secondary">Konfiguracja</a></td></tr>
                        <tr><td><strong>PZPS</strong> — Polski Związek Piłki Siatkowej</td><td>🏐 Volleyball</td><td>VPS-4421</td><td><span class="badge bg-success">Aktywne</span></td><td>14 maj 08:15</td><td><a href="#" class="btn btn-sm btn-outline-secondary">Konfiguracja</a></td></tr>
                        <tr><td><strong>PZLA</strong> — Polski Związek Lekkiej Atletyki</td><td>🏃 Athletics</td><td>—</td><td><span class="badge bg-secondary">Nieaktywne</span></td><td>—</td><td><a href="#" class="btn btn-sm btn-primary">+ Połącz</a></td></tr>
                        <tr><td><strong>PZSS</strong> — Polski Związek Strzelectwa Sportowego</td><td>🎯 Shooting</td><td>SS-7762</td><td><span class="badge bg-warning text-dark">Token wygasa za 5 dni</span></td><td>13 maj 22:00</td><td><a href="#" class="btn btn-sm btn-warning">Odnów</a></td></tr>
                        <tr><td><strong>PZKosz</strong> — Polski Związek Koszykówki</td><td>🏀 Basketball</td><td>—</td><td><span class="badge bg-secondary">Nieaktywne</span></td><td>—</td><td><a href="#" class="btn btn-sm btn-primary">+ Połącz</a></td></tr>
                    </tbody>
                </table>
            </div>
    <div class="manual-mockup-caption">Lista podłączonych federacji ze statusami i datą ostatniej synchronizacji.</div>
</div>

<div class="manual-callout manual-callout-tip">
    <strong><i class="bi bi-lightbulb"></i> Automatyczne zgłaszanie turniejów.</strong> Po włączeniu integracji turnieje organizowane przez klub mogą być automatycznie zgłaszane do federacji — bez ręcznego wypełniania formularzy.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy wszystkie federacje wspierają API?</summary>
        <div class="faq-body">Tak, ale w różnym zakresie. PZPN i PZPS mają pełne API (read+write). PZLA i PZJ tylko read-only. PZW głównie webhook.</div>
    </details>
    <details>
        <summary>Co jeśli token API wygaśnie?</summary>
        <div class="faq-body">System ostrzega 14/7 dni przed wygaśnięciem. Odnowienie — w panelu federacji + paste nowego tokenu.</div>
    </details>
    <details>
        <summary>Czy mogę używać federacji bez API?</summary>
        <div class="faq-body">Tak — wpisuj dane ręcznie. System nadal pomaga w generowaniu raportów do PDF/XML zgodnie ze schemą federacji.</div>
    </details>
</div>
