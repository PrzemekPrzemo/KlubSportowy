<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control" placeholder="<?= View::e(__('members.search_placeholder')) ?>">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value=""><?= __('members.status_filter') ?></option>
                <?php foreach (['aktywny','zawieszony','wykreslony','urlop'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($status ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="sport" class="form-select">
                <option value=""><?= __('members.section_filter') ?></option>
                <?php foreach (($clubSports ?? []) as $cs): ?>
                    <option value="<?= (int)$cs['club_sport_id'] ?>" <?= (int)($sportFilter ?? 0) === (int)$cs['club_sport_id'] ? 'selected' : '' ?>>
                        <?= View::e($cs['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary flex-fill"><i class="bi bi-search"></i></button>
            <a href="<?= url('members/create') ?>" class="btn btn-success flex-fill" title="<?= View::e(__('members.new')) ?>"><i class="bi bi-plus"></i></a>
            <a href="<?= url('import') ?>" class="btn btn-outline-primary flex-fill" title="<?= View::e(__('members.import_csv')) ?>"><i class="bi bi-upload"></i></a>
        </div>
    </form>
</div>

<form method="POST" action="<?= url('members/bulk') ?>" id="bulk-form">
    <?= csrf_field() ?>
    <div class="card">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:40px"><input type="checkbox" id="select-all" class="form-check-input"></th>
                    <th><?= __('members.col_number') ?></th>
                    <th><?= __('members.col_name') ?></th>
                    <th><?= __('members.col_email') ?></th>
                    <th><?= __('members.col_phone') ?></th>
                    <th><?= __('members.col_status') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pagination['data'])): ?>
                    <tr><td colspan="7" class="p-0">
                        <?php
                        $icon       = 'bi-people';
                        $title      = 'Brak zawodników w klubie';
                        $message    = 'Dodaj pierwszego zawodnika ręcznie lub zaimportuj listę z CSV. Zawodnicy mogą logować się do portalu i opłacać składki online.';
                        $actionUrl  = url('members/create');
                        $actionLabel= '+ Dodaj zawodnika';
                        include __DIR__ . '/../_partials/empty_state.php';
                        ?>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($pagination['data'] as $m): ?>
                        <tr>
                            <td><input type="checkbox" name="member_ids[]" value="<?= (int)$m['id'] ?>" class="form-check-input row-checkbox"></td>
                            <td><code><?= View::e($m['member_number']) ?></code></td>
                            <td>
                                <a href="<?= url('members/' . (int)$m['id']) ?>">
                                    <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                                </a>
                            </td>
                            <td><?= View::e($m['email'] ?? '') ?></td>
                            <td><?= View::e($m['phone'] ?? '') ?></td>
                            <td><span class="badge bg-<?= $m['status']==='aktywny' ? 'success' : 'secondary' ?>"><?= View::e($m['status']) ?></span></td>
                            <td class="text-end">
                                <a href="<?= url('members/' . (int)$m['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center gap-2 mt-3">
        <select name="action" class="form-select" style="max-width:250px;">
            <option value=""><?= __('members.bulk_choose') ?></option>
            <option value="activate"><?= __('members.bulk_activate') ?></option>
            <option value="suspend"><?= __('members.bulk_suspend') ?></option>
            <option value="delete"><?= __('members.bulk_delete') ?></option>
            <option value="export_csv"><?= __('members.bulk_export_csv') ?></option>
        </select>
        <button type="submit" class="btn btn-outline-primary"><?= __('members.bulk_submit') ?></button>
    </div>
</form>

<?php if (!empty($pagination['last_page']) && $pagination['last_page'] > 1): ?>
    <nav class="mt-3"><ul class="pagination">
    <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
        <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($q ?? '') ?>&status=<?= urlencode($status ?? '') ?>"><?= $i ?></a>
        </li>
    <?php endfor; ?>
    </ul></nav>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectAll = document.getElementById('select-all');
    if (!selectAll) return;
    selectAll.addEventListener('change', function() {
        var boxes = document.querySelectorAll('.row-checkbox');
        for (var i = 0; i < boxes.length; i++) {
            boxes[i].checked = selectAll.checked;
        }
    });
});
</script>
