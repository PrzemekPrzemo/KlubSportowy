<?php
use App\Helpers\View;

/** @var array<int, array<string, mixed>> $earned */
/** @var int $totalCount */
/** @var int $earnedCount */
/** @var int $totalPoints */
/** @var array<string, int> $byRarity */

$rarityColors = [
    'common'    => 'secondary',
    'uncommon'  => 'success',
    'rare'      => 'primary',
    'epic'      => 'warning',
    'legendary' => 'danger',
];
$rarityLabels = [
    'common'    => 'Zwykla',
    'uncommon'  => 'Niezwykla',
    'rare'      => 'Rzadka',
    'epic'      => 'Epicka',
    'legendary' => 'Legendarna',
];
$progress = $totalCount > 0 ? round(($earnedCount / $totalCount) * 100) : 0;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="m-0"><i class="bi bi-trophy-fill text-warning me-2"></i>Moje osiągnięcia</h2>
    <a href="<?= url('portal/achievements/catalog') ?>" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-collection"></i> Katalog odznak
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 h-100 border-warning">
            <h6 class="text-muted small"><i class="bi bi-award me-1"></i>Zdobyte odznaki</h6>
            <div class="fs-3 fw-bold text-warning"><?= (int)$earnedCount ?> <small class="text-muted fs-6">/ <?= (int)$totalCount ?></small></div>
            <div class="progress mt-2" style="height:6px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= (float)$progress ?>%"
                     aria-valuenow="<?= (float)$progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <small class="text-muted mt-1"><?= (float)$progress ?>% ukończone</small>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3 h-100 border-primary">
            <h6 class="text-muted small"><i class="bi bi-stars me-1"></i>Łącznie punktów</h6>
            <div class="fs-3 fw-bold text-primary"><?= (int)$totalPoints ?></div>
            <small class="text-muted">z osiągnięć</small>
        </div>
    </div>
    <div class="col-sm-12 col-lg-6">
        <div class="card p-3 h-100">
            <h6 class="text-muted small mb-2"><i class="bi bi-gem me-1"></i>Wg rzadkości</h6>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($byRarity as $r => $c): ?>
                    <span class="badge bg-<?= $rarityColors[$r] ?? 'secondary' ?>">
                        <?= View::e($rarityLabels[$r] ?? $r) ?>: <?= (int)$c ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php if (empty($earned)): ?>
    <div class="card p-5 text-center text-muted">
        <i class="bi bi-trophy fs-1 d-block mb-2"></i>
        <h5>Nie masz jeszcze zadnych odznak</h5>
        <p>Zacznij chodzic na treningi i bierz udzial w turniejach — odznaki przyznawane sa automatycznie.</p>
        <a href="<?= url('portal/achievements/catalog') ?>" class="btn btn-primary btn-sm mt-2">
            Zobacz katalog odznak
        </a>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($earned as $a): ?>
            <?php
                $rar = (string)($a['rarity'] ?? 'common');
                $color = $rarityColors[$rar] ?? 'secondary';
                $hidden = (int)($a['is_displayed'] ?? 1) === 0;
            ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 shadow-sm border-<?= $color ?> <?= $hidden ? 'opacity-50' : '' ?>">
                    <div class="card-body text-center">
                        <div style="font-size:3rem; line-height:1;"><?= View::e($a['icon'] ?? '🏆') ?></div>
                        <h6 class="fw-bold mt-2 mb-1"><?= View::e($a['name'] ?? '') ?></h6>
                        <p class="small text-muted mb-2"><?= View::e($a['description'] ?? '') ?></p>
                        <span class="badge bg-<?= $color ?>"><?= View::e($rarityLabels[$rar] ?? $rar) ?></span>
                        <span class="badge bg-light text-dark border">+<?= (int)($a['points'] ?? 0) ?> pkt</span>
                        <div class="text-muted small mt-2">
                            <i class="bi bi-calendar-check"></i>
                            <?= View::e(date('Y-m-d', strtotime((string)($a['earned_at'] ?? 'now')))) ?>
                        </div>
                    </div>
                    <div class="card-footer p-2 d-flex justify-content-between align-items-center">
                        <form method="POST" action="<?= url('portal/achievements/' . (int)$a['ma_id'] . '/toggle') ?>" class="m-0">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none">
                                <?php if ($hidden): ?>
                                    <i class="bi bi-eye-slash"></i> Pokaz w profilu
                                <?php else: ?>
                                    <i class="bi bi-eye"></i> Ukryj w profilu
                                <?php endif; ?>
                            </button>
                        </form>
                        <small class="text-muted"><?= View::e(strtoupper((string)($a['code'] ?? ''))) ?></small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
