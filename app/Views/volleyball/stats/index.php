<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-md-4">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-lightning"></i> Top zabójcze ataki</h5>
            <?php if (empty($topKillers)): ?>
                <div class="text-muted small">Brak danych.</div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>Zawodnik</th><th>Mecze</th><th>Kills</th></tr></thead>
                    <tbody>
                    <?php foreach ($topKillers as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></td>
                            <td><?= (int)$p['matches_played'] ?></td>
                            <td><strong><?= (int)($p['total_value'] ?? 0) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-shield"></i> Top bloki</h5>
            <?php if (empty($topBlockers)): ?>
                <div class="text-muted small">Brak danych.</div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>Zawodnik</th><th>Mecze</th><th>Bloki</th></tr></thead>
                    <tbody>
                    <?php foreach ($topBlockers as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></td>
                            <td><?= (int)$p['matches_played'] ?></td>
                            <td><strong><?= (int)($p['total_value'] ?? 0) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-bullseye"></i> Top asy serwisowe</h5>
            <?php if (empty($topServers)): ?>
                <div class="text-muted small">Brak danych.</div>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead><tr><th>#</th><th>Zawodnik</th><th>Mecze</th><th>Asy</th></tr></thead>
                    <tbody>
                    <?php foreach ($topServers as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></td>
                            <td><?= (int)$p['matches_played'] ?></td>
                            <td><strong><?= (int)($p['total_value'] ?? 0) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
