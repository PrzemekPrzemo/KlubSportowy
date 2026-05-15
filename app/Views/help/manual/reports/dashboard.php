<?php /** reports / dashboard */ ?>
<p class="lead">Dashboard administratora to ekran startowy po zalogowaniu — zawiera 12 widgetów z najważniejszymi KPI klubu w czasie rzeczywistym. Możesz dostosować widoczne widgety przez drag&drop.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Domyślne widgety: liczba członków, MRR (miesięczny przychód), liczba zaległości, % frekwencji, najbliższe turnieje, ostatnie ogłoszenia.</li>
    <li>Kliknij <em>Dostosuj dashboard</em> aby dodać/usunąć widgety lub zmienić rozmiar.</li>
    <li>Dostępne widgety: wykres przychodów (line), wykres członkostwa (area), pie chart kategorii wiekowych, lista najlepszych zawodników, mapa miejsc treningów.</li>
    <li>Wszystkie wykresy są interaktywne — kliknij segment aby przejść do filtrowanej listy.</li>
    <li>Eksport dashboardu do PDF — dla raportów zarządu.</li>
</ol>

<div class="manual-callout manual-callout-tip">
    <strong><i class="bi bi-lightbulb"></i> Refresh.</strong> Dashboard odświeża się co 5 minut. Wymuszenie odświeżenia: Ctrl+R lub przycisk <em>Odśwież</em> w prawym górnym rogu.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy mogę mieć dashboard per rola?</summary>
        <div class="faq-body">Tak — trener widzi inne widgety niż prezes. Konfiguracja w <em>Ustawienia → Role → Dashboard</em>.</div>
    </details>
    <details>
        <summary>Eksport do BI (Power BI, Tableau)?</summary>
        <div class="faq-body">Tak — przez API lub bezpośredni connector ODBC do read-only repliki bazy.</div>
    </details>
    <details>
        <summary>Co z mobile?</summary>
        <div class="faq-body">Dashboard responsywny — na telefonie widgety układają się pionowo.</div>
    </details>
</div>
