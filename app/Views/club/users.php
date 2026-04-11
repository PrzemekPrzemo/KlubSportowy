<?php use App\Helpers\View; ?>
<div class="row g-3">
    <div class="col-md-7">
        <div class="card p-3">
            <h5 class="mb-3">Użytkownicy (<?= count($users) ?>)</h5>
            <?php if (empty($users)): ?>
                <div class="text-muted">Brak użytkowników.</div>
            <?php else: ?>
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>Imię i nazwisko</th><th>E-mail</th><th>Rola</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= View::e($u['full_name']) ?></td>
                            <td><small><?= View::e($u['email']) ?></small></td>
                            <td><span class="badge bg-secondary"><?= View::e($u['role']) ?></span></td>
                            <td class="text-end">
                                <form method="POST" action="<?= url('club/users/' . (int)$u['user_id'] . '/revoke') ?>" onsubmit="return confirm('Odebrać rolę?')" class="m-0">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="role" value="<?= View::e($u['role']) ?>">
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card p-3">
            <h5 class="mb-3">Dodaj użytkownika</h5>
            <form method="POST" action="<?= url('club/users/add') ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label small">E-mail</label>
                    <input type="email" name="email" class="form-control" required>
                    <small class="text-muted">Jeśli konto istnieje, dodamy tylko rolę.</small>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Imię i nazwisko</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Rola</label>
                    <select name="role" class="form-select">
                        <?php foreach (['zarzad','trener','instruktor','sedzia','lekarz','ksiegowy'] as $r): ?>
                            <option value="<?= $r ?>"><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">Hasło startowe (min 8 znaków)</label>
                    <input type="password" name="password" class="form-control" required minlength="8">
                </div>
                <button class="btn btn-primary w-100"><i class="bi bi-plus"></i> Dodaj</button>
            </form>
        </div>
    </div>
</div>
