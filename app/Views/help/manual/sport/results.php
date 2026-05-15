<?php /** sport / results */ ?>
<p class="lead">Wpisywanie wyników to najczęstsza operacja podczas turnieju. ClubDesk wspiera różne typy wyników w zależności od dyscypliny: set/gem (tenis), gole + karne (piłka), punkty + remis (siatkówka), czas (lekkoatletyka), waga (judo).</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Otwórz drabinkę turnieju lub mecz z kalendarza.</li>
    <li>Kliknij mecz, dla którego wpisujesz wynik.</li>
    <li>Wypełnij pola wyniku zgodnie z formularzem właściwym dla dyscypliny.</li>
    <li>Opcjonalnie dodaj statystyki indywidualne (gole, asysty, kartki — dla piłki nożnej).</li>
    <li>Załącz protokół sędziowski (PDF) i (opcjonalnie) zdjęcia z meczu.</li>
    <li>Zatwierdź wynik kliknięciem <strong>Zapisz</strong>. System przeliczy ranking i drabinkę automatycznie.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span></span><span></span><span></span></span>app.clubdesk.pl/tournaments/123/match/45</div>
    <div class="manual-mockup-content">
                <div class="card"><div class="card-body">
                    <h6 class="mb-3">Finał — Puchar klubu 2026</h6>
                    <div class="row align-items-center text-center mb-3">
                        <div class="col-5"><strong>Drużyna A</strong><br>Orły Warszawa</div>
                        <div class="col-2"><h2>2 : 1</h2></div>
                        <div class="col-5"><strong>Drużyna B</strong><br>Sokoły Kraków</div>
                    </div>
                    <hr>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Bramki Drużyna A</label>
                            <input type="number" class="form-control form-control-sm" value="2">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Bramki Drużyna B</label>
                            <input type="number" class="form-control form-control-sm" value="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Strzelcy A</label>
                            <input class="form-control form-control-sm" value="Kowalski J. (15'), Nowak P. (78')">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Strzelcy B</label>
                            <input class="form-control form-control-sm" value="Wiśniewski (62')">
                        </div>
                        <div class="col-md-6"><label class="form-label small">Kartki żółte</label><input class="form-control form-control-sm" value="2 (A), 3 (B)"></div>
                        <div class="col-md-6"><label class="form-label small">Czas dodany</label><input class="form-control form-control-sm" value="4 min"></div>
                    </div>
                    <hr>
                    <div class="d-flex gap-2 align-items-center">
                        <label class="form-label small mb-0">Protokół (PDF):</label>
                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-paperclip"></i> Załącz</button>
                        <span class="ms-auto"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Zatwierdź wynik</button></span>
                    </div>
                </div></div>
            </div>
    <div class="manual-mockup-caption">Formularz wpisywania wyniku meczu z statystykami i protokołem.</div>
</div>

<div class="manual-callout manual-callout-warn">
    <strong><i class="bi bi-exclamation-triangle"></i> Auto-recalc.</strong> Zmiana wyniku po zatwierdzeniu drabinki przelicza wszystkie następne rundy. System ostrzega i wymaga podwójnego potwierdzenia.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Kto może wpisywać wyniki?</summary>
        <div class="faq-body">Sędzia przypisany do turnieju, trener sekcji, administrator klubu. Konflikt — kto ostatni, ten wygrywa (z logiem).</div>
    </details>
    <details>
        <summary>Czy mogę edytować wynik historyczny?</summary>
        <div class="faq-body">Tak, ale każda zmiana wymaga uzasadnienia (np. reklamacja sędziowska) i jest widoczna w audit logu.</div>
    </details>
    <details>
        <summary>Czy zawodnicy widzą wyniki?</summary>
        <div class="faq-body">Tak — w portalu i (opcjonalnie) publicznie.</div>
    </details>
</div>
