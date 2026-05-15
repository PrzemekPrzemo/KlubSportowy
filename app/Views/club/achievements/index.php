<?php
use App\Helpers\View;

/** @var array<int, array<string, mixed>> $items */
/** @var int $clubId */

$rarityColors = [
    'common' => 'secondary', 'uncommon' => 'success', 'rare' => 'primary',
    'epic' => 'warning', 'legendary' => 'danger',
];
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="m-0"><i class="bi bi-trophy me-2"></i>Odznaki klubu</h2>
    <a href="<?= url('club/achievements/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle"></i> Nowa custom odznaka
    </a>
</div>

<?php if (empty($items)): ?>
    <div class="alert alert-info">Brak odznak. Mozesz uzyc globalnych badzi dodac wlasne.</div>
<?php else: ?>
    <div class="card">
        <table class="table table-hover mb-0">
            <thead class="table-light">
            <tr>
                <th style="width:60px;">Ikona</th>
                <th>Nazwa / Code</th>
                <th>Kategoria</th>
                <th>Rzadkosc</th>
                <th>Punkty</th>
                <th>Zakres</th>
                <th>Zdobyte</th>
                <th>Status</th>
                <th>Akcje</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $it):
                $rar = (string)($it['rarity'] ?? 'common');
                $color = $rarityColors[$rar] ?? 'secondary';
                $isGlobal = $it['club_id'] === null;
                $isActive = (int)($it['is_active'] ?? 1) === 1;
            ?>
                <tr>
                    <td style="font-size:1.5rem;"><?= View::e($it['icon'] ?? '🏆') ?></td>
                    <td>
                        <div class="fw-semibold"><?= View::e($it['name'] ?? '') ?></div>
                        <small class="text-muted"><code><?= View::e($it['code'] ?? '') ?></code></small>
                        <?php if (!empty($it['description'])): ?>
                            <div class="small text-muted"><?= View::e($it['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= View::e($it['category'] ?? '') ?></span></td>
                    <td><span class="badge bg-<?= $color ?>"><?= View::e($rar) ?></span></td>
                    <td><?= (int)($it['points'] ?? 0) ?></td>
                    <td>
                        <?php if ($isGlobal): ?>
                            <span class="badge bg-info">global</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">custom</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)($it['earned_count'] ?? 0) ?>x</td>
                    <td>
                        <?php if ($isActive): ?>
                            <span class="badge bg-success">aktywna</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">wylaczona</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$isGlobal): ?>
                            <a href="<?= url('club/achievements/' . (int)$it['id'] . '/edit') ?>"
                               class="btn btn-sm btn-outline-primary" title="Edytuj">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="<?= url('club/achievements/' . (int)$it['id'] . '/toggle') ?>" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-secondary" title="Przelacz status">
                                    <i class="bi bi-toggle-on"></i>
                                </button>
                            </form>
                            <form method="POST" action="<?= url('club/achievements/' . (int)$it['id'] . '/delete') ?>" class="d-inline"
                                  onsubmit="return confirm('Usunac te odznake? Czlonkowie ktorzy ja zdobyli ja straca.');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" title="Usun">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">tylko global</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
