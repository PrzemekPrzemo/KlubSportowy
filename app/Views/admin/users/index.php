<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-shield-fill-check me-2"></i>Super administratorzy</h4>
        <small class="text-muted">Konta z uprawnieniem <code>is_super_admin = 1</code>.</small>
    </div>
    <a href="<?= url('admin/users/create') ?>" class="btn btn-success btn-sm">
        <i class="bi bi-person-plus"></i> Nowy super admin
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Login</th><th>E-mail</th><th>Pełna nazwa</th><th>Telefon</th>
                    <th>Ostatnie logowanie</th><th>Utworzono</th><th>Status</th><th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Brak kont super adminów.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r):
                        $isSelf = (int)$r['id'] === (int)$currentUserId;
                    ?>
                    <tr class="<?= !$r['is_active'] ? 'text-muted' : '' ?>">
                        <td>
                            <code><?= View::e($r['username']) ?></code>
                            <?php if ($isSelf): ?><span class="badge bg-info ms-1">Ty</span><?php endif; ?>
                        </td>
                        <td><small><?= View::e($r['email']) ?></small></td>
                        <td><?= View::e($r['full_name']) ?></td>
                        <td><small><?= View::e($r['phone'] ?? '—') ?></small></td>
                        <td><small><?= $r['last_login'] ? format_datetime($r['last_login']) : '—' ?></small></td>
                        <td><small><?= format_datetime($r['created_at']) ?></small></td>
                        <td>
                            <?php if ($r['is_active']): ?>
                                <span class="badge bg-success">aktywny</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">nieaktywny</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <?php if ($r['is_active'] && !$isSelf): ?>
                                    <form method="POST" action="<?= url('admin/users/' . (int)$r['id'] . '/deactivate') ?>"
                                          onsubmit="return confirm('Dezaktywować konto?');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-warning" title="Dezaktywuj"><i class="bi bi-pause-circle"></i></button>
                                    </form>
                                <?php elseif (!$r['is_active']): ?>
                                    <form method="POST" action="<?= url('admin/users/' . (int)$r['id'] . '/activate') ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-success" title="Aktywuj"><i class="bi bi-play-circle"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="<?= url('admin/users/' . (int)$r['id'] . '/reset-password') ?>"
                                      onsubmit="return confirm('Zresetować hasło? Stare hasło przestanie działać natychmiast.');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-secondary" title="Resetuj hasło"><i class="bi bi-key"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
