<?php use App\Helpers\View;
/** @var array $items */
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0"><i class="bi bi-file-earmark-check me-2"></i> Dokumenty prawne</h1>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/platform/legal-docs/acceptances') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-list-check"></i> Log akceptacji
        </a>
        <a href="<?= url('legal') ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-box-arrow-up-right"></i> Strona publiczna
        </a>
    </div>
</div>

<p class="text-muted small">
    Aktualne wersje wszystkich dokumentów prawnych platformy ClubDesk.
    Operator: <strong>Sendormeco Holding Sp. z o.o.</strong>, NIP 5252866457.
</p>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
        <tr>
            <th>Dokument</th>
            <th>Bieżąca wersja</th>
            <th>Obowiązuje od</th>
            <th>Akceptacje</th>
            <th class="text-end">Akcje</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td>
                    <strong><?= View::e($it['label']) ?></strong><br>
                    <small class="text-muted"><?= View::e($it['description']) ?></small>
                </td>
                <td>
                    <?php if ($it['current']): ?>
                        <span class="badge bg-success"><?= View::e($it['current']['version']) ?></span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">brak</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($it['current']['effective_from'])): ?>
                        <?= View::e(date('d.m.Y', strtotime((string)$it['current']['effective_from']))) ?>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><?= (int)$it['acceptances'] ?></td>
                <td class="text-end">
                    <a href="<?= url('admin/platform/legal-docs/' . $it['type']) ?>"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-clock-history"></i> Wersje
                    </a>
                    <a href="<?= url('admin/platform/legal-docs/' . $it['type'] . '/new') ?>"
                       class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg"></i> Nowa wersja
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
