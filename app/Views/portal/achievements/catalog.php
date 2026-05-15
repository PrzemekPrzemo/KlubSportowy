<?php
use App\Helpers\View;

/** @var array<string, array<int, array<string, mixed>>> $grouped */
/** @var array<int, string> $earnedMap */

$rarityColors = [
    'common'    => 'secondary',
    'uncommon'  => 'success',
    'rare'      => 'primary',
    'epic'      => 'warning',
    'legendary' => 'danger',
];
$rarityLabels = [
    'common' => 'Zwykla', 'uncommon' => 'Niezwykla', 'rare' => 'Rzadka',
    'epic'   => 'Epicka', 'legendary' => 'Legendarna',
];
$categoryLabels = [
    'attendance'     => 'Frekwencja',
    'tournament'     => 'Turnieje',
    'training'       => 'Trening i postep',
    'milestone'      => 'Jubileusze',
    'sport_specific' => 'Specjalne sportowe',
    'social'         => 'Spolecznosc',
    'other'          => 'Inne',
];

function ach_hint(array $a): string {
    $crit = $a['criteria'] ?? null;
    if (is_string($crit)) {
        $crit = json_decode($crit, true);
    }
    if (!is_array($crit)) return '';
    $type = (string)($crit['type'] ?? '');
    return match ($type) {
        'trainings_count'          => 'Wymaga: ' . (int)($crit['count'] ?? 0) . ' obecnych treningow',
        'tournament_played'        => 'Wymaga: udzial w turnieju',
        'tournament_place'         => 'Wymaga: miejsce ' . (int)($crit['place'] ?? 0) . ' w turnieju',
        'tournament_top'           => 'Wymaga: top ' . (int)($crit['n'] ?? 0) . ' w turnieju',
        'tournaments_played_count' => 'Wymaga: ' . (int)($crit['count'] ?? 0) . ' rozegranych turniejow',
        'season_wins'              => 'Wymaga: ' . (int)($crit['count'] ?? 0) . ' wygranych w sezonie',
        'membership_years'         => 'Wymaga: ' . (int)($crit['years'] ?? 0) . ' lat w klubie',
        'perfect_month'            => 'Wymaga: miesiac ze 100% obecnoscia',
        'training_streak'          => 'Wymaga: ' . (int)($crit['count'] ?? 0) . ' obecnych z rzedu',
        'referrals_count'          => 'Wymaga: ' . (int)($crit['count'] ?? 0) . ' polecen',
        'team_match_won'           => 'Wymaga: wygrana w meczu druzynowym',
        'belt_promotions_count'    => 'Wymaga: ' . (int)($crit['count'] ?? 0) . ' promocji pasow',
        'profile_complete'         => 'Wymaga: uzupelniony profil',
        default                    => '',
    };
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="m-0"><i class="bi bi-collection text-primary me-2"></i>Katalog odznak</h2>
    <a href="<?= url('portal/achievements') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Moje osiagniecia
    </a>
</div>

<p class="text-muted">Wszystkie odznaki dostepne w klubie. <i class="bi bi-check-circle text-success"></i> = zdobyta, <i class="bi bi-lock text-muted"></i> = jeszcze do zdobycia.</p>

<?php foreach ($grouped as $cat => $items): ?>
    <h4 class="mt-4 mb-3"><i class="bi bi-folder me-2"></i><?= View::e($categoryLabels[$cat] ?? $cat) ?></h4>
    <div class="row g-3">
        <?php foreach ($items as $a): ?>
            <?php
                $earned = !empty($a['earned_at']);
                $rar    = (string)($a['rarity'] ?? 'common');
                $color  = $rarityColors[$rar] ?? 'secondary';
                $opacityClass = $earned ? '' : 'opacity-50';
            ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="card h-100 shadow-sm <?= $earned ? "border-{$color}" : '' ?> <?= $opacityClass ?>">
                    <div class="card-body text-center">
                        <div style="font-size:2.5rem; line-height:1;">
                            <?= View::e($a['icon'] ?? '🏆') ?>
                        </div>
                        <h6 class="fw-bold mt-2 mb-1"><?= View::e($a['name'] ?? '') ?></h6>
                        <p class="small text-muted mb-2"><?= View::e($a['description'] ?? '') ?></p>
                        <div>
                            <span class="badge bg-<?= $color ?>"><?= View::e($rarityLabels[$rar] ?? $rar) ?></span>
                            <span class="badge bg-light text-dark border">+<?= (int)($a['points'] ?? 0) ?> pkt</span>
                        </div>
                        <?php if ($earned): ?>
                            <div class="text-success small mt-2">
                                <i class="bi bi-check-circle-fill"></i> Zdobyta
                                <div class="text-muted"><?= View::e(date('Y-m-d', strtotime((string)$a['earned_at']))) ?></div>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small mt-2">
                                <i class="bi bi-lock"></i> <?= View::e(ach_hint($a)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>
