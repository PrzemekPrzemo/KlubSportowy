/**
 * Z.2 — Recent items tracker.
 *
 * Tracks last 5 visited "trackable" URLs (members, trainings, events) w
 * localStorage per browser. Renderuje listę w sidebarze pod sekcją 'club'.
 *
 * Storage key: clubdesk_recent (JSON array of {url, label, icon, ts})
 * Max items: 5 (FIFO oldest dropped)
 *
 * Trackable URLs (regex):
 *   /members/:id       → "Imię Nazwisko"  → bi-person
 *   /trainings/:id     → tytuł treningu   → bi-stopwatch
 *   /events/:id        → nazwa wydarzenia → bi-calendar-event
 */
(function() {
    'use strict';
    var STORAGE_KEY = 'clubdesk_recent';
    var MAX_ITEMS   = 5;

    function getRecent() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            return [];
        }
    }

    function saveRecent(items) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(items.slice(0, MAX_ITEMS)));
        } catch (e) { /* localStorage full / disabled */ }
    }

    function trackCurrentPage() {
        var path = window.location.pathname;
        // Wyciągnij prefix bez basePath (jeśli aplikacja jest w subfolder)
        var match = null;
        var icon = '', label = '', type = '';

        // /members/123 ale NIE /members/123/edit (zostawiamy detail-view tylko)
        if ((match = path.match(/\/members\/(\d+)(?:\?.*)?$/))) {
            type = 'member'; icon = 'bi-person';
            // Pobierz label ze strony (nagłówek <h2> lub title)
            var h2 = document.querySelector('h2');
            label = h2 ? h2.textContent.trim() : 'Zawodnik #' + match[1];
        } else if ((match = path.match(/\/trainings\/(\d+)(?:\?.*)?$/))) {
            type = 'training'; icon = 'bi-stopwatch';
            label = (document.querySelector('h2, h3') || {}).textContent || ('Trening #' + match[1]);
        } else if ((match = path.match(/\/events\/(\d+)(?:\?.*)?$/))) {
            type = 'event'; icon = 'bi-calendar-event';
            label = (document.querySelector('h2, h3') || {}).textContent || ('Wydarzenie #' + match[1]);
        } else {
            return; // not trackable
        }

        // Trim label (no długie tytuły)
        label = label.replace(/\s+/g, ' ').trim().substring(0, 50);

        var items = getRecent();
        // Usuń duplikat tego samego URL
        items = items.filter(function(it) { return it.url !== path; });
        items.unshift({
            url:   path,
            label: label,
            icon:  icon,
            type:  type,
            ts:    Date.now(),
        });
        saveRecent(items);
    }

    function renderSidebar() {
        var container = document.getElementById('recent-items-list');
        if (!container) return;
        var items = getRecent();
        if (items.length === 0) {
            container.style.display = 'none';
            return;
        }

        var html = items.map(function(it) {
            return '<a href="' + it.url + '" title="' + escapeAttr(it.label) + '">'
                + '<i class="bi ' + it.icon + '"></i> '
                + '<span style="font-size: .85rem;">' + escapeHtml(it.label) + '</span>'
                + '</a>';
        }).join('');
        container.innerHTML = html;
        container.style.display = 'block';
    }

    function escapeHtml(s) {
        var d = document.createElement('div'); d.textContent = s; return d.innerHTML;
    }
    function escapeAttr(s) {
        return String(s).replace(/"/g, '&quot;');
    }

    // Track this page once DOM ready (label needs h2 to exist)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            trackCurrentPage();
            renderSidebar();
        });
    } else {
        trackCurrentPage();
        renderSidebar();
    }
})();
