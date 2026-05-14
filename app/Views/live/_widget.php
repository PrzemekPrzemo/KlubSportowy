<?php
use App\Helpers\View;
/**
 * Live score widget — partial.
 *
 * Wymaga zmiennych:
 *  - $channel (string)  np. "match:42"
 *  - $title   (string)  tytul do wyswietlenia
 *
 * JS uzywa EventSource z auto-reconnect (przegladarka robi to sama
 * po zerwaniu polaczenia, korzystajac z Last-Event-ID).
 */
$widgetId = 'live-widget-' . substr(md5($channel), 0, 8);
?>
<div class="live-widget" id="<?= View::e($widgetId) ?>" data-channel="<?= View::e($channel) ?>">
    <div class="d-flex align-items-center mb-2">
        <span class="badge bg-danger me-2" id="<?= View::e($widgetId) ?>-status">● LIVE</span>
        <strong><?= View::e($title) ?></strong>
        <small class="text-muted ms-auto" id="<?= View::e($widgetId) ?>-last">—</small>
    </div>
    <ul class="list-group list-group-flush small mb-0" id="<?= View::e($widgetId) ?>-feed" style="max-height:240px;overflow-y:auto;">
        <li class="list-group-item text-muted text-center">Oczekiwanie na aktualizacje...</li>
    </ul>
</div>

<script>
(function () {
    var widgetId = <?= json_encode($widgetId) ?>;
    var channel  = <?= json_encode($channel) ?>;
    var streamUrl = <?= json_encode(url('live/stream/')) ?> + encodeURIComponent(channel);

    var root  = document.getElementById(widgetId);
    if (!root) return;
    var feed  = document.getElementById(widgetId + '-feed');
    var statusEl = document.getElementById(widgetId + '-status');
    var lastEl   = document.getElementById(widgetId + '-last');
    var firstUpdate = true;
    var lastId = 0;

    if (typeof window.EventSource === 'undefined') {
        statusEl.className = 'badge bg-secondary me-2';
        statusEl.textContent = 'SSE niewspierane';
        return;
    }

    var es = new EventSource(streamUrl + '?since=0');

    function appendItem(eventName, data, id) {
        if (firstUpdate) { feed.innerHTML = ''; firstUpdate = false; }
        var li = document.createElement('li');
        li.className = 'list-group-item';
        var ts = new Date().toLocaleTimeString();
        var label = '<span class="badge bg-primary me-2">' + escapeHtml(eventName) + '</span>';
        var pre   = '<code style="font-size:0.78em;">' + escapeHtml(typeof data === 'string' ? data : JSON.stringify(data)) + '</code>';
        li.innerHTML = label + pre + ' <small class="text-muted float-end">#' + id + ' · ' + ts + '</small>';
        feed.insertBefore(li, feed.firstChild);
        // Limit DOM do 50 elementow
        while (feed.children.length > 50) { feed.removeChild(feed.lastChild); }
        lastEl.textContent = 'Ostatni: ' + ts;
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];
        });
    }

    es.onmessage = function (e) {
        try {
            var data = JSON.parse(e.data);
            appendItem('message', data, e.lastEventId || (++lastId));
        } catch (err) {
            appendItem('message', e.data, e.lastEventId || (++lastId));
        }
    };

    // Specyficzne event types
    ['goal','point','quarter','timeout','foul','start','comment'].forEach(function (ev) {
        es.addEventListener(ev, function (e) {
            var data;
            try { data = JSON.parse(e.data); } catch (_) { data = e.data; }
            appendItem(ev, data, e.lastEventId);
        });
    });

    es.addEventListener('end', function (e) {
        statusEl.className = 'badge bg-secondary me-2';
        statusEl.textContent = 'ZAKOŃCZONE';
        try { es.close(); } catch (_) {}
    });

    es.onerror = function () {
        statusEl.className = 'badge bg-warning me-2';
        statusEl.textContent = 'reconnect...';
        // Przegladarka sama wznowi po `retry: 3000`
    };

    es.onopen = function () {
        statusEl.className = 'badge bg-danger me-2';
        statusEl.textContent = '● LIVE';
    };
})();
</script>
