<?php
use App\Helpers\MemberAuth;
use App\Helpers\View;
try {
    $notifCount = (new \App\Models\MemberNotificationModel())
        ->countUnread((int)($member['id'] ?? 0), (int)MemberAuth::clubId());
} catch (\Throwable) { $notifCount = 0; }
?>
<div class="row g-3">
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 h-100 position-relative">
            <h6 class="text-muted small"><i class="bi bi-receipt me-1"></i><?= __('portal.dash.fees_year', ['year' => date('Y')]) ?></h6>
            <div class="fs-4 fw-bold"><?= format_money($totalThisYear) ?></div>
            <div class="d-flex gap-2 small">
                <a href="<?= url('portal/dues') ?>" class="text-primary"><?= __('portal.dash.dues') ?></a>
                <span class="text-muted">·</span>
                <a href="<?= url('portal/fees') ?>" class="text-primary"><?= __('portal.dash.history') ?></a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 h-100 position-relative <?= ($medical && days_until($medical['valid_until']) !== null && days_until($medical['valid_until']) <= 30) ? 'border-warning' : '' ?>">
            <h6 class="text-muted small"><i class="bi bi-heart-pulse me-1"></i><?= __('portal.dash.medical_exam') ?></h6>
            <?php if ($medical): ?>
                <?php $days = days_until($medical['valid_until']); ?>
                <div class="fw-bold"><?= format_date($medical['valid_until']) ?></div>
                <span class="badge bg-<?= alert_class($days) ?> mt-1">
                    <?= $days !== null && $days < 0 ? __('portal.dash.expired_days_ago', ['days' => abs($days)]) : __('portal.dash.days_short', ['days' => $days]) ?>
                </span>
            <?php else: ?>
                <div class="text-muted small"><?= __('portal.dash.no_data') ?></div>
            <?php endif; ?>
            <a href="<?= url('portal/medical') ?>" class="small stretched-link text-primary mt-1"><?= __('portal.dash.details_arrow') ?></a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 h-100 position-relative">
            <h6 class="text-muted small"><i class="bi bi-patch-check me-1"></i><?= __('portal.dash.licenses') ?></h6>
            <?php $activeLic = array_filter($licenses ?? [], fn($l) => ($l['status'] ?? '') === 'aktywna'); ?>
            <div class="fw-bold fs-4"><?= count($activeLic) ?></div>
            <div class="text-muted small"><?= __('portal.dash.licenses_active') ?></div>
            <a href="<?= url('portal/licenses') ?>" class="small stretched-link text-primary"><?= __('portal.dash.details_arrow') ?></a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 h-100 position-relative <?= $notifCount > 0 ? 'border-primary' : '' ?>">
            <h6 class="text-muted small"><i class="bi bi-bell me-1"></i><?= __('portal.notifications') ?></h6>
            <div class="fw-bold fs-4 <?= $notifCount > 0 ? 'text-primary' : '' ?>"><?= $notifCount ?></div>
            <div class="text-muted small"><?= __('portal.dash.unread') ?></div>
            <a href="<?= url('portal/notifications') ?>" class="small stretched-link text-primary"><?= __('portal.dash.view_arrow') ?></a>
        </div>
    </div>
</div>

<div class="row g-3 mt-3">
    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-calendar-event"></i> <?= __('portal.dash.upcoming_events') ?></h5>
            <?php if (empty($upcoming)): ?>
                <div class="text-muted"><?= __('portal.dash.no_events') ?></div>
            <?php else: ?>
                <?php foreach ($upcoming as $e): ?>
                    <div class="border-bottom py-2">
                        <strong><?= View::e($e['name']) ?></strong>
                        <small class="text-muted d-block"><?= format_datetime($e['event_date']) ?> • <?= View::e($e['location'] ?? '') ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-stopwatch"></i> <?= __('portal.dash.upcoming_trainings') ?></h5>
            <?php if (empty($trainings)): ?>
                <div class="text-muted"><?= __('portal.dash.no_trainings') ?></div>
            <?php else: ?>
                <?php foreach ($trainings as $t): ?>
                    <div class="border-bottom py-2">
                        <strong><?= View::e($t['name']) ?></strong>
                        <small class="text-muted d-block"><?= format_datetime($t['start_time']) ?> • <?= View::e($t['location'] ?? '') ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Widget: ostatnie osiągnięcia + total points -->
<?php
$recentAch = [];
$achPoints = 0;
try {
    $_db = \App\Helpers\Database::pdo();
    $_stmt = $_db->prepare(
        "SELECT ac.icon, ac.name, ac.rarity, ac.points, ma.earned_at
         FROM member_achievements ma
         JOIN achievement_catalog ac ON ac.id = ma.achievement_id
         WHERE ma.member_id = ?
         ORDER BY ma.earned_at DESC
         LIMIT 3"
    );
    $_stmt->execute([(int)($member['id'] ?? 0)]);
    $recentAch = $_stmt->fetchAll(\PDO::FETCH_ASSOC);

    $_stmt2 = $_db->prepare(
        "SELECT COALESCE(SUM(ac.points),0)
         FROM member_achievements ma
         JOIN achievement_catalog ac ON ac.id = ma.achievement_id
         WHERE ma.member_id = ?"
    );
    $_stmt2->execute([(int)($member['id'] ?? 0)]);
    $achPoints = (int)$_stmt2->fetchColumn();
} catch (\Throwable) {}
?>
<div class="row g-3 mt-3">
    <div class="col-12">
        <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0"><i class="bi bi-trophy-fill text-warning"></i> Ostatnie osiągnięcia
                    <span class="badge bg-warning text-dark ms-2"><?= $achPoints ?> pkt</span>
                </h5>
                <a href="<?= url('portal/achievements') ?>" class="btn btn-sm btn-outline-warning">
                    Zobacz wszystkie
                </a>
            </div>
            <?php if (empty($recentAch)): ?>
                <div class="text-muted small">
                    Nie masz jeszcze odznak. Chodz na treningi i bierz udzial w turniejach!
                    <a href="<?= url('portal/achievements/catalog') ?>">Zobacz katalog &rarr;</a>
                </div>
            <?php else: ?>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($recentAch as $a): ?>
                        <div class="text-center" style="min-width:120px;">
                            <div style="font-size:2rem; line-height:1;"><?= View::e($a['icon'] ?? '🏆') ?></div>
                            <div class="fw-semibold small"><?= View::e($a['name'] ?? '') ?></div>
                            <small class="text-muted"><?= View::e(date('Y-m-d', strtotime((string)$a['earned_at']))) ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Szybkie linki do nowych sekcji -->
<?php
// Pasy/stopnie widoczne tylko gdy klub ma aktywny sport ze stopniami
$beltSports = ['bjj','judo','karate','aikido','taekwondo'];
$showBelts = !empty(array_intersect($beltSports, $activeSportKeys ?? []));

$tiles = [
    ['portal/member-card',  'bi-person-badge',   'primary',   __('portal.dash.tile.member_card')],
    ['portal/announcements','bi-megaphone',       'warning',   __('portal.dash.tile.announcements')],
    ['portal/schedule',     'bi-calendar3',       'info',      __('portal.dash.tile.schedule')],
    ['portal/attendance',   'bi-list-check',      'success',   __('portal.dash.tile.attendance')],
    ['portal/results',      'bi-bar-chart',       'danger',    __('portal.dash.tile.results')],
];
if ($showBelts) {
    $tiles[] = ['portal/belts', 'bi-award', 'dark', __('portal.dash.tile.belts')];
}
$tiles[] = ['portal/consents',     'bi-shield-check',    'secondary', __('portal.dash.tile.consents')];
$tiles[] = ['portal/tournaments',  'bi-trophy',          'primary',   __('portal.dash.tile.tournaments')];
?>
<div class="row g-2 mt-3">
    <?php foreach ($tiles as [$link, $icon, $color, $label]): ?>
    <div class="col-6 col-md-3">
        <a href="<?= url($link) ?>" class="card text-decoration-none text-<?= $color ?> p-3 d-flex flex-row align-items-center gap-2">
            <i class="bi <?= $icon ?> fs-4"></i>
            <span class="small fw-semibold"><?= $label ?></span>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<?php if (!empty($activeSportKeys)): ?>
<div class="card mt-3 p-3">
    <h5 class="mb-3"><i class="bi bi-trophy me-1"></i> <?= __('portal.dash.my_sections') ?></h5>
    <div class="row g-2">
        <?php foreach ($activeSportKeys as $sportKey):
            $manifest = \App\Helpers\SportModuleLoader::get($sportKey);
            if (!$manifest) continue;
            $label = $manifest['name'] ?? ucfirst($sportKey);
            $icon  = $manifest['icon']  ?? 'bi-trophy';
        ?>
            <div class="col-6 col-md-3">
                <a href="<?= url('portal/sport/' . $sportKey) ?>"
                   class="card text-decoration-none text-dark p-2 d-flex flex-row align-items-center gap-2 h-100 hover-shadow">
                    <i class="bi <?= View::e($icon) ?> fs-5 text-primary"></i>
                    <span class="small fw-semibold"><?= View::e($label) ?></span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
