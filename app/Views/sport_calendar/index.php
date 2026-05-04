<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Kalendarz zawodów — <?= View::e($sportName) ?></h4>
    <a href="<?= url('events/create?sport_key='.urlencode($sportKey)) ?>" class="btn btn-success btn-sm">
        <i class="bi bi-plus-circle"></i> Dodaj zawody
    </a>
</div>

<form method="GET" class="d-flex gap-2 align-items-center mb-3">
    <label class="form-label mb-0 text-nowrap">Od daty:</label>
    <input type="date" name="from" class="form-control form-control-sm w-auto"
           value="<?= View::e($from ?? '') ?>">
    <button type="submit" class="btn btn-sm btn-outline-secondary">Filtruj</button>
    <?php if ($from): ?>
        <a href="?" class="btn btn-sm btn-link">Wyczyść</a>
    <?php endif; ?>
</form>

<?php
$typeLabels = [
    'mecz'    => ['Mecz',    'primary'],
    'zawody'  => ['Zawody',  'success'],
    'trening' => ['Trening', 'secondary'],
    'obóz'    => ['Obóz',    'info'],
    'turniej' => ['Turniej', 'warning'],
    'inny'    => ['Inne',    'light'],
];
$statusLabels = [
    'planowane'  => ['Planowane',  'secondary'],
    'otwarte'    => ['Otwarte',    'success'],
    'w_trakcie'  => ['W trakcie',  'primary'],
    'zakonczone' => ['Zakończone', 'dark'],
    'odwolane'   => ['Odwołane',   'danger'],
];
?>

<?php if (empty($pagination['data'])): ?>
<div class="alert alert-info">
    <i class="bi bi-calendar-x me-2"></i>
    Brak zaplanowanych zawodów dla <?= View::e($sportName) ?>.
    Aby zobaczyć zawody, dodaj je w sekcji <a href="<?= url('events') ?>">Kalendarz</a> i przypisz sport.
</div>
<?php else: ?>

<div class="row g-3">
<?php foreach ($pagination['data'] as $e):
    [$typeLabel, $typeColor] = $typeLabels[$e['type']] ?? ['Inne', 'light'];
    [$statusLabel, $statusColor] = $statusLabels[$e['status']] ?? ['?', 'secondary'];
    $isPast = $e['event_date'] < date('Y-m-d H:i:s');
?>
    <div class="col-md-6">
        <div class="card h-100 <?= $isPast ? 'opacity-75' : '' ?>">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="badge bg-<?= $typeColor ?> text-dark"><?= $typeLabel ?></span>
                <span class="badge bg-<?= $statusColor ?>"><?= $statusLabel ?></span>
            </div>
            <div class="card-body py-2">
                <h6 class="mb-1"><?= View::e($e['name']) ?></h6>
                <div class="text-muted small">
                    <i class="bi bi-calendar me-1"></i>
                    <?= date('j.m.Y H:i', strtotime($e['event_date'])) ?>
                    <?php if ($e['end_date']): ?>
                        — <?= date('j.m.Y', strtotime($e['end_date'])) ?>
                    <?php endif; ?>
                </div>
                <?php if ($e['location']): ?>
                <div class="text-muted small">
                    <i class="bi bi-geo-alt me-1"></i><?= View::e($e['location']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!$isPast && $e['status'] === 'otwarte'): ?>
            <div class="card-footer py-1">
                <a href="<?= url($sportKey.'/results?competition='.urlencode($e['name'])) ?>"
                   class="btn btn-sm btn-outline-success w-100">
                    <i class="bi bi-plus-circle me-1"></i>Dodaj wynik po zawodach
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php if ($pagination['last_page'] > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-end mb-0">
        <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
            <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $p ?><?= $from ? '&from='.urlencode($from) : '' ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
<?php endif; ?>
