<?php
use App\Helpers\View;
$monthNames = ['',
    'Styczeń','Luty','Marzec','Kwiecień','Maj','Czerwiec',
    'Lipiec','Sierpień','Wrzesień','Październik','Listopad','Grudzień'
];
$prevMonth = $month === 1 ? 12 : $month - 1;
$prevYear  = $month === 1 ? $year - 1 : $year;
$nextMonth = $month === 12 ? 1 : $month + 1;
$nextYear  = $month === 12 ? $year + 1 : $year;

// build map date => events
$byDay = [];
foreach ($events as $e) {
    $d = substr($e['start_at'], 0, 10);
    $byDay[$d][] = $e;
}

$daysInMonth = (int)date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)));
$firstDow = (int)date('N', strtotime(sprintf('%04d-%02d-01', $year, $month))); // 1=Mon
?>
<div class="row g-3">
    <div class="col-lg-9">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chevron-left"></i>
                </a>
                <h4 class="mb-0"><?= $monthNames[$month] ?> <?= $year ?></h4>
                <a href="?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
            <div class="d-flex justify-content-end mb-2 gap-2">
                <a href="<?= url('calendar/ical') ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-calendar-check"></i> Subskrypcja iCal
                </a>
                <a href="<?= url('calendar/create') ?>" class="btn btn-sm btn-success">
                    <i class="bi bi-plus"></i> Nowy wpis
                </a>
            </div>

            <table class="table table-bordered mb-0" style="table-layout:fixed;">
                <thead>
                    <tr>
                        <?php foreach (['Pn','Wt','Śr','Cz','Pt','Sb','Nd'] as $d): ?>
                            <th class="text-center small"><?= $d ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $day = 1;
                $totalCells = $firstDow - 1 + $daysInMonth;
                $weeks = (int)ceil($totalCells / 7);
                for ($w = 0; $w < $weeks; $w++): ?>
                    <tr>
                    <?php for ($dow = 1; $dow <= 7; $dow++):
                        $cellIdx = $w * 7 + $dow;
                        if ($cellIdx < $firstDow || $day > $daysInMonth): ?>
                            <td style="height: 90px; background:#f8f9fa;"></td>
                        <?php else:
                            $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            $dayEvents = $byDay[$dateKey] ?? [];
                            $isToday = $dateKey === date('Y-m-d');
                        ?>
                            <td style="height: 90px; vertical-align: top;" class="<?= $isToday ? 'bg-warning-subtle' : '' ?>">
                                <div class="small text-muted"><strong><?= $day ?></strong></div>
                                <?php foreach ($dayEvents as $e): ?>
                                    <div class="small mt-1 px-1" style="border-left:3px solid <?= View::e($e['category_color'] ?? '#6c757d') ?>; background:#fff;">
                                        <a href="<?= url('calendar/' . (int)$e['id'] . '/edit') ?>" class="text-decoration-none text-dark">
                                            <?= View::e(substr($e['title'], 0, 18)) ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <?php $day++; ?>
                        <?php endif; ?>
                    <?php endfor; ?>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card p-3">
            <h6>Kategorie</h6>
            <?php foreach ($categories as $c): ?>
                <div class="small d-flex align-items-center mb-1">
                    <span style="display:inline-block;width:12px;height:12px;background:<?= View::e($c['color']) ?>;border-radius:2px;"></span>
                    <span class="ms-2"><?= View::e($c['name']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="card p-3 mt-3">
            <h6>Najbliższe (30 dni)</h6>
            <?php if (empty($upcoming)): ?>
                <div class="small text-muted">Brak nadchodzących wpisów.</div>
            <?php else: ?>
                <?php foreach ($upcoming as $u): ?>
                    <div class="small mb-2">
                        <div class="text-muted"><?= format_datetime($u['start_at'], 'd.m H:i') ?></div>
                        <div><?= View::e($u['title']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
