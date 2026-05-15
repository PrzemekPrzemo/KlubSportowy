<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-calendar-check"></i> Moje rezerwacje</h4>
    <a href="<?= url('portal/bookings/new') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle"></i> Nowa rezerwacja
    </a>
</div>

<?php $rows = $pagination['data'] ?? []; ?>
<?php if (empty($rows)): ?>
    <div class="card p-4 text-center text-muted">Brak rezerwacji.</div>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Zasób</th>
                        <th>Tytuł</th>
                        <th>Termin</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $b): ?>
                        <tr>
                            <td><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:<?= View::e($b['resource_color']) ?>"></span>
                                <?= View::e($b['resource_name']) ?></td>
                            <td><?= View::e($b['title']) ?></td>
                            <td><small><?= View::e($b['start_at']) ?><br><?= View::e($b['end_at']) ?></small></td>
                            <td><span class="badge bg-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'pending' ? 'warning text-dark' : 'secondary') ?>"><?= View::e($b['status']) ?></span></td>
                            <td class="text-end">
                                <?php if (!in_array($b['status'], ['cancelled','completed'], true) && strtotime($b['start_at']) > time()): ?>
                                    <form method="POST" action="<?= url('portal/bookings/' . (int)$b['id'] . '/cancel') ?>"
                                          onsubmit="return confirm('Anulować?')" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
