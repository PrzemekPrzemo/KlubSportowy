<?php
/**
 * Dashboard biura (sekretariat) — widok zarząd/księgowy.
 *
 * @var array $tiles
 * @var array $aging
 * @var array $medicalCounts
 * @var array $activityFeed
 */
use App\Helpers\View;

$tiles         = $tiles         ?? [];
$aging         = $aging         ?? [];
$medicalCounts = $medicalCounts ?? ['in_30_days' => 0, 'in_14_days' => 0, 'in_7_days' => 0];
$activityFeed  = $activityFeed  ?? [];

$fmt = static function (float $v): string {
    return number_format($v, 2, ',', ' ') . ' zł';
};
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-0"><i class="bi bi-building"></i> Dashboard biura</h1>
        <small class="text-muted">Pulpit operacyjny dla sekretariatu (księgowy / zarząd).</small>
    </div>
    <div class="btn-group">
        <a href="<?= url('members/create?from=sekretariat') ?>" class="btn btn-primary">
            <i class="bi bi-person-plus"></i> Dodaj członka
        </a>
        <a href="<?= url('club/members/import') ?>" class="btn btn-outline-primary">
            <i class="bi bi-upload"></i> Import CSV
        </a>
        <a href="<?= url('club/invoices/create') ?>" class="btn btn-outline-primary">
            <i class="bi bi-receipt"></i> Wystaw fakturę
        </a>
        <a href="<?= url('members/export') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-download"></i> Eksport CSV
        </a>
    </div>
</div>

<!-- ─────────────────────────────────────────────────────────────── -->
<!-- Kafelki: zadania dnia                                            -->
<!-- ─────────────────────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-3">
        <a href="<?= url('members?status=aktywny') ?>" class="text-decoration-none text-dark">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small"><i class="bi bi-person-plus text-primary"></i> Nowi członkowie</div>
                    <div class="h3 mb-0"><?= (int)$tiles['pending_members'] ?></div>
                    <small class="text-muted">Dodani w ostatnich 7 dniach</small>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-4 col-lg-3">
        <a href="<?= url('club/invoices/bulk-from-payments') ?>" class="text-decoration-none text-dark">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small"><i class="bi bi-receipt text-warning"></i> Wpłaty bez faktury</div>
                    <div class="h3 mb-0"><?= (int)$tiles['payments_without_invoice'] ?></div>
                    <small class="text-muted">Do wystawienia FV</small>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-4 col-lg-3">
        <a href="<?= url('fees') ?>" class="text-decoration-none text-dark">
            <div class="card h-100 <?= $tiles['overdue_count'] > 0 ? 'border-danger' : '' ?>">
                <div class="card-body">
                    <div class="text-muted small"><i class="bi bi-exclamation-triangle text-danger"></i> Zaległe składki</div>
                    <div class="h3 mb-0 text-danger"><?= $fmt((float)$tiles['overdue_amount']) ?></div>
                    <small class="text-muted"><?= (int)$tiles['overdue_count'] ?> należności</small>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-4 col-lg-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small"><i class="bi bi-heart-pulse text-danger"></i> Badania medyczne</div>
                <div class="h3 mb-0"><?= (int)$medicalCounts['in_30_days'] ?></div>
                <small class="text-muted">
                    wygasają w 30 dni
                    (<?= (int)$medicalCounts['in_14_days'] ?> / 14d,
                    <?= (int)$medicalCounts['in_7_days'] ?> / 7d)
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-lg-3">
        <a href="<?= url('club/invoices?status=issued') ?>" class="text-decoration-none text-dark">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small"><i class="bi bi-receipt-cutoff text-info"></i> Niezapłacone faktury</div>
                    <div class="h3 mb-0"><?= (int)$tiles['invoices_unpaid_count'] ?></div>
                    <small class="text-muted">Saldo: <?= $fmt((float)$tiles['invoices_outstanding']) ?></small>
                </div>
            </div>
        </a>
    </div>

    <div class="col-md-4 col-lg-3">
        <a href="<?= url('messages') ?>" class="text-decoration-none text-dark">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small"><i class="bi bi-envelope-open text-secondary"></i> Korespondencja</div>
                    <div class="h3 mb-0"><?= (int)$tiles['draft_campaigns'] ?></div>
                    <small class="text-muted">Drafty kampanii do wysłania</small>
                </div>
            </div>
        </a>
    </div>

    <?php if (!empty($tiles['pending_docs'])): ?>
    <div class="col-md-4 col-lg-3">
        <a href="<?= url('documents') ?>" class="text-decoration-none text-dark">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small"><i class="bi bi-file-earmark-text text-secondary"></i> Dokumenty</div>
                    <div class="h3 mb-0"><?= (int)$tiles['pending_docs'] ?></div>
                    <small class="text-muted">Do podpisu / akceptacji</small>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="row g-3">
    <!-- ─────────────────────────────────────────────────────── -->
    <!-- TOP 10 zaległości z aging buckets                       -->
    <!-- ─────────────────────────────────────────────────────── -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-list-ol"></i> Top 10 zaległości — aging</strong>
                <a href="<?= url('fees') ?>" class="btn btn-sm btn-outline-secondary">Wszystkie</a>
            </div>
            <?php if (empty($aging)): ?>
                <div class="card-body text-muted">
                    <i class="bi bi-check-circle text-success"></i> Brak zaległości — wszystko opłacone.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Członek</th>
                                <th>Termin</th>
                                <th>Wiek</th>
                                <th>Saldo</th>
                                <th>Akcja</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($aging as $a):
                            $bucketBg = match ($a['aging_bucket']) {
                                '0-30'  => 'bg-warning-subtle',
                                '31-60' => 'bg-warning',
                                '61-90' => 'bg-danger-subtle',
                                default => 'bg-danger text-white',
                            };
                        ?>
                            <tr>
                                <td>
                                    <a href="<?= url('members/' . (int)$a['member_id']) ?>">
                                        <?= View::e(($a['last_name'] ?? '') . ' ' . ($a['first_name'] ?? '')) ?>
                                    </a>
                                    <?php if (!empty($a['member_number'])): ?>
                                        <small class="text-muted">#<?= View::e((string)$a['member_number']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= View::e((string)($a['due_date'] ?? '')) ?></td>
                                <td><span class="badge <?= $bucketBg ?>"><?= View::e((string)$a['aging_bucket']) ?> dni</span></td>
                                <td><strong><?= $fmt((float)$a['outstanding']) ?></strong></td>
                                <td>
                                    <a href="<?= url('members/' . (int)$a['member_id']) ?>"
                                       class="btn btn-xs btn-outline-primary" title="Profil">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────── -->
    <!-- Recent activity (tenant_access_log)                     -->
    <!-- ─────────────────────────────────────────────────────── -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><strong><i class="bi bi-clock-history"></i> Ostatnia aktywność księgowa</strong></div>
            <?php if (empty($activityFeed)): ?>
                <div class="card-body text-muted">
                    Brak wpisów w dzienniku.
                </div>
            <?php else: ?>
                <ul class="list-group list-group-flush small">
                    <?php foreach ($activityFeed as $a):
                        $sev = (string)($a['severity'] ?? 'info');
                        $sevClass = match ($sev) {
                            'critical' => 'text-danger',
                            'warning'  => 'text-warning',
                            default    => 'text-muted',
                        };
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <span class="<?= $sevClass ?>">
                                    <i class="bi bi-dot"></i>
                                    <?= View::e((string)($a['operation'] ?? '')) ?>
                                </span>
                                <small class="text-muted d-block">
                                    <?= View::e((string)($a['table_name'] ?? '')) ?>
                                    <?php if (!empty($a['context'])): ?>
                                        — <?= View::e(mb_substr((string)$a['context'], 0, 80)) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <small class="text-muted text-nowrap ms-2">
                                <?= View::e((string)($a['created_at'] ?? '')) ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
