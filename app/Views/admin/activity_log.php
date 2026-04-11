<?php use App\Helpers\View; ?>
<p class="text-muted">Ostatnie 100 akcji w systemie (audyt).</p>
<div class="card">
    <table class="table table-sm table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Czas</th><th>Użytkownik</th><th>Klub</th><th>Akcja</th>
                <th>Encja</th><th>ID</th><th>Szczegóły</th><th>IP</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($recent)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Log pusty.</td></tr>
        <?php else: ?>
            <?php foreach ($recent as $r): ?>
                <tr>
                    <td><small><?= format_datetime($r['created_at']) ?></small></td>
                    <td><small><?= View::e($r['full_name'] ?? $r['username'] ?? '—') ?></small></td>
                    <td><small><?= View::e($r['club_name'] ?? '—') ?></small></td>
                    <td><code><?= View::e($r['action']) ?></code></td>
                    <td><small><?= View::e($r['entity'] ?? '') ?></small></td>
                    <td><small><?= $r['entity_id'] !== null ? (int)$r['entity_id'] : '' ?></small></td>
                    <td><small><?= View::e($r['details'] ?? '') ?></small></td>
                    <td><small><?= View::e($r['ip_address'] ?? '') ?></small></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
