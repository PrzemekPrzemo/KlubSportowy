/**
 * Dark mode toggle — persists in localStorage.
 */
(function() {
    'use strict';
    var KEY = 'ks_theme';
    var html = document.documentElement;
    var saved = localStorage.getItem(KEY);

    // Apply saved theme on load
    if (saved) {
        html.setAttribute('data-theme', saved);
    }

    // Toggle button
    var btn = document.getElementById('dark-mode-toggle');
    if (!btn) return;

    updateIcon();

    btn.addEventListener('click', function() {
        var current = html.getAttribute('data-theme');
        var next = current === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', next);
        localStorage.setItem(KEY, next);
        updateIcon();
    });

    function updateIcon() {
        var isDark = html.getAttribute('data-theme') === 'dark';
        btn.innerHTML = isDark
            ? '<i class="bi bi-sun"></i>'
            : '<i class="bi bi-moon"></i>';
        btn.title = isDark ? 'Tryb jasny' : 'Tryb ciemny';
    }
})();
