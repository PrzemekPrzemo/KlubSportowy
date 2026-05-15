<?php
use App\Helpers\View;
use App\Models\LegalDocumentModel;
/** @var string $docType */
/** @var string $label */
/** @var array $versions */
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div>
        <a href="<?= url('admin/platform/legal-docs') ?>" class="text-muted small">
            <i class="bi bi-arrow-left"></i> Wszystkie dokumenty
        </a>
        <h1 class="h4 mb-0 mt-1"><?= View::e($label) ?> — wersje</h1>
    </div>
    <a href="<?= url('admin/platform/legal-docs/' . $docType . '/new') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Nowa wersja
    </a>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
        <tr>
            <th>Wersja</th>
            <th>Tytuł</th>
            <th>Obowiązuje od</th>
            <th>Utworzono</th>
            <th>Status</th>
            <th class="text-end">Podgląd</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$versions): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Brak wersji. Opublikuj pierwszą wersję.</td></tr>
        <?php endif; ?>
        <?php foreach ($versions as $v): ?>
            <tr>
                <td><code><?= View::e($v['version']) ?></code></td>
                <td><?= View::e($v['title']) ?></td>
                <td><?= View::e(date('d.m.Y', strtotime((string)$v['effective_from']))) ?></td>
                <td><small class="text-muted"><?= View::e(date('d.m.Y H:i', strtotime((string)$v['created_at']))) ?></small></td>
                <td>
                    <?php if (!empty($v['is_current'])): ?>
                        <span class="badge bg-success">aktywna</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">archiwalna</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <a href="<?= url('legal/' . LegalDocumentModel::typeToSlug($docType) . '/v/' . $v['version']) ?>"
                       target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-eye"></i> Zobacz
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
