<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-key-fill me-2"></i>Uprawnienia: <?= View::e($club['name']) ?></h4>
        <small class="text-muted">Matryca <code>can_view</code> / <code>can_edit</code> per rola × moduł dla tego klubu.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/clubs/' . (int)$club['id'] . '/config') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-gear"></i> Ustawienia
        </a>
        <form method="POST" action="<?= url('admin/clubs/' . (int)$club['id'] . '/permissions/reset') ?>"
              onsubmit="return confirm('Usunąć wszystkie nadpisania per-klub i wrócić do domyślnych globalnych?');">
            <?= csrf_field() ?>
            <button class="btn btn-outline-warning btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Reset do globalnych</button>
        </form>
    </div>
</div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    Pola zielone w tle = wartość jest nadpisana per-klub (override). Pozostałe pola korzystają z globalnych domyślnych.
    Zapis zawsze utworzy wiersze override dla wszystkich komórek matrycy — kliknij „Reset do globalnych” żeby wrócić do fallbacku.
</div>

<form method="POST" action="<?= url('admin/clubs/' . (int)$club['id'] . '/permissions') ?>">
    <?= csrf_field() ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 align-middle">
                <thead class="table-light text-center">
                    <tr>
                        <th rowspan="2" style="min-width:110px;">Moduł</th>
                        <?php foreach ($roles as $role): ?>
                            <th colspan="2"><?= View::e(ucfirst($role)) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($roles as $role): ?>
                            <th><small>view</small></th>
                            <th><small>edit</small></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modules as $mKey => $mLabel): ?>
                    <tr>
                        <td class="bg-light"><strong><?= View::e($mLabel) ?></strong><br><small class="text-muted"><?= View::e($mKey) ?></small></td>
                        <?php foreach ($roles as $role):
                            $override = $overrides[$role][$mKey] ?? null;
                            $default  = $defaults[$role][$mKey]  ?? ['view' => 0, 'edit' => 0];
                            $view = $override !== null ? $override['view'] : $default['view'];
                            $edit = $override !== null ? $override['edit'] : $default['edit'];
                            $bgView = $override !== null && $override['view'] !== $default['view'] ? 'bg-success-subtle' : '';
                            $bgEdit = $override !== null && $override['edit'] !== $default['edit'] ? 'bg-success-subtle' : '';
                        ?>
                            <td class="text-center <?= $bgView ?>">
                                <input type="checkbox" class="form-check-input"
                                       name="perm[<?= $role ?>][<?= $mKey ?>][view]" value="1"
                                       <?= $view ? 'checked' : '' ?>>
                            </td>
                            <td class="text-center <?= $bgEdit ?>">
                                <input type="checkbox" class="form-check-input"
                                       name="perm[<?= $role ?>][<?= $mKey ?>][edit]" value="1"
                                       <?= $edit ? 'checked' : '' ?>>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Zapisz uprawnienia
        </button>
    </div>
</form>
