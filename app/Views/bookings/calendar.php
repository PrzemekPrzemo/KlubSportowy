<?php use App\Helpers\View; ?>
<?php
$ws = $weekStart ?? date('Y-m-d', strtotime('monday this week'));
$prevWeek = date('Y-m-d', strtotime($ws . ' -7 days'));
$nextWeek = date('Y-m-d', strtotime($ws . ' +7 days'));
$days = [];
for ($d = 0; $d < 7; $d++) {
    $days[] = date('Y-m-d', strtotime($ws . " +{$d} days"));
}
$hours = range(7, 21);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex gap-2 align-items-center">
        <a href="<?= url('bookings/calendar?facility=' . (int)($facilityId ?? 0) . '&week=' . $prevWeek) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-chevron-left"></i>
        </a>
        <strong><?= format_date($ws) ?> — <?= format_date(end($days)) ?></strong>
        <a href="<?= url('bookings/calendar?facility=' . (int)($facilityId ?? 0) . '&week=' . $nextWeek) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-chevron-right"></i>
        </a>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <label class="form-label mb-0">Obiekt:</label>
        <select class="form-select form-select-sm" style="width:auto;"
                onchange="location.href='<?= url('bookings/calendar') ?>?facility='+this.value+'&week=<?= $ws ?>'">
            <?php foreach (($facilities ?? []) as $f): ?>
                <option value="<?= (int)$f['id'] ?>" <?= (int)($facilityId ?? 0) === (int)$f['id'] ? 'selected' : '' ?>>
                    <?= View::e($f['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<?php if (empty($facilities)): ?>
    <div class="card p-4 text-center text-muted">
        Brak obiektów. <a href="<?= url('bookings/facilities') ?>">Dodaj obiekt</a> aby korzystać z kalendarza.
    </div>
<?php else: ?>

<!-- Weekly grid -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0" style="font-size:.85rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">Godz.</th>
                    <?php foreach ($days as $day): ?>
                        <th class="text-center <?= $day === date('Y-m-d') ? 'table-primary' : '' ?>">
                            <?= ['Pn','Wt','Śr','Cz','Pt','Sb','Nd'][(int)date('N', strtotime($day)) - 1] ?>
                            <br><small><?= date('d.m', strtotime($day)) ?></small>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hours as $h): ?>
                    <tr>
                        <td class="text-center text-muted fw-bold"><?= sprintf('%02d:00', $h) ?></td>
                        <?php foreach ($days as $day):
                            $slotStart = $day . ' ' . sprintf('%02d:00:00', $h);
                            $slotEnd   = $day . ' ' . sprintf('%02d:59:59', $h);
                            $slotBookings = [];
                            foreach (($bookings ?? []) as $b) {
                                if ($b['start_time'] < $slotEnd && $b['end_time'] > $slotStart) {
                                    $slotBookings[] = $b;
                                }
                            }
                        ?>
                            <td class="<?= !empty($slotBookings) ? 'table-warning' : '' ?>" style="min-width:100px; cursor:pointer;"
                                onclick="openBookForm('<?= $day ?>', <?= $h ?>, <?= (int)($facilityId ?? 0) ?>)">
                                <?php foreach ($slotBookings as $sb): ?>
                                    <div class="small rounded px-1 mb-1 <?= $sb['status'] === 'confirmed' ? 'bg-success text-white' : ($sb['status'] === 'pending' ? 'bg-warning' : 'bg-secondary text-white') ?>">
                                        <strong><?= View::e($sb['title']) ?></strong><br>
                                        <small><?= date('H:i', strtotime($sb['start_time'])) ?>-<?= date('H:i', strtotime($sb['end_time'])) ?></small>
                                        <?php if (!empty($sb['booked_by_name'])): ?>
                                            <br><small><?= View::e($sb['booked_by_name']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($sb['status'] !== 'cancelled'): ?>
                                            <form method="POST" action="<?= url('bookings/' . (int)$sb['id'] . '/cancel') ?>"
                                                  onsubmit="event.stopPropagation(); return confirm('Anulować?')" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-link btn-sm text-white p-0" onclick="event.stopPropagation();">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick booking modal -->
<div class="modal fade" id="bookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('bookings/book') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-calendar-plus"></i> Nowa rezerwacja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="facility_id" id="bookFacilityId">
                    <div class="mb-3">
                        <label class="form-label">Tytuł</label>
                        <input type="text" name="title" class="form-control" required maxlength="150">
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Od</label>
                            <input type="datetime-local" name="start_time" id="bookStart" class="form-control" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Do</label>
                            <input type="datetime-local" name="end_time" id="bookEnd" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dla zawodnika (opcjonalnie)</label>
                        <select name="booked_for_id" class="form-select">
                            <option value="">— brak —</option>
                            <?php foreach (($members ?? []) as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check"></i> Zarezerwuj</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function openBookForm(day, hour, facilityId) {
    document.getElementById('bookFacilityId').value = facilityId;
    var hh = ('0'+hour).slice(-2);
    var hhEnd = ('0'+(hour+1)).slice(-2);
    document.getElementById('bookStart').value = day + 'T' + hh + ':00';
    document.getElementById('bookEnd').value = day + 'T' + hhEnd + ':00';
    new bootstrap.Modal(document.getElementById('bookModal')).show();
}
</script>
