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
            <h6 class="text-muted small"><i class="bi bi-receipt me-1"></i>Składki <?= date('Y') ?></h6>
            <div class="fs-4 fw-bold"><?= format_money($totalThisYear) ?></div>
            <a href="<?= url('portal/fees') ?>" class="small stretched-link text-primary">Historia składek &rarr;</a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 h-100 position-relative <?= ($medical && days_until($medical['valid_until']) !== null && days_until($medical['valid_until']) <= 30) ? 'border-warning' : '' ?>">
            <h6 class="text-muted small"><i class="bi bi-heart-pulse me-1"></i>Badanie lekarskie</h6>
            <?php if ($medical): ?>
                <?php $days = days_until($medical['valid_until']); ?>
                <div class="fw-bold"><?= format_date($medical['valid_until']) ?></div>
                <span class="badge bg-<?= alert_class($days) ?> mt-1">
                    <?= $days !== null && $days < 0 ? 'Wygasłe ' . abs($days) . ' dni temu' : ($days . ' dni') ?>
                </span>
            <?php else: ?>
                <div class="text-muted small">Brak danych</div>
            <?php endif; ?>
            <a href="<?= url('portal/medical') ?>" class="small stretched-link text-primary mt-1">Szczegóły &rarr;</a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 h-100 position-relative">
            <h6 class="text-muted small"><i class="bi bi-patch-check me-1"></i>Licencje</h6>
            <?php $activeLic = array_filter($licenses ?? [], fn($l) => ($l['status'] ?? '') === 'aktywna'); ?>
            <div class="fw-bold fs-4"><?= count($activeLic) ?></div>
            <div class="text-muted small">aktywne</div>
            <a href="<?= url('portal/licenses') ?>" class="small stretched-link text-primary">Szczegóły &rarr;</a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 h-100 position-relative <?= $notifCount > 0 ? 'border-primary' : '' ?>">
            <h6 class="text-muted small"><i class="bi bi-bell me-1"></i>Powiadomienia</h6>
            <div class="fw-bold fs-4 <?= $notifCount > 0 ? 'text-primary' : '' ?>"><?= $notifCount ?></div>
            <div class="text-muted small">nieprzeczytane</div>
            <a href="<?= url('portal/notifications') ?>" class="small stretched-link text-primary">Zobacz &rarr;</a>
        </div>
    </div>
</div>

<div class="row g-3 mt-3">
    <div class="col-md-6">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-calendar-event"></i> Nadchodzące wydarzenia</h5>
            <?php if (empty($upcoming)): ?>
                <div class="text-muted">Brak zaplanowanych wydarzeń.</div>
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
            <h5 class="mb-3"><i class="bi bi-stopwatch"></i> Nadchodzące treningi</h5>
            <?php if (empty($trainings)): ?>
                <div class="text-muted">Brak zaplanowanych treningów.</div>
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

<!-- Szybkie linki do nowych sekcji -->
<?php
// Pasy/stopnie widoczne tylko gdy klub ma aktywny sport ze stopniami
$beltSports = ['bjj','judo','karate','aikido','taekwondo'];
$showBelts = !empty(array_intersect($beltSports, $activeSportKeys ?? []));

$tiles = [
    ['portal/member-card',  'bi-person-badge',   'primary',   'Karta zawodnika'],
    ['portal/announcements','bi-megaphone',       'warning',   'Ogłoszenia'],
    ['portal/schedule',     'bi-calendar3',       'info',      'Plan treningów'],
    ['portal/attendance',   'bi-list-check',      'success',   'Frekwencja'],
    ['portal/results',      'bi-bar-chart',       'danger',    'Wyniki & Rankingi'],
];
if ($showBelts) {
    $tiles[] = ['portal/belts', 'bi-award', 'dark', 'Pasy & Stopnie'];
}
$tiles[] = ['portal/consents',     'bi-shield-check',    'secondary', 'Zgody RODO'];
$tiles[] = ['portal/tournaments',  'bi-trophy',          'primary',   'Zawody'];
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
    <h5 class="mb-3"><i class="bi bi-trophy me-1"></i> Moje sekcje sportowe</h5>
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
