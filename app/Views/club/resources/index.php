<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-box-seam"></i> Zasoby do rezerwacji</h4>
    <a href="<?= url('club/resources/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle"></i> Dodaj zasób
    </a>
</div>

<?php if (empty($resources)): ?>
    <div class="card p-4 text-center text-muted">
        Brak zasobów. Dodaj salę, kort, sprzęt lub boisko, aby umożliwić rezerwacje.
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Nazwa</th>
                        <th>Typ</th>
                        <th>Pojemność</th>
                        <th>Godziny</th>
                        <th>Dni</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resources as $r): ?>
                        <tr>
                            <td><span class="d-inline-block" style="width:18px;height:18px;border-radius:4px;background:<?= View::e($r['color']) ?>"></span></td>
                            <td><strong><?= View::e($r['name']) ?></strong>
                                <?php if (!empty($r['location'])): ?><br><small class="text-muted"><?= View::e($r['location']) ?></small><?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary"><?= View::e($r['type']) ?></span></td>
                            <td><?= $r['capacity'] ? (int)$r['capacity'] : '—' ?></td>
                            <td><?= !empty($r['available_from']) ? substr($r['available_from'],0,5) . ' — ' . substr($r['available_until'] ?? '23:59',0,5) : '00:00 — 23:59' ?></td>
                            <td><small><?= View::e($r['available_weekdays']) ?></small></td>
                            <td>
                                <?php if ((int)$r['is_active'] === 1): ?>
                                    <span class="badge bg-success">aktywny</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">nieaktywny</span>
                                <?php endif; ?>
                                <?php if (!empty($r['requires_approval'])): ?>
                                    <span class="badge bg-warning text-dark">wymaga akceptacji</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= url('club/resources/' . (int)$r['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ((int)$r['is_active'] === 1): ?>
                                <form method="POST" action="<?= url('club/resources/' . (int)$r['id'] . '/delete') ?>"
                                      onsubmit="return confirm('Dezaktywować zasób?')" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-archive"></i></button>
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

<div class="mt-3">
    <a href="<?= url('bookings') ?>" class="btn btn-outline-primary"><i class="bi bi-calendar-week"></i> Kalendarz rezerwacji</a>
    <a href="<?= url('bookings/list') ?>" class="btn btn-outline-secondary"><i class="bi bi-list-ul"></i> Lista rezerwacji</a>
</div>
