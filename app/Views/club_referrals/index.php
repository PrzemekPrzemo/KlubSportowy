<?php
use App\Helpers\Csrf;
use App\Helpers\View;
/**
 * @var string $code
 * @var array|null $codeRow
 * @var array $referrals
 * @var array $stats
 * @var float $totalEarn
 * @var array|null $rewardCfg
 * @var string $shareLink
 */

$statusLabel = [
    'pending'   => ['Pending',   'secondary'],
    'qualified' => ['Aktywny',   'success'],
    'paid'      => ['Wyplacony', 'primary'],
    'expired'   => ['Wygasl',    'warning'],
    'cancelled' => ['Anulowany', 'danger'],
];
$timesUsed = (int)($codeRow['times_used'] ?? 0);
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-share me-2"></i> Polecenia / rabaty</h1>
        <a href="<?= url('club/referrals/share') ?>" class="btn btn-outline-primary">
            <i class="bi bi-send"></i> Udostepnij zaproszenie
        </a>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger"><?= View::e($flashError) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-ticket-perforated me-1"></i> Twoj kod polecajacy
                    </h5>
                    <div class="d-flex align-items-center gap-3 flex-wrap mb-3">
                        <code id="ref-code" class="px-3 py-2 fs-4 bg-light rounded border" style="letter-spacing:2px;">
                            <?= View::e($code) ?>
                        </code>
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="navigator.clipboard.writeText(document.getElementById('ref-code').innerText.trim());this.innerHTML='<i class=\'bi bi-check\'></i> Skopiowano';">
                            <i class="bi bi-clipboard"></i> Skopiuj
                        </button>
                        <form method="post" action="<?= url('club/referrals/regenerate') ?>" class="d-inline"
                              onsubmit="return confirm('Wygenerowac nowy kod? Stary przestanie dzialac. Mozesz to zrobic maks. raz na dobe.');">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                <i class="bi bi-arrow-clockwise"></i> Regeneruj
                            </button>
                        </form>
                    </div>
                    <div class="mb-2"><strong>Link do udostepnienia:</strong></div>
                    <div class="input-group">
                        <input type="text" class="form-control" id="ref-share-link"
                               value="<?= View::e($shareLink) ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button"
                                onclick="document.getElementById('ref-share-link').select();document.execCommand('copy');this.innerHTML='<i class=\'bi bi-check\'></i>';">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                    <small class="text-muted">
                        Kod uzyty: <strong><?= $timesUsed ?></strong> razy.
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-gift me-1"></i> Twoja nagroda</h5>
                    <?php if ($rewardCfg): ?>
                        <p class="mb-2"><strong><?= View::e($rewardCfg['name']) ?></strong></p>
                        <p class="text-muted small mb-2"><?= View::e($rewardCfg['description'] ?? '') ?></p>
                        <ul class="list-unstyled small mb-0">
                            <li><strong>Typ:</strong> <?= View::e($rewardCfg['reward_type']) ?></li>
                            <li><strong>Wartosc:</strong>
                                <?= View::e((string)$rewardCfg['reward_value']) ?>
                                <?php if ($rewardCfg['reward_type'] === 'discount'): ?>%<?php endif; ?>
                                <?php if ($rewardCfg['reward_type'] === 'months_free'): ?> miesiacy<?php endif; ?>
                                <?php if ($rewardCfg['reward_type'] === 'credit'): ?> PLN<?php endif; ?>
                            </li>
                            <li><strong>Min. miesiecy oplaconych:</strong>
                                <?= (int)$rewardCfg['min_paid_months'] ?>
                            </li>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">Brak aktywnego programu polecen.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <?php
        $tiles = [
            ['Polecone (lacznie)', (int)$stats['total'],     'people',         'primary'],
            ['Pending',            (int)$stats['pending'],   'hourglass-split','secondary'],
            ['Aktywne (qualified)',(int)$stats['qualified'], 'patch-check',    'success'],
            ['Total reward',       number_format($totalEarn,2,',',' '), 'wallet2','warning'],
        ];
        foreach ($tiles as [$label, $val, $icon, $color]):
        ?>
            <div class="col-md-3 col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-<?= $icon ?> fs-3 text-<?= $color ?>"></i>
                        <div class="text-muted small mt-2"><?= View::e($label) ?></div>
                        <div class="h4 mb-0"><?= is_string($val) ? View::e($val) : $val ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="bi bi-list-ul me-1"></i> Polecone kluby</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($referrals)): ?>
                <div class="p-4 text-center text-muted">
                    Brak polecen. Udostepnij swoj kod by zaczac zarabiac rabaty.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Klub</th>
                                <th>Miasto</th>
                                <th>Data polecenia</th>
                                <th>Status</th>
                                <th>Reward</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referrals as $r): ?>
                                <?php
                                $st = (string)$r['status'];
                                [$slabel, $scolor] = $statusLabel[$st] ?? [$st, 'secondary'];
                                ?>
                                <tr>
                                    <td><?= View::e($r['referred_club_name'] ?? '(usuniety)') ?></td>
                                    <td><?= View::e($r['referred_club_city'] ?? '-') ?></td>
                                    <td><?= View::e($r['referred_at']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $scolor ?>"><?= View::e($slabel) ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($r['reward_value'])): ?>
                                            <?= View::e((string)$r['reward_value']) ?>
                                            <small class="text-muted">(<?= View::e((string)$r['reward_type']) ?>)</small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
