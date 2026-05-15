<?php /** members / anonymize */ ?>
<p class="lead">Anonimizacja realizuje prawo do bycia zapomnianym (art. 17 RODO). Operacja zastępuje dane osobowe członka pseudonimami i hashami, zachowując jednak statystyki sportowe i finansowe potrzebne do sprawozdawczości klubu.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Wejdź w profil członka → menu <em>⋯</em> → <strong>Anonimizuj</strong>.</li>
    <li>Zaakceptuj ostrzeżenie i podaj powód (wymagane do audit logu) — np. „Wniosek członka z 2026-04-12, art. 17 RODO".</li>
    <li>Wpisz hasło administratora dla potwierdzenia.</li>
    <li>System zastąpi: imię → <code>Zanonimizowany_42</code>, e-mail → <code>anon_42@deleted.local</code>, telefon → <code>***</code>, adres → null, PESEL → hash. Zdjęcia i dokumenty są usuwane.</li>
    <li>Statystyki sportowe (mecze, wyniki, frekwencja) pozostają, ale przypisane do pseudonimu.</li>
    <li>Po 30 dniach od anonimizacji w audit logu pozostaje tylko wpis <code>member.anonymized</code> z datą i osobą wykonującą.</li>
</ol>

<div class="manual-callout manual-callout-danger">
    <strong><i class="bi bi-shield-exclamation"></i> Operacja nieodwracalna.</strong> Anonimizacja jest <strong>trwała</strong> — nie można jej cofnąć. Upewnij się, że wszystkie zobowiązania finansowe zostały rozliczone i niezbędne dokumenty wyeksportowane.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy faktury historyczne też są anonimizowane?</summary>
        <div class="faq-body">Zgodnie z art. 17 ust. 3 RODO — nie, jeśli ich przechowywanie wymaga prawo (np. ustawa o rachunkowości — 5 lat). Po upływie tego okresu faktury są usuwane automatycznie.</div>
    </details>
    <details>
        <summary>Kto może wykonać anonimizację?</summary>
        <div class="faq-body">Wyłącznie administrator klubu z rolą <em>zarząd</em>. Trenerzy i księgowi nie mają tej opcji.</div>
    </details>
    <details>
        <summary>Czy członek musi wnioskować pisemnie?</summary>
        <div class="faq-body">Wniosek może być złożony e-mailem, ustnie (z protokołem) lub przez portal samoobsługowy GDPR. Każda forma wymaga udokumentowania w polu „Powód".</div>
    </details>
</div>
