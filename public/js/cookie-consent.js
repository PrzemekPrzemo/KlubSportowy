(function() {
    'use strict';

    if (localStorage.getItem('cookie_consent') === 'accepted') {
        return;
    }

    var banner = document.createElement('div');
    banner.id = 'cookie-consent-banner';
    banner.style.cssText = 'position:fixed;bottom:0;left:0;right:0;z-index:9999;'
        + 'background:#212529;color:#f8f9fa;padding:1rem 1.5rem;'
        + 'display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;'
        + 'box-shadow:0 -2px 10px rgba(0,0,0,.3);font-size:0.9rem;';

    var text = document.createElement('span');
    text.textContent = 'Ta strona wykorzystuje pliki cookies w celu zapewnienia prawidlowego '
        + 'dzialania serwisu oraz analizy ruchu. Korzystajac z serwisu, zgadzasz sie na ich uzycie. ';

    var linkPrivacy = document.createElement('a');
    linkPrivacy.href = '/privacy';
    linkPrivacy.textContent = 'Polityka prywatnosci';
    linkPrivacy.style.cssText = 'color:#0dcaf0;text-decoration:underline;';
    text.appendChild(linkPrivacy);

    var btn = document.createElement('button');
    btn.textContent = 'Akceptuje';
    btn.style.cssText = 'background:#0d6efd;color:#fff;border:none;padding:0.5rem 1.5rem;'
        + 'border-radius:6px;cursor:pointer;font-size:0.9rem;white-space:nowrap;';

    btn.addEventListener('click', function() {
        localStorage.setItem('cookie_consent', 'accepted');
        banner.parentNode.removeChild(banner);
    });

    banner.appendChild(text);
    banner.appendChild(btn);
    document.body.appendChild(banner);
})();
