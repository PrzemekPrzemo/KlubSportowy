<?php
$page = [
    'title'        => 'Zdjęcie profilowe i dokumenty',
    'category'     => 'Zawodnik',
    'group'        => 'Mój profil',
    'last_updated' => '2026-05-15',
    'reading_time' => '3 min',
];
include __DIR__ . '/../_layout_manual.php';
?>
<h1>Zdjęcie profilowe i dokumenty</h1>
<p class="lead">Zdjęcie sprawia, że Twój profil staje się rozpoznawalny — trener łatwiej odnajdzie Cię na liście, a Ty zobaczysz swoją podobiznę na karcie członkowskiej. Dokumenty (np. badania lekarskie, oświadczenia rodziców) możesz wgrać raz i mieć z głowy.</p>

<h2>Wgrywanie zdjęcia</h2>
<ol>
    <li><span class="manual-step-num">1</span>Wejdź w <em>Profil</em>.</li>
    <li><span class="manual-step-num">2</span>Kliknij w pole ze zdjęciem (na górze ekranu) lub przycisk <strong>Zmień zdjęcie</strong>.</li>
    <li><span class="manual-step-num">3</span>Wybierz plik z dysku albo zrób zdjęcie aparatem (na telefonie).</li>
    <li><span class="manual-step-num">4</span>Przytnij obraz w kółku — żeby twarz była na środku.</li>
    <li><span class="manual-step-num">5</span>Kliknij <strong>Zapisz</strong>. Zdjęcie pojawi się od razu.</li>
</ol>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/profile</div>
    <div class="manual-mockup-content">
        <div class="d-flex align-items-center gap-3">
            <div style="width:96px; height:96px; border-radius:50%; background:#dee2e6; display:flex; align-items:center; justify-content:center; color:#777; position:relative;">
                <i class="bi bi-person" style="font-size:3rem;"></i>
                <span class="position-absolute" style="bottom:0; right:0; background:#EE2C28; color:#fff; border-radius:50%; width:30px; height:30px; display:flex; align-items:center; justify-content:center;">
                    <i class="bi bi-camera"></i>
                </span>
            </div>
            <div>
                <h6 class="mb-1">Anna Kowalska</h6>
                <small class="text-muted">Pływanie · UKS Iskra</small>
                <div class="mt-2">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-upload"></i> Wgraj zdjęcie</button>
                    <button class="btn btn-sm btn-outline-secondary">Usuń</button>
                </div>
            </div>
        </div>
    </div>
    <div class="manual-mockup-caption">Wystarczy kliknąć w awatara, żeby wgrać własne zdjęcie.</div>
</div>

<div class="manual-tip">
    <strong>Wymagania.</strong> Najlepsze efekty daje zdjęcie kwadratowe, jasne, z dobrze widoczną twarzą. Dozwolone formaty: JPG, PNG. Maksymalny rozmiar: 5 MB.
</div>

<h2>Dokumenty — wgrywanie</h2>
<p>Klub może poprosić Cię o kilka dokumentów: zgodę rodzica (jeśli jesteś niepełnoletni), aktualne badania lekarskie, skan ID itp. W sekcji <em>Profil → Dokumenty</em> widzisz listę z statusem.</p>

<div class="manual-mockup">
    <div class="manual-mockup-toolbar"><span class="dots"><span class="r"></span><span class="y"></span><span class="g"></span></span>app.clubdesk.pl/portal/profile (sekcja Dokumenty)</div>
    <div class="manual-mockup-content">
        <table class="table table-sm mb-0">
            <thead><tr><th>Dokument</th><th>Status</th><th>Ważny do</th><th></th></tr></thead>
            <tbody>
                <tr><td>Zgoda RODO</td><td><span class="badge bg-success">Aktywna</span></td><td>—</td><td><button class="btn btn-sm btn-link">Pobierz</button></td></tr>
                <tr><td>Badania lekarskie</td><td><span class="badge bg-warning text-dark">Wygasa za 12 dni</span></td><td>27.05.2026</td><td><button class="btn btn-sm btn-primary">Wgraj nowe</button></td></tr>
                <tr><td>Oświadczenie rodzica</td><td><span class="badge bg-danger">Brak</span></td><td>—</td><td><button class="btn btn-sm btn-primary">Wgraj</button></td></tr>
            </tbody>
        </table>
    </div>
    <div class="manual-mockup-caption">Lista dokumentów z kolorowym statusem — od razu wiesz, co trzeba uzupełnić.</div>
</div>

<h2>Co oznaczają kolory statusu</h2>
<ul>
    <li><span class="badge bg-success">Aktywna</span> — wszystko OK.</li>
    <li><span class="badge bg-warning text-dark">Wygasa wkrótce</span> — masz mniej niż 30 dni, czas pomyśleć o aktualizacji.</li>
    <li><span class="badge bg-danger">Brak / wygasły</span> — trzeba pilnie wgrać; możesz zostać niedopuszczony do zawodów.</li>
</ul>

<h2>Najczęstsze pytania</h2>
<details>
    <summary>Mogę wgrać zdjęcie z telefonu?</summary>
    <p>Tak. Na telefonie, po kliknięciu Wgraj, system zapyta czy chcesz wybrać plik z galerii czy zrobić nowe zdjęcie aparatem.</p>
</details>
<details>
    <summary>Jakie pliki przyjmuje system dla dokumentów?</summary>
    <p>PDF, JPG, PNG. Maksymalnie 10 MB. Jeśli masz papierowy dokument — zrób mu zdjęcie albo zeskanuj.</p>
</details>
<details>
    <summary>Kto widzi moje dokumenty?</summary>
    <p>Tylko Ty, trener i administracja klubu. Dokumenty nie są publiczne. Pełna polityka prywatności jest w sekcji RODO.</p>
</details>
