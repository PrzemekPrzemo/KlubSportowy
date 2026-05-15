<?php /** Lista członków, filtry i wyszukiwanie */ ?>
<p class="lead">Lista członków to centralne miejsce zarządzania ewidencją klubu. Z poziomu jednego ekranu możesz wyszukiwać osoby, filtrować po sekcji, statusie składek, kategorii wiekowej, a także wykonywać operacje grupowe.</p>

<h2>Otwarcie listy</h2>
<p>Przejdź do <strong>Członkowie → Lista</strong> w menu głównym. Widok domyślny zawiera wszystkich aktywnych członków klubu posortowanych alfabetycznie po nazwisku.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span></span><span></span><span></span></span>app.clubdesk.pl/members</div>
    <div class="manual-mockup-content">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body small">
                        <h6 class="mb-2"><i class="bi bi-funnel"></i> Filtry</h6>
                        <label class="form-label mb-1">Sekcja</label>
                        <select class="form-select form-select-sm mb-2">
                            <option>Wszystkie</option>
                            <option>Piłka nożna — seniorzy</option>
                            <option>Siatkówka U-16</option>
                        </select>
                        <label class="form-label mb-1">Status składek</label>
                        <select class="form-select form-select-sm mb-2">
                            <option>Wszystkie</option>
                            <option>Opłacone</option>
                            <option>Zaległe</option>
                        </select>
                        <label class="form-label mb-1">Kategoria wiekowa</label>
                        <select class="form-select form-select-sm mb-2">
                            <option>Wszystkie</option>
                            <option>Junior</option>
                            <option>Senior</option>
                        </select>
                        <label class="form-label mb-1">Status członka</label>
                        <div class="form-check"><input class="form-check-input" type="checkbox" checked><label class="form-check-label">Aktywny</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox"><label class="form-check-label">Zawieszony</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox"><label class="form-check-label">Archiwum</label></div>
                        <button class="btn btn-sm btn-primary mt-2 w-100">Zastosuj filtry</button>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="d-flex gap-2 mb-2">
                    <input class="form-control form-control-sm" placeholder="Szukaj po imieniu, nazwisku, e-mailu, telefonie…" value="">
                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-download"></i> Eksport</button>
                    <button class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> Dodaj</button>
                </div>
                <table class="table table-hover table-sm align-middle">
                    <thead class="table-light">
                        <tr><th><input type="checkbox"></th><th>#</th><th>Imię i nazwisko</th><th>Sekcja</th><th>Wiek</th><th>Składki</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <tr><td><input type="checkbox"></td><td>1024</td><td>Kowalski Jan</td><td>Piłka nożna</td><td>23</td><td><span class="badge bg-success">Opłacone</span></td><td>Aktywny</td><td><i class="bi bi-three-dots"></i></td></tr>
                        <tr><td><input type="checkbox"></td><td>1025</td><td>Nowak Anna</td><td>Siatkówka U-16</td><td>15</td><td><span class="badge bg-danger">Zaległe 80 zł</span></td><td>Aktywny</td><td><i class="bi bi-three-dots"></i></td></tr>
                        <tr><td><input type="checkbox"></td><td>1026</td><td>Wiśniewski Piotr</td><td>Piłka nożna</td><td>34</td><td><span class="badge bg-success">Opłacone</span></td><td>Aktywny</td><td><i class="bi bi-three-dots"></i></td></tr>
                        <tr><td><input type="checkbox"></td><td>1027</td><td>Lewandowska Maria</td><td>Strzelectwo</td><td>28</td><td><span class="badge bg-warning text-dark">7 dni</span></td><td>Aktywny</td><td><i class="bi bi-three-dots"></i></td></tr>
                    </tbody>
                </table>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Wyświetlono 4 z 247 członków</small>
                    <nav><ul class="pagination pagination-sm mb-0"><li class="page-item active"><a class="page-link">1</a></li><li class="page-item"><a class="page-link">2</a></li><li class="page-item"><a class="page-link">3</a></li><li class="page-item"><a class="page-link">…</a></li><li class="page-item"><a class="page-link">25</a></li></ul></nav>
                </div>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Lista członków z sidebarem filtrów po lewej i wyszukiwarką nad tabelą.</div>
</div>

<h2>Wyszukiwanie</h2>
<p>Pole wyszukiwania (Ctrl + K na klawiaturze) przeszukuje równocześnie: imię, nazwisko, e-mail, telefon, numer karty członkowskiej i PESEL (jeśli wprowadzony). Wyszukiwanie jest fonetyczne — wpisanie „kowalski" znajdzie też „Kowalsky" i „Kowal Ski".</p>

<h2>Sortowanie i kolumny</h2>
<p>Kliknij nagłówek kolumny, aby zmienić sortowanie (rosnące/malejące). Przycisk <em>Kolumny</em> (ikona <i class="bi bi-layout-three-columns"></i>) pozwala dostosować widoczne kolumny — np. dodać <em>Datę dołączenia</em>, <em>Numer licencji federacji</em> czy <em>Tag</em>. Ustawienia widoku są zapisywane per użytkownik.</p>

<h2>Operacje grupowe</h2>
<p>Po zaznaczeniu kilku członków (checkbox w pierwszej kolumnie) na górze pojawia się pasek akcji: <em>Wyślij e-mail</em>, <em>Wyślij SMS</em>, <em>Zmień status</em>, <em>Wystaw fakturę</em>, <em>Eksport CSV</em>, <em>Zarchiwizuj</em>. Szczegóły opisaliśmy w sekcji <a href="<?= url('help/admin-members-bulk-ops') ?>">Operacje grupowe</a>.</p>

<h2>Dobre praktyki</h2>
<div class="manual-callout manual-callout-tip">
    Twórz <strong>filtry zapisane</strong> dla najczęstszych widoków — np. „Zaległe składki ponad 30 dni", „Juniorzy bez aktualnych badań". Zapisany filtr otwiera się jednym kliknięciem.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details><summary>Jak wyświetlić byłych członków?</summary><div class="faq-body">W filtrach zaznacz status <em>Archiwum</em>. Możesz też przejść do zakładki <em>Członkowie → Archiwum</em> dla pełnej listy historycznej.</div></details>
    <details><summary>Czy mogę eksportować widok do Excela?</summary><div class="faq-body">Tak — przycisk <em>Eksport</em> oferuje CSV, XLSX i PDF. Eksport uwzględnia aktualne filtry i sortowanie.</div></details>
    <details><summary>Skąd biorą się statusy składek?</summary><div class="faq-body">Z modułu Finanse — system codziennie odświeża status na podstawie nieopłaconych faktur i terminów.</div></details>
</div>
