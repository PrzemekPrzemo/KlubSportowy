<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <form method="GET" class="row g-2">
        <div class="col-md-4">
            <input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control" placeholder="Szukaj po nazwisku, e-mailu, numerze...">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">— status —</option>
                <?php foreach (['aktywny','zawieszony','wykreslony','urlop'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($status ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="sport" class="form-select">
                <option value="">— sekcja —</option>
                <?php foreach (($clubSports ?? []) as $cs): ?>
                    <option value="<?= (int)$cs['club_sport_id'] ?>" <?= (int)($sportFilter ?? 0) === (int)$cs['club_sport_id'] ? 'selected' : '' ?>>
                        <?= View::e($cs['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary flex-fill"><i class="bi bi-search"></i></button>
            <a href="<?= url('members/create') ?>" class="btn btn-success flex-fill"><i class="bi bi-plus"></i></a>
        </div>
    </form>
</div>

<div class="card">
    <table class="table table-hover mb-0">
        <thead class="table-light">
            <tr>
                <th>Nr</th>
                <th>Nazwisko i imię</th>
                <th>E-mail</th>
                <th>Telefon</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pagination['data'])): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Brak zawodników.</td></tr>
            <?php else: ?>
                <?php foreach ($pagination['data'] as $m): ?>
                    <tr>
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

<?php if (!empty($pagination['last_page']) && $pagination['last_page'] > 1): ?>
    <nav class="mt-3"><ul class="pagination">
    <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
        <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($q ?? '') ?>&status=<?= urlencode($status ?? '') ?>"><?= $i ?></a>
        </li>
    <?php endfor; ?>
    </ul></nav>
<?php endif; ?>
