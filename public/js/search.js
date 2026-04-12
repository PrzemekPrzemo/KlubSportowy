/**
 * Global AJAX search bar — debounced, dropdown results.
 */
(function() {
    'use strict';
    var input = document.getElementById('global-search-input');
    var dropdown = document.getElementById('global-search-dropdown');
    if (!input || !dropdown) return;

    var timer = null;

    input.addEventListener('input', function() {
        clearTimeout(timer);
        var q = input.value.trim();
        if (q.length < 2) { dropdown.style.display = 'none'; return; }

        timer = setTimeout(function() {
            fetch('/api/search?q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var results = data.results || [];
                    if (results.length === 0) {
                        dropdown.innerHTML = '<div class="px-3 py-2 text-muted small">Brak wyników</div>';
                    } else {
                        dropdown.innerHTML = results.map(function(r) {
                            return '<a href="' + r.url + '" class="dropdown-item py-1">'
                                + '<i class="bi ' + r.icon + ' me-2"></i>'
                                + '<span>' + escapeHtml(r.label) + '</span>'
                                + '<small class="text-muted ms-2">' + r.type + '</small>'
                                + '</a>';
                        }).join('');
                    }
                    dropdown.style.display = 'block';
                })
                .catch(function() { dropdown.style.display = 'none'; });
        }, 300);
    });

    input.addEventListener('blur', function() {
        setTimeout(function() { dropdown.style.display = 'none'; }, 200);
    });
    input.addEventListener('focus', function() {
        if (dropdown.innerHTML && input.value.trim().length >= 2) dropdown.style.display = 'block';
    });

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
})();
