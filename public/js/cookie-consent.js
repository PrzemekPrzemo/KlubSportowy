(function() {
    'use strict';

    if (localStorage.getItem('cookie_consent') === 'accepted') {
        return;
    }

    var banner = document.createElement('div');
    banner.id = 'cookie-consent-banner';
    banner.style.cssText = 'position:fixed;bottom:0;left:0;right:0;z-index:9999;'
        + 'background:#212529;color:#f8f9fa;padding:1rem 1.5rem;'
        + 'display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;'
        + 'box-shadow:0 -2px 10px rgba(0,0,0,.3);font-size:0.9rem;';

    var text = document.createElement('span');
    text.style.cssText = 'max-width:780px;line-height:1.5;';
    text.textContent = 'Ta strona wykorzystuje pliki cookies w celu zapewnienia prawidłowego '
        + 'działania serwisu, analizy ruchu oraz personalizacji treści. Korzystając z serwisu, '
        + 'zgadzasz się na ich użycie. Szczegóły: ';

    var linkCookies = document.createElement('a');
    linkCookies.href = '/legal/cookies';
    linkCookies.textContent = 'Polityka cookies';
    linkCookies.style.cssText = 'color:#0dcaf0;text-decoration:underline;margin-right:.25rem;';
    text.appendChild(linkCookies);

    var sep = document.createElement('span');
    sep.textContent = ' · ';
    text.appendChild(sep);

    var linkPrivacy = document.createElement('a');
    linkPrivacy.href = '/legal/polityka-prywatnosci';
    linkPrivacy.textContent = 'Polityka prywatności';
    linkPrivacy.style.cssText = 'color:#0dcaf0;text-decoration:underline;';
    text.appendChild(linkPrivacy);

    var actions = document.createElement('div');
    actions.style.cssText = 'display:flex;gap:.5rem;flex-wrap:wrap;';

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = 'Akceptuję';
    btn.style.cssText = 'background:#0d6efd;color:#fff;border:none;padding:0.5rem 1.5rem;'
        + 'border-radius:6px;cursor:pointer;font-size:0.9rem;white-space:nowrap;font-weight:500;';

    btn.addEventListener('click', function() {
        localStorage.setItem('cookie_consent', 'accepted');
        // Set persistent cookie too (consistent with PHP-side check).
        try {
            document.cookie = 'cookie_consent=accepted; Max-Age=' + (12 * 30 * 86400) +
                              '; Path=/; SameSite=Lax';
        } catch (e) {}
        // Best-effort: record acceptance in legal_acceptances audit log.
        try {
            fetch('/legal/accept-cookies', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            }).catch(function() { /* ignore */ });
        } catch (e) { /* ignore */ }
        if (banner.parentNode) banner.parentNode.removeChild(banner);
    });

    actions.appendChild(btn);

    banner.appendChild(text);
    banner.appendChild(actions);

    if (document.body) {
        document.body.appendChild(banner);
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            document.body.appendChild(banner);
        });
    }
})();
