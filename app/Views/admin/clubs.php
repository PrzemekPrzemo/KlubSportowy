<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-6"><input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control" placeholder="Szukaj klubu..."></div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Szukaj</button></div>
        <div class="col-md-3"><a href="<?= url('admin/clubs/create') ?>" class="btn btn-success w-100"><i class="bi bi-plus"></i> Nowy klub</a></div>
    </form>
</div>
<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Nazwa</th><th>Miasto</th><th>E-mail</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($pagination['data'])): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Brak klubów.</td></tr>
        <?php else: ?>
            <?php foreach ($pagination['data'] as $c): ?>
                <tr>
                    <td><strong><?= View::e($c['name']) ?></strong></td>
                    <td><?= View::e($c['city'] ?? '') ?></td>
                    <td><?= View::e($c['email'] ?? '') ?></td>
                    <td>
                        <span class="badge bg-<?= $c['is_active']?'success':'secondary' ?>">
                            <?= $c['is_active']?'aktywny':'nieaktywny' ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <form method="POST" action="<?= url('admin/switch-club/' . (int)$c['id']) ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-primary" title="Wejdź jako">
                                <i class="bi bi-box-arrow-in-right"></i>
                            </button>
                        </form>
                        <a href="<?= url('admin/clubs/' . (int)$c['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
