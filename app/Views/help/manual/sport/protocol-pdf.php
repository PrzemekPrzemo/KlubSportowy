<?php /** sport / protocol-pdf */ ?>
<p class="lead">Protokół turniejowy PDF to oficjalny dokument podsumowujący przebieg turnieju — listę uczestników, harmonogram, wyniki wszystkich meczów, klasyfikację końcową, listę sędziów. Wymagany przez federacje sportowe do zaliczenia turnieju do kalendarza punktowanego.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Po zakończeniu turnieju przejdź do widoku turnieju → zakładka <strong>Protokół</strong>.</li>
    <li>Wybierz szablon: <em>Federacja PZPN</em>, <em>Federacja PZLA</em>, <em>Klubowy standard</em>, <em>Minimalistyczny</em>.</li>
    <li>Sprawdź podgląd PDF — system wypełnia dane automatycznie z bazy.</li>
    <li>Opcjonalnie dodaj komentarz przewodniczącego komisji i podpisy delegatów.</li>
    <li>Kliknij <strong>Wygeneruj PDF</strong>. Plik zostanie zapisany w archiwum turnieju i wysłany do federacji (jeśli włączona integracja).</li>
</ol>

<div class="manual-callout manual-callout-tip">
    <strong><i class="bi bi-lightbulb"></i> Podpis cyfrowy.</strong> Włącz w ustawieniach klubu opcję <em>e-podpis</em> aby protokoły były automatycznie podpisywane kwalifikowanym podpisem klubu (wymaga konfiguracji w <em>Ustawienia → e-podpis</em>).
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>W jakim formacie protokół?</summary>
        <div class="faq-body">PDF/A — wersja archiwalna, zgodna z wymaganiami federacji i archiwów państwowych.</div>
    </details>
    <details>
        <summary>Czy mogę edytować PDF po wygenerowaniu?</summary>
        <div class="faq-body">Nie — PDF jest niemutowalny. Zmiana wymaga generowania nowej wersji (z numerem korekty).</div>
    </details>
    <details>
        <summary>Czy federacja dostaje protokół automatycznie?</summary>
        <div class="faq-body">Tak, jeśli skonfigurujesz integrację w <em>Klub → Federacje</em>. W przeciwnym razie musisz wysłać ręcznie.</div>
    </details>
</div>
