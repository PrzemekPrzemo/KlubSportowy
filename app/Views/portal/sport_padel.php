<?php use App\Helpers\View; ?>

<?php if (!empty($myPairs)): ?>
<div class="card p-3 mb-3">
    <h6 class="mb-3"><i class="bi bi-people me-1"></i>Moje pary</h6>
    <?php
    $catLabel = ['men' => 'Mężczyźni', 'women' => 'Kobiety', 'mixed' => 'Mikst'];
    foreach ($myPairs as $i => $p):
    ?>
        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
            <div>
                <strong>#<?= $i + 1 ?></strong>
                <?= View::e($p['pair_name'] ?? $p['p1_last'] . '/' . $p['p2_last']) ?>
                <span class="badge bg-info text-dark ms-1"><?= $catLabel[$p['category']] ?? '' ?></span>
            </div>
            <div class="fw-bold"><?= (int)$p['ranking_points'] ?> pkt</div>
        </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Brak par padel.</div>
<?php endif; ?>

<?php if (!empty($myReservations)): ?>
<div class="card p-3">
    <h6 class="mb-3"><i class="bi bi-calendar3 me-1"></i>Moje rezerwacje kortów</h6>
    <?php foreach ($myReservations as $r):
        $sBadge=['pending'=>['warning','Oczekuje'],'confirmed'=>['success','Potwierdzona'],'cancelled'=>['secondary','Anulowana']];
        [$sc,$sl]=$sBadge[$r['status']]??['secondary',$r['status']];
    ?>
        <div class="border-bottom py-2 d-flex justify-content-between align-items-center">
            <div>
                <strong><?= View::e($r['court_name']) ?></strong>
                <div class="small text-muted">
                    <?= date('d.m.Y H:i', strtotime($r['start_datetime'])) ?> – <?= date('H:i', strtotime($r['end_datetime'])) ?>
                </div>
            </div>
            <span class="badge bg-<?= $sc ?> text-dark"><?= $sl ?></span>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
