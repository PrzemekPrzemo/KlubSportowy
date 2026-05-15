<?php /** security / mfa */ ?>
<p class="lead">Dwuskładnikowe uwierzytelnianie (2FA / MFA) to dodatkowa warstwa ochrony konta administratora — oprócz hasła wymagany jest jednorazowy 6-cyfrowy kod z aplikacji autentykatora. Dla administratorów klubu jest <strong>wymagane</strong> w planach Pro+.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Pobierz aplikację autentykatora: Google Authenticator, Authy, 1Password, Bitwarden, Microsoft Authenticator.</li>
    <li>W ClubDesk wejdź w <strong>Mój profil → Bezpieczeństwo → Dodaj 2FA</strong>.</li>
    <li>Zeskanuj wyświetlony kod QR aplikacją.</li>
    <li>Wpisz pierwszy 6-cyfrowy kod aby potwierdzić sparowanie.</li>
    <li>Zapisz 10 jednorazowych kodów odzyskiwania — w bezpiecznym managerze haseł.</li>
    <li>Od następnego logowania system będzie pytał o kod 2FA.</li>
</ol>

<div class="manual-callout manual-callout-danger">
    <strong><i class="bi bi-shield-exclamation"></i> Utrata urządzenia.</strong> Jeśli stracisz telefon z autentykatorem, użyj kodu odzyskiwania. Jeśli i to zgubisz — wymagany kontakt z supportem ClubDesk z dokumentem potwierdzającym tożsamość.
</div>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Czy 2FA opóźnia logowanie?</summary>
        <div class="faq-body">O ~5 sekund. Możesz włączyć <em>Zapamiętaj urządzenie 30 dni</em> aby kolejne logowania z tego samego urządzenia nie wymagały kodu.</div>
    </details>
    <details>
        <summary>Czy mogę używać SMS?</summary>
        <div class="faq-body">TOTP (aplikacja) jest bezpieczniejsze niż SMS i preferowane. SMS dostępne tylko jako fallback w Enterprise.</div>
    </details>
    <details>
        <summary>Co z hardware key (YubiKey)?</summary>
        <div class="faq-body">Tak — wsparcie dla WebAuthn / FIDO2 / YubiKey w planach Pro+. Konfiguracja w <em>Bezpieczeństwo → Klucze sprzętowe</em>.</div>
    </details>
</div>
