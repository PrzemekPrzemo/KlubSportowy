<?php /** members / documents */ ?>
<p class="lead">Każdy członek ma w profilu zakładkę <em>Dokumenty</em> z możliwością wgrywania umów, oświadczeń, zaświadczeń medycznych, zgód RODO i innych plików. ClubDesk pełni rolę cyfrowego archiwum z kontrolą wersji i logiem dostępu.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Wejdź w profil członka i przejdź do zakładki <strong>Dokumenty</strong>.</li>
    <li>Kliknij <em>+ Dodaj dokument</em> i wybierz typ z listy: umowa zawodnicza, oświadczenie RODO, zgoda na wizerunek, zaświadczenie medyczne, ubezpieczenie, regulamin podpisany, inny.</li>
    <li>Wgraj plik PDF, JPG, PNG (do 10 MB) lub wygeneruj z szablonu klubu.</li>
    <li>Ustaw datę ważności (jeśli dotyczy) — system przypomni 30/14/7 dni przed wygaśnięciem.</li>
    <li>Opcjonalnie dodaj notatkę dla siebie i zarządu (członek jej nie zobaczy).</li>
    <li>Kliknij <strong>Zapisz</strong>. Dokument jest natychmiast dostępny w portalu członka (jeśli ma uprawnienie do podglądu).</li>
</ol>

<div class="manual-callout manual-callout-danger">
    <strong><i class="bi bi-shield-exclamation"></i> RODO i wrażliwe dokumenty.</strong> Zaświadczenia medyczne i dokumenty z danymi szczególnej kategorii (art. 9 RODO) wgrywaj wyłącznie z odpowiednim typem — system stosuje wówczas szyfrowanie at-rest AES-256 i pełny audit log dostępu.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Kto widzi dokumenty członka?</summary>
        <div class="faq-body">Domyślnie: sam członek (poprzez portal) i administratorzy klubu. Trenerzy widzą jedynie typ i datę ważności (bez podglądu treści). Lekarz klubowy widzi dokumenty medyczne.</div>
    </details>
    <details>
        <summary>Czy mogę masowo wgrać dokumenty dla wielu osób?</summary>
        <div class="faq-body">Tak — w sekcji <em>Operacje grupowe → Wgraj dokument</em>. System dopasuje pliki do członków po nazwie (format: <code>nazwisko_imie.pdf</code>).</div>
    </details>
    <details>
        <summary>Co się dzieje po anonimizacji członka?</summary>
        <div class="faq-body">Wszystkie dokumenty są bezpowrotnie usuwane, a w audit logu pojawia się wpis <code>documents.purged</code>.</div>
    </details>
</div>
