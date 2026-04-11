<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <h5 class="mb-0"><?= View::e($club['name']) ?></h5>
    <small class="text-muted"><?= View::e($club['city'] ?? '') ?></small>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr><th>Imię i nazwisko</th><th>E-mail</th><th>Rola</th><th></th></tr>
        </thead>
        <tbody>
        <?php if (empty($users)): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">Brak użytkowników.</td></tr>
        <?php else: ?>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= View::e($u['full_name']) ?></td>
                    <td><small><?= View::e($u['email']) ?></small></td>
                    <td><span class="badge bg-secondary"><?= View::e($u['role']) ?></span></td>
                    <td class="text-end">
                        <form method="POST" action="<?= url('admin/clubs/' . (int)$club['id'] . '/users/' . (int)$u['user_id'] . '/impersonate') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-warning" title="Zaloguj jako ten użytkownik">
                                <i class="bi bi-person-fill-lock"></i> Impersonuj
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="mt-3">
    <a href="<?= url('admin/clubs') ?>" class="btn btn-outline-secondary">&larr; Wróć do listy klubów</a>
</div>
