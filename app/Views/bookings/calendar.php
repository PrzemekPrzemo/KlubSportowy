<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0"><i class="bi bi-calendar-week"></i> Kalendarz rezerwacji</h4>
    <div class="d-flex gap-2 align-items-center">
        <select id="resource-filter" class="form-select form-select-sm" style="width:auto;">
            <option value="">Wszystkie zasoby</option>
            <?php foreach (($resources ?? []) as $r): ?>
                <option value="<?= (int)$r['id'] ?>" <?= (int)($resourceId ?? 0) === (int)$r['id'] ? 'selected' : '' ?>>
                    <?= View::e($r['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <a href="<?= url('bookings/create') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Nowa rezerwacja
        </a>
        <a href="<?= url('club/resources') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-gear"></i> Zasoby
        </a>
    </div>
</div>

<?php if (empty($resources)): ?>
    <div class="card p-4 text-center text-muted">
        Brak aktywnych zasobów do rezerwacji.
        <a href="<?= url('club/resources/create') ?>">Dodaj pierwszy zasób</a>.
    </div>
<?php else: ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-3 mb-2">
                <?php foreach ($resources as $r): ?>
                    <div class="d-flex align-items-center gap-1">
                        <span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:<?= View::e($r['color']) ?>"></span>
                        <small><?= View::e($r['name']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="calendar"></div>
        </div>
    </div>
<?php endif; ?>

<!-- FullCalendar v6 z CDN -->
<link  href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('calendar');
    if (!el) return;

    var filterSelect = document.getElementById('resource-filter');
    function currentResource() {
        var v = filterSelect ? filterSelect.value : '';
        return v ? parseInt(v, 10) : null;
    }

    var calendar = new FullCalendar.Calendar(el, {
        initialView: 'timeGridWeek',
        locale: 'pl',
        firstDay: 1,
        nowIndicator: true,
        slotMinTime: '06:00:00',
        slotMaxTime: '23:00:00',
        height: 700,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: { today: 'dziś', month: 'miesiąc', week: 'tydzień', day: 'dzień' },
        slotDuration: '00:30:00',
        selectable: true,
        events: function(info, success, failure) {
            var params = new URLSearchParams({
                from: info.startStr.substring(0, 10),
                to:   info.endStr.substring(0, 10)
            });
            var rid = currentResource();
            if (rid) params.set('resource_id', rid);
            fetch('<?= url('bookings/api/events') ?>?' + params.toString())
                .then(function(r){ return r.json(); })
                .then(success)
                .catch(failure);
        },
        select: function(info) {
            var rid = currentResource() || '';
            var s = info.startStr.replace(/[+-]\d{2}:\d{2}$/,'').substring(0,16);
            var e = info.endStr.replace(/[+-]\d{2}:\d{2}$/,'').substring(0,16);
            var url = '<?= url('bookings/create') ?>?resource_id=' + rid + '&start=' + encodeURIComponent(s) + '&end=' + encodeURIComponent(e);
            window.location.href = url;
        },
        eventClick: function(info) {
            window.location.href = '<?= url('bookings/') ?>' + info.event.id;
        }
    });
    calendar.render();

    if (filterSelect) {
        filterSelect.addEventListener('change', function(){ calendar.refetchEvents(); });
    }
});
</script>
