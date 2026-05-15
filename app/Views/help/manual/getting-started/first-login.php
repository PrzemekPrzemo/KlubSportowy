<?php /** Pierwsze logowanie i ustawienia konta */ ?>
<p class="lead">Po założeniu konta klubu otrzymasz e-mail z linkiem aktywacyjnym i danymi do logowania. W tej sekcji przeprowadzimy Cię przez ustawienie hasła, włączenie dwuskładnikowego uwierzytelniania (2FA) oraz dostosowanie profilu administratora.</p>

<h2>Krok po kroku</h2>
<ol>
    <li>Otwórz wiadomość e-mail od <code>noreply@clubdesk.pl</code> i kliknij przycisk <strong>Aktywuj konto</strong> (link ważny 24h).</li>
    <li>Ustaw hasło zgodne z polityką klubu — minimum 10 znaków, w tym mała litera, wielka litera, cyfra i znak specjalny.</li>
    <li>Po zalogowaniu trafisz na ekran powitalny — kliknij <em>Skonfiguruj klub</em>, aby przejść kreator wdrożenia.</li>
    <li>Wejdź w <strong>Ustawienia → Mój profil</strong> i uzupełnij imię, nazwisko, telefon kontaktowy oraz zdjęcie profilowe.</li>
    <li>Przejdź do zakładki <strong>Bezpieczeństwo</strong> i włącz 2FA (TOTP — Google Authenticator, Authy, 1Password lub Bitwarden).</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span></span><span></span><span></span></span>app.clubdesk.pl/login</div>
    <div class="manual-mockup-content">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card p-4">
                    <h5 class="mb-3 text-center">Zaloguj się do ClubDesk</h5>
                    <div class="mb-3"><label class="form-label small">E-mail</label><input class="form-control" value="admin@klub-sportowy.pl"></div>
                    <div class="mb-3"><label class="form-label small">Hasło</label><input class="form-control" type="password" value="••••••••••••"></div>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" checked><label class="form-check-label small">Zapamiętaj urządzenie 30 dni</label></div>
                    <button class="btn btn-primary w-100">Zaloguj się</button>
                    <div class="text-center mt-2"><a href="#" class="small">Nie pamiętam hasła</a></div>
                </div>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Ekran logowania — adres URL klubu może mieć też formę subdomeny, np. <code>nazwaklubu.clubdesk.pl</code>.</div>
</div>

<h2>Włączenie 2FA</h2>
<p>Dwuskładnikowe uwierzytelnianie jest <strong>wymagane</strong> dla administratorów klubu w planach Pro i Enterprise, a zalecane we wszystkich pozostałych. Po przejściu do zakładki <em>Bezpieczeństwo → Dodaj 2FA</em> system wyświetli kod QR — zeskanuj go aplikacją autentykatora, a następnie wpisz 6-cyfrowy kod, aby potwierdzić sparowanie.</p>

<div class="manual-callout manual-callout-warn">
    <strong>Zapisz kody zapasowe.</strong> Po włączeniu 2FA system pokaże 10 jednorazowych kodów odzyskiwania — zachowaj je w bezpiecznym menedżerze haseł. Bez nich w przypadku utraty telefonu konto będzie wymagało resetu przez support.
</div>

<h2>Profil i preferencje</h2>
<p>W sekcji <em>Ustawienia → Mój profil</em> uzupełnij dane kontaktowe — będą one widoczne dla pozostałych członków zarządu i pojawią się jako stopka w komunikacji wychodzącej (faktury, ogłoszenia). Polecamy także skonfigurować preferencje:</p>
<ul>
    <li><strong>Język interfejsu</strong> — polski lub angielski.</li>
    <li><strong>Strefa czasowa</strong> — <code>Europe/Warsaw</code> dla większości klubów.</li>
    <li><strong>Motyw</strong> — jasny / ciemny / auto.</li>
    <li><strong>Powiadomienia</strong> — które zdarzenia mają trafiać na e-mail, push, SMS.</li>
</ul>

<h2>Najczęstsze pytania</h2>
<div class="manual-faq">
    <details>
        <summary>Mój link aktywacyjny wygasł — co teraz?</summary>
        <div class="faq-body">Na ekranie logowania kliknij <em>Nie pamiętam hasła</em> i podaj swój e-mail. System wyśle nowy link bez konieczności kontaktu z supportem.</div>
    </details>
    <details>
        <summary>Czy mogę zmienić e-mail przypisany do konta?</summary>
        <div class="faq-body">Tak — w <em>Mój profil → Bezpieczeństwo → Zmiana e-maila</em>. Procedura wymaga potwierdzenia obu adresów (starego i nowego) tokenami wysłanymi mailowo.</div>
    </details>
    <details>
        <summary>Czy 2FA opóźnia logowanie?</summary>
        <div class="faq-body">Tylko o ok. 5 sekund. Jeśli zaznaczysz <em>Zapamiętaj urządzenie 30 dni</em>, kolejne logowania z tego samego urządzenia nie wymagają kodu.</div>
    </details>
</div>
