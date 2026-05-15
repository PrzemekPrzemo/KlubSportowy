<?php
use App\Helpers\View;
use App\Models\LegalDocumentModel;
/** @var array $rows */
?>
<a href="<?= url('admin/platform/legal-docs') ?>" class="text-muted small mb-2 d-inline-block">
    <i class="bi bi-arrow-left"></i> Powrót do dokumentów
</a>
<h1 class="h4 mb-3"><i class="bi bi-list-check me-2"></i> Log akceptacji dokumentów</h1>
<p class="text-muted small">Ostatnie 500 zapisów. Logi służą jako dowód udzielenia zgody (RODO + art. 8 ust. 4 UŚUDE).</p>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 small">
            <thead class="table-light">
            <tr>
                <th>Data</th>
                <th>Dokument</th>
                <th>Wersja</th>
                <th>Kontekst</th>
                <th>Użytkownik</th>
                <th>Klub</th>
                <th>IP</th>
                <th>User-Agent</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Brak zapisów akceptacji.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= View::e(date('d.m.Y H:i:s', strtotime((string)$r['accepted_at']))) ?></td>
                    <td><?= View::e(LegalDocumentModel::typeLabel((string)$r['doc_type'])) ?></td>
                    <td><code><?= View::e($r['version']) ?></code></td>
                    <td><span class="badge bg-light text-dark"><?= View::e($r['context']) ?></span></td>
                    <td><?= $r['user_email'] ? View::e((string)$r['user_email']) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= $r['club_name'] ? View::e((string)$r['club_name']) : '<span class="text-muted">—</span>' ?></td>
                    <td><code><?= View::e($r['ip_address'] ?? '') ?></code></td>
                    <td class="text-muted" style="max-width:240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                        title="<?= View::e($r['user_agent'] ?? '') ?>">
                        <?= View::e($r['user_agent'] ?? '') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
