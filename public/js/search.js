/**
 * Global AJAX search bar — debounced, dropdown z keyboard navigation.
 *
 * Z.1 enhancements:
 *  - Ctrl+K (Cmd+K na Mac) fokusuje pole z anywhere in app
 *  - Arrow up/down nawiguje po wynikach
 *  - Enter otwiera podświetlony wynik
 *  - Esc zamyka dropdown
 */
(function() {
    'use strict';
    var input = document.getElementById('global-search-input');
    var dropdown = document.getElementById('global-search-dropdown');
    if (!input || !dropdown) return;

    // Z.1: wzbogać placeholder o hint o skrócie
    if (!input.placeholder.includes('Ctrl')) {
        input.placeholder = (input.placeholder || 'Szukaj') + ' (Ctrl+K)';
    }

    var timer = null;
    var selectedIdx = -1;

    function getItems() {
        return Array.prototype.slice.call(dropdown.querySelectorAll('.dropdown-item'));
    }

    function highlight(idx) {
        var items = getItems();
        items.forEach(function(el, i) {
            if (i === idx) {
                el.classList.add('active');
                el.style.background = '#f1f5f9';
            } else {
                el.classList.remove('active');
                el.style.background = '';
            }
        });
        selectedIdx = idx;
    }

    function performSearch(q) {
        fetch('/api/search?q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var results = data.results || [];
                if (results.length === 0) {
                    dropdown.innerHTML = '<div class="px-3 py-2 text-muted small">Brak wyników</div>';
                } else {
                    dropdown.innerHTML = results.map(function(r) {
                        return '<a href="' + r.url + '" class="dropdown-item py-1 d-flex align-items-center">'
                            + '<i class="bi ' + r.icon + ' me-2"></i>'
                            + '<span class="flex-grow-1">' + escapeHtml(r.label) + '</span>'
                            + '<small class="text-muted ms-2">' + r.type + '</small>'
                            + '</a>';
                    }).join('');
                }
                dropdown.style.display = 'block';
                selectedIdx = -1;
            })
            .catch(function() { dropdown.style.display = 'none'; });
    }

    input.addEventListener('input', function() {
        clearTimeout(timer);
        var q = input.value.trim();
        if (q.length < 2) { dropdown.style.display = 'none'; return; }
        timer = setTimeout(function() { performSearch(q); }, 300);
    });

    input.addEventListener('keydown', function(e) {
        var items = getItems();
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (items.length > 0) highlight(Math.min(selectedIdx + 1, items.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (items.length > 0) highlight(Math.max(selectedIdx - 1, 0));
        } else if (e.key === 'Enter') {
            if (selectedIdx >= 0 && items[selectedIdx]) {
                e.preventDefault();
                window.location.href = items[selectedIdx].href;
            }
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
            input.blur();
        }
    });

    input.addEventListener('blur', function() {
        // setTimeout aby kliknięcie w wynik zdążyło zadziałać
        setTimeout(function() { dropdown.style.display = 'none'; }, 200);
    });
    input.addEventListener('focus', function() {
        if (dropdown.innerHTML && input.value.trim().length >= 2) dropdown.style.display = 'block';
    });

    // Z.1: Ctrl+K / Cmd+K → focus search z anywhere
    document.addEventListener('keydown', function(e) {
        var isMac = /Mac/i.test(navigator.platform);
        var modKey = isMac ? e.metaKey : e.ctrlKey;
        if (modKey && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            input.focus();
            input.select();
        }
    });

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }
})();
