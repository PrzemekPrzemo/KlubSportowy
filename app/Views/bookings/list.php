<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-list-ul"></i> Lista rezerwacji</h4>
    <div class="d-flex gap-2">
        <a href="<?= url('bookings') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-calendar-week"></i> Kalendarz</a>
        <a href="<?= url('bookings/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Nowa rezerwacja</a>
    </div>
</div>

<form method="get" class="mb-3 d-flex gap-2">
    <select name="status" class="form-select form-select-sm" style="width:auto;">
        <option value="">— status —</option>
        <?php foreach (['pending','confirmed','cancelled','completed','no_show'] as $s): ?>
            <option value="<?= $s ?>" <?= ($status ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-outline-secondary btn-sm">Filtruj</button>
</form>

<?php $rows = $pagination['data'] ?? []; ?>
<?php if (empty($rows)): ?>
    <div class="card p-4 text-center text-muted">Brak rezerwacji.</div>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Zasób</th>
                        <th>Tytuł</th>
                        <th>Termin</th>
                        <th>Kto</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $b): ?>
                        <tr>
                            <td><?= (int)$b['id'] ?></td>
                            <td><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:<?= View::e($b['resource_color']) ?>"></span>
                                <?= View::e($b['resource_name']) ?></td>
                            <td><?= View::e($b['title']) ?></td>
                            <td><small><?= View::e($b['start_at']) ?><br><?= View::e($b['end_at']) ?></small></td>
                            <td><?= View::e(trim(($b['member_first'] ?? '') . ' ' . ($b['member_last'] ?? ''))) ?: '—' ?></td>
                            <td><span class="badge bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning text-dark' : 'secondary') ?>"><?= View::e($b['status']) ?></span></td>
                            <td class="text-end">
                                <a href="<?= url('bookings/' . (int)$b['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php $last = $pagination['last_page'] ?? 1; $cur = $pagination['current_page'] ?? 1; ?>
    <?php if ($last > 1): ?>
    <nav class="mt-3"><ul class="pagination pagination-sm">
        <?php for ($p = 1; $p <= $last; $p++): ?>
            <li class="page-item <?= $p === $cur ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $p ?>&status=<?= View::e($status ?? '') ?>"><?= $p ?></a>
            </li>
        <?php endfor; ?>
    </ul></nav>
    <?php endif; ?>
<?php endif; ?>
