<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card p-3">
            <div class="text-center mb-3">
                <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center" style="width:80px;height:80px;">
                    <span class="text-white" style="font-size:2rem"><?= mb_substr($member['first_name'],0,1) ?><?= mb_substr($member['last_name'],0,1) ?></span>
                </div>
                <h4 class="mt-2 mb-0"><?= View::e($member['first_name']) ?> <?= View::e($member['last_name']) ?></h4>
                <small class="text-muted">#<?= View::e($member['member_number']) ?></small>
            </div>
            <dl class="row small mb-0">
                <dt class="col-6">Status</dt><dd class="col-6"><span class="badge bg-<?= $member['status']==='aktywny'?'success':'secondary' ?>"><?= View::e($member['status']) ?></span></dd>
                <dt class="col-6">Członek od</dt><dd class="col-6"><?= format_date($member['join_date']) ?></dd>
                <dt class="col-6">Sekcje sportowe</dt><dd class="col-6"><?= count($member['sports'] ?? []) ?></dd>
            </dl>
            <?php foreach (($member['sports'] ?? []) as $s): ?>
                <span class="sport-badge mt-1" style="background:<?= View::e($s['color']) ?>"><?= View::e($s['sport_name']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Attendance -->
        <div class="card p-3 mb-3">
            <h5><i class="bi bi-check2-square"></i> Frekwencja treningów</h5>
            <div class="d-flex gap-3">
                <?php foreach (['obecny'=>'success','zapisany'=>'info','nieobecny'=>'danger','spozniony'=>'warning'] as $s=>$c): ?>
                    <div class="text-center">
                        <div class="display-6"><?= (int)($attendanceStats[$s] ?? 0) ?></div>
                        <small class="text-<?= $c ?>"><?= $s ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Events -->
        <div class="card p-3 mb-3">
            <h5><i class="bi bi-calendar-event"></i> Udział w wydarzeniach</h5>
            <div class="d-flex gap-3">
                <?php foreach (['zgloszony'=>'info','potwierdzony'=>'success','wycofany'=>'secondary'] as $s=>$c): ?>
                    <div class="text-center">
                        <div class="display-6"><?= (int)($eventStats[$s] ?? 0) ?></div>
                        <small class="text-<?= $c ?>"><?= $s ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Payment history -->
        <div class="card p-3 mb-3">
            <h5><i class="bi bi-cash-coin"></i> Historia wpłat</h5>
            <?php if (empty($paymentHistory)): ?>
                <div class="text-muted small">Brak wpłat.</div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead><tr><th>Rok</th><th class="text-end">Suma</th></tr></thead>
                    <tbody>
                    <?php foreach ($paymentHistory as $p): ?>
                        <tr><td><?= (int)$p['period_year'] ?></td><td class="text-end"><strong><?= format_money($p['total']) ?></strong></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Results -->
        <?php if (!empty($resultHistory)): ?>
        <div class="card p-3 mb-3">
            <h5><i class="bi bi-trophy"></i> Wyniki (ostatnie 20)</h5>
            <table class="table table-sm mb-0">
                <thead><tr><th>Data</th><th>Wydarzenie</th><th>Sport</th><th class="text-end">Wynik</th><th>Miejsce</th></tr></thead>
                <tbody>
                <?php foreach ($resultHistory as $r): ?>
                    <tr>
                        <td><small><?= format_date(substr($r['event_date'],0,10)) ?></small></td>
                        <td><?= View::e($r['event_name']) ?></td>
                        <td><small><?= View::e($r['sport_name'] ?? '') ?></small></td>
                        <td class="text-end"><strong><?= $r['score'] !== null ? number_format((float)$r['score'], 2) : '—' ?></strong></td>
                        <td><?= $r['place'] ? '#' . (int)$r['place'] : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Licenses -->
        <?php if (!empty($licenseList)): ?>
        <div class="card p-3 mb-3">
            <h5><i class="bi bi-patch-check"></i> Licencje</h5>
            <?php foreach ($licenseList as $l): ?>
                <div class="d-flex justify-content-between border-bottom py-1">
                    <span><?= View::e($l['license_type']) ?> <?= !empty($l['federation_code']) ? '(' . View::e($l['federation_code']) . ')' : '' ?></span>
                    <span>
                        <code><?= View::e($l['license_number']) ?></code>
                        — do <?= format_date($l['valid_until']) ?>
                        <span class="badge bg-<?= alert_class(days_until($l['valid_until'])) ?>">
                            <?= days_until($l['valid_until']) >= 0 ? days_until($l['valid_until']) . ' dni' : 'wygasła' ?>
                        </span>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Medical -->
        <?php if (!empty($medicalTimeline)): ?>
        <div class="card p-3">
            <h5><i class="bi bi-heart-pulse"></i> Badania lekarskie</h5>
            <?php foreach ($medicalTimeline as $m): ?>
                <div class="d-flex justify-content-between border-bottom py-1 small">
                    <span><?= View::e($m['exam_type']) ?> — <?= format_date($m['exam_date']) ?></span>
                    <span>ważne do <?= format_date($m['valid_until']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
