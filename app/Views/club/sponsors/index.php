<?php
use App\Helpers\View;

$tierBadge = static function (string $tier): string {
    return match ($tier) {
        'platinum' => '<span class="badge" style="background:#e5e4e2;color:#333;border:1px solid #aaa;">Platinum</span>',
        'gold'     => '<span class="badge" style="background:#d4af37;color:#fff;">Gold</span>',
        'silver'   => '<span class="badge" style="background:#c0c0c0;color:#333;">Silver</span>',
        'bronze'   => '<span class="badge" style="background:#cd7f32;color:#fff;">Bronze</span>',
        default    => '<span class="badge bg-secondary">Partner</span>',
    };
};
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><i class="bi bi-award me-2"></i> Sponsorzy</h2>
    <a href="<?= url('club/sponsors/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Dodaj sponsora
    </a>
</div>

<!-- Statystyki -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small">Aktywnych</div>
            <div class="fs-3 fw-bold"><?= (int)$stats['active'] ?> / <?= (int)$stats['total'] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small">Kontrakty kończące się (30 dni)</div>
            <div class="fs-3 fw-bold text-warning"><?= (int)$stats['expiring_soon'] ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small">Łączna wartość kontraktów</div>
            <div class="fs-3 fw-bold text-success">
                <?= number_format((float)$stats['total_value'], 2, ',', ' ') ?> PLN
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <div class="text-muted small">Akcje</div>
            <a href="<?= url('club/sponsors?expiring=1') ?>" class="btn btn-sm btn-outline-warning mt-1">
                <i class="bi bi-clock-history"></i> Pokaż kończące się
            </a>
        </div>
    </div>
</div>

<!-- Filtry -->
<form method="GET" action="<?= url('club/sponsors') ?>" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small mb-1">Tier</label>
            <select name="tier" class="form-select form-select-sm">
                <option value="">— wszystkie —</option>
                <?php foreach ($tiers as $tk => $tl): ?>
                    <option value="<?= View::e($tk) ?>" <?= $tierFilter === $tk ? 'selected' : '' ?>>
                        <?= View::e($tl) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <div class="form-check mt-3">
                <input type="checkbox" id="f_expiring" name="expiring" value="1" class="form-check-input"
                       <?= !empty($_GET['expiring']) ? 'checked' : '' ?>>
                <label for="f_expiring" class="form-check-label">Tylko kończące się (30 dni)</label>
            </div>
        </div>
        <div class="col-md-3">
            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i> Filtruj</button>
            <a href="<?= url('club/sponsors') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

<!-- Tabela -->
<div class="card p-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width:60px;">Logo</th>
                    <th>Nazwa</th>
                    <th>Tier</th>
                    <th>Wartość</th>
                    <th>Kontrakt do</th>
                    <th>Display</th>
                    <th>Status</th>
                    <th style="width:120px;" class="text-end">Akcje</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($sponsors)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Brak sponsorów. Dodaj pierwszego!</td></tr>
            <?php else: foreach ($sponsors as $s):
                $days = $s['days_to_expiry'] ?? null;
                $expClass = '';
                if ($days !== null) {
                    if ($days < 0) $expClass = 'text-danger fw-bold';
                    elseif ($days <= 30) $expClass = 'text-warning fw-bold';
                }
            ?>
                <tr>
                    <td>
                        <?php if (!empty($s['logo_path'])): ?>
                            <img src="<?= url($s['logo_path']) ?>" alt="<?= View::e($s['name']) ?>"
                                 style="width:40px;height:40px;object-fit:contain;background:#f5f5f7;border-radius:4px;">
                        <?php else: ?>
                            <div style="width:40px;height:40px;background:#e9ecef;border-radius:4px;display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-image text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= View::e($s['name']) ?></strong>
                        <?php if (!empty($s['contact_person'])): ?>
                            <div class="small text-muted"><?= View::e($s['contact_person']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($s['website'])): ?>
                            <div class="small">
                                <a href="<?= View::e($s['website']) ?>" target="_blank" rel="noopener">
                                    <?= View::e($s['website']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= $tierBadge($s['tier'] ?? 'partner') ?></td>
                    <td>
                        <?php if ($s['contract_value'] !== null): ?>
                            <?= number_format((float)$s['contract_value'], 2, ',', ' ') ?> PLN
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($s['contract_end'])): ?>
                            <span class="<?= $expClass ?>">
                                <?= View::e($s['contract_end']) ?>
                                <?php if ($days !== null): ?>
                                    <div class="small">(<?= (int)$days ?> dni)</div>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">bezterminowo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)$s['display_in_portal'] === 1): ?>
                            <span class="badge bg-info" title="Wyświetlany w portalu"><i class="bi bi-globe"></i></span>
                        <?php endif; ?>
                        <?php if ((int)$s['display_in_emails'] === 1): ?>
                            <span class="badge bg-info" title="Wyświetlany w emailach"><i class="bi bi-envelope"></i></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)$s['active'] === 1): ?>
                            <span class="badge bg-success">Aktywny</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Nieaktywny</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= url('club/sponsors/' . (int)$s['id'] . '/edit') ?>"
                           class="btn btn-sm btn-outline-primary" title="Edytuj">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="<?= url('club/sponsors/' . (int)$s['id'] . '/delete') ?>"
                              class="d-inline" onsubmit="return confirm('Na pewno usunąć sponsora <?= View::e($s['name']) ?>?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" title="Usuń">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($expiringSoon) && empty($_GET['expiring'])): ?>
<div class="alert alert-warning mt-4">
    <h6><i class="bi bi-exclamation-triangle"></i> Kontrakty wygasające w 30 dniach</h6>
    <ul class="mb-0">
        <?php foreach ($expiringSoon as $e): ?>
            <li>
                <strong><?= View::e($e['name']) ?></strong> —
                kontrakt do <?= View::e($e['contract_end']) ?> (<?= (int)$e['days_left'] ?> dni)
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
