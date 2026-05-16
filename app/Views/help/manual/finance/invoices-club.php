<?php /** finance / invoices-club — KSeF Phase 2 */ ?>
<p class="lead">Moduł <strong>Faktury sprzedaży klubu</strong> pozwala wystawiać faktury VAT za składki członkowskie, opłaty turniejowe, sprzęt sklepowy i inne pozycje sprzedażowe — z przygotowaniem do wysyłki do Krajowego Systemu e-Faktur (KSeF) w schemacie FA(2).</p>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    <strong>Phase 2 (ten release)</strong> obejmuje wystawianie, edycję szkiców, generowanie PDF oraz podgląd XML KSeF FA(2).
    Wysyłka do KSeF + podpis XAdES + odbiór UPO zostaną uruchomione w <strong>Phase 3</strong>.
</div>

<h2>Kiedy używać faktur klubowych?</h2>
<ul>
    <li>Klub jest podatnikiem VAT (lub VAT-zw) i wystawia faktury członkom/sponsorom.</li>
    <li>Składka członkowska została opłacona i członek prosi o fakturę (B2C lub B2B z NIP).</li>
    <li>Klub sprzedaje sprzęt, koszulki, vouchery i potrzebuje dokumentu sprzedaży.</li>
    <li>Przygotowanie do obowiązkowej wysyłki KSeF (rok obowiązkowości zgodnie z ustawą).</li>
</ul>

<h2>Workflow krok po kroku</h2>
<ol>
    <li>
        <strong>Włącz KSeF dla klubu</strong> — wymaga akcji administratora platformy (super admin)
        w <code>/admin/platform/ksef</code>. Bez tego sekcja nie pojawia się w menu.
    </li>
    <li>
        <strong>Skonfiguruj klub</strong>: <em>Finanse → KSeF — konfiguracja</em>. Wpisz NIP klubu (10 cyfr,
        z poprawną sumą kontrolną), wybierz tryb (TEST/PROD), wgraj certyfikat .p12 (opcjonalnie — wymagane
        przy wysyłce w Phase 3), opcjonalnie zmień format numeracji (domyślnie <code>FV/{seq}/{year}</code>).
    </li>
    <li>
        <strong>Wystaw fakturę</strong> przez <em>Finanse → Faktury sprzedaży → + Nowa faktura</em>:
        <ul>
            <li>Wybierz członka klubu (auto-uzupełnia nazwę/email/adres) lub wpisz nabywcę ręcznie.</li>
            <li>Dodaj pozycje (przycisk „+ Dodaj pozycję"). Każda pozycja: opis, ilość, jednostka, cena netto, stawka VAT.</li>
            <li>Stawki VAT: <code>23%</code>, <code>8%</code>, <code>5%</code>, <code>0%</code>, <code>ZW</code> (zwolniona art. 43), <code>NP</code> (niepodlegająca).</li>
            <li>Opcjonalne pola: <code>PKWiU</code> (Polska Klasyfikacja Wyrobów i Usług), <code>GTU</code> (kod JPK_V7).</li>
            <li>Sumy <em>netto / VAT / brutto</em> liczone automatycznie w przeglądarce + przeliczane na serwerze przy zapisie.</li>
        </ul>
    </li>
    <li>
        <strong>Zapis szkicu</strong> — faktura zostaje w stanie <code>draft</code> z tymczasowym numerem <code>DRAFT-xxxxx</code>.
        Możesz wracać i edytować dowolnie.
    </li>
    <li>
        <strong>Wystawienie</strong> — przycisk „Wystaw fakturę" przypisuje ostateczny numer FV (atomowo,
        z licznika per-klub per-rok) i blokuje edycję. Status zmienia się na <code>issued</code>.
    </li>
    <li>
        <strong>PDF</strong> — można pobrać w każdej chwili. Format zawiera dane sprzedawcy, nabywcy,
        pozycje, sumy VAT i kwotę słownie.
    </li>
    <li>
        <strong>Podgląd XML KSeF</strong> — przycisk „Podgląd XML KSeF" generuje plik zgodny ze
        schematem <code>FA(2)</code> (xmlns <code>http://crd.gov.pl/wzor/2023/06/29/12648/</code>).
        Możesz go pobrać, otworzyć w edytorze, zwalidować przez <code>xmllint --schema fa2.xsd</code>
        i wgrać ręcznie do panelu KSeF do czasu uruchomienia automatycznej wysyłki.
    </li>
</ol>

<h2>Faktura z istniejącej płatności</h2>
<p>Jeśli członek już zapłacił składkę, możesz szybko wystawić fakturę:</p>
<ul>
    <li><em>Faktury sprzedaży → Wystaw z płatności</em> — lista wszystkich płatności bez faktury, z multi-selectem.</li>
    <li>Kwota płatności jest traktowana jako brutto; netto/VAT obliczane przy stawce 23%.</li>
    <li>Każda płatność może mieć tylko jedną aktywną fakturę (duplikaty są blokowane).</li>
</ul>

<h2>Numeracja</h2>
<p>Numer faktury jest nadawany <strong>atomowo</strong> z osobnego licznika per-klub per-rok:</p>
<ul>
    <li>Format konfigurowalny: domyślnie <code>FV/{seq}/{year}</code> → <code>FV/1/2026</code>, <code>FV/2/2026</code>...</li>
    <li>Placeholdery: <code>{seq}</code> (wymagany), <code>{year}</code>, <code>{month}</code>.</li>
    <li>Numer jest unikalny w obrębie klubu (UNIQUE w bazie) — nie da się go duplikować nawet przy równoległym kliknięciu.</li>
    <li>Zmiana formatu działa od następnego wystawienia — wcześniejsze numery pozostają.</li>
</ul>

<h2>Status i anulowanie</h2>
<ul>
    <li><span class="badge bg-secondary">Szkic</span> — można edytować i kasować/anulować.</li>
    <li><span class="badge bg-primary">Wystawiona</span> — numer FV przypisany, edycja zablokowana. Można anulować.</li>
    <li><span class="badge bg-info">Wysłana do KSeF</span> — Phase 3.</li>
    <li><span class="badge bg-success">Zaakceptowana</span> — KSeF zwrócił numer referencyjny + UPO.</li>
    <li><span class="badge bg-danger">Odrzucona</span> — błąd walidacji KSeF, sprawdź XML.</li>
    <li><span class="badge bg-dark">Anulowana</span> — faktura unieważniona (nie ma trafienia do KSeF).</li>
</ul>
<p><strong>Po wysłaniu do KSeF</strong> nie można już anulować — wymagana będzie faktura korygująca (Phase 3).</p>

<h2>Co dalej (roadmap KSeF)</h2>
<ul>
    <li><strong>Phase 3</strong>: podpis XAdES certyfikatem klubu, wysyłka XML, odbiór UPO, kolejka retry.</li>
    <li><strong>Phase 4</strong>: pull faktur zakupowych (otrzymane), eksport JPK_FA dla księgowej.</li>
</ul>

<div class="alert alert-warning small">
    <strong>Bezpieczeństwo:</strong> Faktury są ściśle izolowane per-klub. Każde zapytanie filtruje po <code>club_id</code>
    z aktywnego kontekstu sesji — IDOR jest niemożliwy nawet przy odgadnięciu ID.
</div>
