<?php use App\Helpers\View; ?>
<form method="POST" action="<?= url('club/customization/save') ?>" enctype="multipart/form-data" class="card p-4 mb-4">
    <?= csrf_field() ?>
    <h5 class="mb-3"><i class="bi bi-palette"></i> Branding podstawowy</h5>
    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label">Motto / hasło klubu</label>
            <input type="text" name="motto" value="<?= View::e($custom['motto'] ?? '') ?>" class="form-control">
        </div>
        <div class="col-md-4">
            <label class="form-label">Subdomena</label>
            <input type="text" name="subdomain" value="<?= View::e($custom['subdomain'] ?? '') ?>" class="form-control" placeholder="np. azs-warszawa">
            <small class="text-muted">np. <code>azs-warszawa.klubsportowy.pl</code></small>
        </div>
        <div class="col-md-12">
            <label class="form-label">Logo klubu (PNG, JPG, WebP, SVG)</label>
            <input type="file" name="logo" class="form-control" accept="image/*">
            <?php if (!empty($custom['logo_path'])): ?>
                <small class="text-muted d-block mt-2">
                    Aktualne: <code><?= View::e($custom['logo_path']) ?></code>
                </small>
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label">Kolor główny</label>
            <input type="color" name="primary_color" value="<?= View::e($custom['primary_color'] ?? '#0d6efd') ?>" class="form-control form-control-color">
        </div>
        <div class="col-md-4">
            <label class="form-label">Kolor sidebara</label>
            <input type="color" name="navbar_bg" value="<?= View::e($custom['navbar_bg'] ?? '#212529') ?>" class="form-control form-control-color">
        </div>
        <div class="col-md-4">
            <label class="form-label">Kolor akcentu</label>
            <input type="color" name="accent_color" value="<?= View::e($custom['accent_color'] ?? '#198754') ?>" class="form-control form-control-color">
        </div>
        <div class="col-12">
            <label class="form-label">Dodatkowe CSS (zaawansowane — niedostępne w tym formularzu)</label>
            <textarea name="custom_css" rows="4" class="form-control" style="font-family: monospace;" readonly><?= View::e($custom['custom_css'] ?? '') ?></textarea>
            <small class="text-muted">Użyj formularza "Wygląd zaawansowany" niżej, aby zmodyfikować custom CSS — tu pokazujemy tylko aktualną wartość.</small>
        </div>
    </div>
    <div class="mt-4">
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz branding podstawowy</button>
    </div>
</form>

<!-- Whitelabel: Wygląd zaawansowany (favicon + custom CSS) -->
<div class="card p-4 mb-4">
    <h5 class="mb-3"><i class="bi bi-gear-wide-connected"></i> Wygląd zaawansowany</h5>

    <!-- Favicon -->
    <h6 class="mt-2">Favicon klubu (PNG lub ICO, max 50 KB)</h6>
    <form method="POST" action="<?= url('club/customization/favicon') ?>" enctype="multipart/form-data" class="mb-3">
        <?= csrf_field() ?>
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <input type="file" name="favicon" class="form-control" accept=".png,.ico,image/png,image/x-icon,image/vnd.microsoft.icon">
                <?php if (!empty($custom['favicon_path'])): ?>
                    <small class="text-muted d-block mt-2">
                        Aktualne:
                        <img src="<?= url($custom['favicon_path']) ?>" alt="favicon" style="height:24px; vertical-align:middle;">
                        <code><?= View::e($custom['favicon_path']) ?></code>
                    </small>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-primary w-100"><i class="bi bi-upload"></i> Wgraj favicon</button>
            </div>
            <?php if (!empty($custom['favicon_path'])): ?>
                <div class="col-md-3">
                    <button type="submit" formaction="<?= url('club/customization/favicon/delete') ?>" class="btn btn-outline-danger w-100" formnovalidate>
                        <i class="bi bi-trash"></i> Usuń
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </form>

    <hr class="my-3">

    <!-- Custom CSS -->
    <h6>Custom CSS (max 50 KB)</h6>
    <small class="text-muted d-block mb-2">
        Sanitization: odrzucamy wstrzyknięcia <code>&lt;script&gt;</code>, <code>expression()</code>,
        <code>javascript:</code>, <code>@import</code>, <code>behavior:</code>. Renderowane w
        <code>&lt;head&gt;</code> po dynamicznych kolorach.
    </small>
    <form method="POST" action="<?= url('club/customization/css') ?>" class="mb-2">
        <?= csrf_field() ?>
        <textarea name="custom_css" rows="10" class="form-control"
                  style="font-family: monospace; font-size: 13px;"
                  placeholder=".sidebar .brand h5 { font-style: italic; }"><?= View::e($custom['custom_css'] ?? '') ?></textarea>
        <?php if (!empty($custom['custom_css_updated_at'])): ?>
            <small class="text-muted d-block mt-1">Ostatnia zmiana: <?= View::e($custom['custom_css_updated_at']) ?></small>
        <?php endif; ?>
        <div class="mt-3">
            <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz custom CSS</button>
        </div>
    </form>
</div>

<!-- Whitelabel: Komunikacja (email/SMS) -->
<div class="card p-4 mb-4">
    <h5 class="mb-3"><i class="bi bi-megaphone"></i> Komunikacja (email/SMS)</h5>

    <form method="POST" action="<?= url('club/customization/communication') ?>" class="mb-4">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-7">
                <label class="form-label">Nazwa nadawcy email (From display name)</label>
                <input type="text" name="email_from_name" maxlength="120"
                       value="<?= View::e($custom['email_from_name'] ?? '') ?>"
                       class="form-control" placeholder="np. Klub Sportowy Warszawa">
                <small class="text-muted">Pokazywane jako "Nazwa &lt;noreply@...&gt;". Max 120 znaków.</small>
            </div>
            <div class="col-md-5">
                <label class="form-label">SMS sender ID (alphanum, 1-11 znaków)</label>
                <input type="text" name="sms_sender_id" maxlength="11"
                       value="<?= View::e($custom['sms_sender_id'] ?? '') ?>"
                       class="form-control text-uppercase" pattern="[A-Za-z0-9]{1,11}"
                       placeholder="np. KSWARSZAWA">
                <small class="text-muted">A-Z i 0-9. SMSAPI/Twilio: nadawca alphanum (Twilio: wymaga approval).</small>
            </div>
        </div>
        <div class="mt-3">
            <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz ustawienia komunikacji</button>
        </div>
    </form>

    <hr>

    <h6>Email header (HTML, max 5000 znaków)</h6>
    <small class="text-muted d-block mb-2">
        Wstawiany jako górny nagłówek wszystkich emaili wysyłanych przez ClubDesk.
        Dozwolone tagi: <code>a, img, p, div, span, strong, em, h1-h6, br, hr, table, tr, td, th</code>.
        Bez <code>&lt;script&gt;</code>, <code>&lt;style&gt;</code>, <code>&lt;iframe&gt;</code>.
        Pusty = automatyczny default (logo + nazwa klubu na tle koloru głównego).
    </small>
    <form method="POST" action="<?= url('club/customization/email-header') ?>">
        <?= csrf_field() ?>
        <textarea name="email_header_html" rows="6" maxlength="5000"
                  class="form-control" style="font-family: monospace; font-size: 13px;"
                  placeholder='<div style="background:#EE2C28; padding:16px; color:#fff;"><strong>Mój Klub</strong></div>'><?= View::e($custom['email_header_html'] ?? '') ?></textarea>
        <div class="mt-3">
            <button class="btn btn-primary"><i class="bi bi-check2"></i> Zapisz email header</button>
        </div>
    </form>
</div>
