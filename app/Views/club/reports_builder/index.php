<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-tools text-primary"></i> Kreator raportów</h1>
        <p class="text-muted mb-0">Buduj własne raporty bez znajomości SQL — przeciągnij i upuść kolumny, filtry i wykresy.</p>
    </div>
    <a href="<?= url('club/reports-builder/new') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nowy raport
    </a>
</div>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success"><?= View::e($flashSuccess) ?></div>
<?php endif; ?>
<?php if (!empty($flashError)): ?>
    <div class="alert alert-danger"><?= View::e($flashError) ?></div>
<?php endif; ?>

<?php
$ownReports     = array_filter($reports, fn($r) => (int)($r['is_template'] ?? 0) === 0);
$templateReports = array_filter($reports, fn($r) => (int)($r['is_template'] ?? 0) === 1);
?>

<div class="card mb-4">
    <div class="card-header bg-light"><strong>Twoje raporty</strong></div>
    <div class="card-body p-0">
        <?php if (empty($ownReports)): ?>
            <div class="p-4 text-center text-muted">
                Brak zapisanych raportów. <a href="<?= url('club/reports-builder/new') ?>">Utwórz pierwszy raport</a>.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nazwa</th>
                            <th>Źródło</th>
                            <th>Autor</th>
                            <th>Ostatnio uruchomiony</th>
                            <th>Wykonań</th>
                            <th>Współdzielony?</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ownReports as $r): ?>
                        <tr>
                            <td>
                                <strong><?= View::e($r['name']) ?></strong>
                                <?php if (!empty($r['description'])): ?>
                                    <div class="small text-muted"><?= View::e($r['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary"><?= View::e($dataSources[$r['data_source']]['label'] ?? $r['data_source']) ?></span></td>
                            <td><?= View::e($r['author_name'] ?? '—') ?></td>
                            <td><?= !empty($r['last_run_at']) ? View::e($r['last_run_at']) : '<span class="text-muted">—</span>' ?></td>
                            <td><?= (int)$r['run_count'] ?></td>
                            <td>
                                <?= (int)$r['is_shared'] === 1
                                    ? '<i class="bi bi-people text-success" title="Współdzielony"></i>'
                                    : '<i class="bi bi-person text-muted" title="Prywatny"></i>' ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('club/reports-builder/' . (int)$r['id'] . '/run') ?>" class="btn btn-sm btn-primary"
                                   title="Uruchom"><i class="bi bi-play-fill"></i></a>
                                <a href="<?= url('club/reports-builder/' . (int)$r['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary"
                                   title="Edytuj"><i class="bi bi-pencil"></i></a>
                                <form method="post" action="<?= url('club/reports-builder/' . (int)$r['id'] . '/delete') ?>"
                                      class="d-inline" onsubmit="return confirm('Usunąć raport &quot;<?= View::e($r['name']) ?>&quot;?');">
                                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                                    <button class="btn btn-sm btn-outline-danger" title="Usuń"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($templateReports)): ?>
<div class="card">
    <div class="card-header bg-light"><strong><i class="bi bi-stars"></i> Gotowe szablony</strong>
        <span class="text-muted small ms-2">Predefiniowane raporty — uruchom lub skopiuj jako własny.</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Nazwa</th><th>Źródło</th><th>Opis</th><th class="text-end">Akcje</th></tr></thead>
                <tbody>
                <?php foreach ($templateReports as $r): ?>
                    <tr>
                        <td><strong><?= View::e($r['name']) ?></strong></td>
                        <td><span class="badge bg-info"><?= View::e($dataSources[$r['data_source']]['label'] ?? $r['data_source']) ?></span></td>
                        <td class="small text-muted"><?= View::e($r['description'] ?? '') ?></td>
                        <td class="text-end">
                            <a href="<?= url('club/reports-builder/' . (int)$r['id'] . '/run') ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-play-fill"></i> Uruchom
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
