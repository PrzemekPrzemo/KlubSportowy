<?php use App\Helpers\View; ?>

<div class="card p-3 mb-3">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">— Wszystkie —</option>
                <?php foreach (['active','trial','suspended','expired','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($status ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small">Szukaj klubu</label>
            <input type="text" name="q" value="<?= View::e($q ?? '') ?>" class="form-control form-control-sm" placeholder="Nazwa klubu...">
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Filtruj</button>
        </div>
        <div class="col-md-3 text-end">
            <a href="<?= url('admin/subscriptions/revenue') ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-graph-up-arrow"></i> Przychody</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Klub</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Ważna do</th>
                    <th>Członkowie</th>
                    <th>Sporty</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Brak subskrypcji.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r):
                    $daysLeft = (int)((strtotime($r['valid_until']) - time()) / 86400);
                    $statusColors = ['active'=>'success','trial'=>'info','suspended'=>'warning','expired'=>'danger','cancelled'=>'secondary'];
                    $badgeColor = $statusColors[$r['status']] ?? 'secondary';
                    $memberLimit = $r['max_members_override'] ?? $r['plan_max_members'] ?? '∞';
                    $sportLimit  = $r['max_sports_override'] ?? $r['plan_max_sports'] ?? '∞';
                ?>
                <tr>
                    <td>
                        <strong><?= View::e($r['club_name']) ?></strong>
                        <div class="small text-muted">#<?= (int)$r['club_id'] ?></div>
                    </td>
                    <td>
                        <form method="POST" action="<?= url('admin/subscriptions/' . (int)$r['id'] . '/plan') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <select name="plan_id" class="form-select form-select-sm d-inline-block" style="width:auto;" onchange="this.form.submit()">
                                <?php foreach ($plans as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>" <?= (int)$r['plan_id'] === (int)$p['id'] ? 'selected' : '' ?>><?= View::e($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td><span class="badge bg-<?= $badgeColor ?>"><?= View::e($r['status']) ?></span></td>
                    <td>
                        <?= View::e($r['valid_until']) ?>
                        <div class="small <?= $daysLeft < 7 ? 'text-danger fw-bold' : 'text-muted' ?>">
                            <?= $daysLeft ?> dni
                        </div>
                    </td>
                    <td><?= (int)$r['member_count'] ?> / <?= $memberLimit ?></td>
                    <td><?= (int)$r['sport_count'] ?> / <?= $sportLimit ?></td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap align-items-center">
                            <!-- Extend -->
                            <form method="POST" action="<?= url('admin/subscriptions/' . (int)$r['id'] . '/extend') ?>" class="d-inline-flex gap-1">
                                <?= csrf_field() ?>
                                <input type="number" name="days" value="30" min="1" max="365" class="form-control form-control-sm" style="width:70px;" title="Dni">
                                <button class="btn btn-sm btn-outline-primary" title="Przedłuż"><i class="bi bi-calendar-plus"></i></button>
                            </form>

                            <!-- Suspend / Activate -->
                            <?php if ($r['status'] !== 'suspended'): ?>
                                <form method="POST" action="<?= url('admin/subscriptions/' . (int)$r['id'] . '/suspend') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-warning" title="Zawieś"><i class="bi bi-pause-circle"></i></button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="<?= url('admin/subscriptions/' . (int)$r['id'] . '/activate') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-success" title="Aktywuj"><i class="bi bi-play-circle"></i></button>
                                </form>
                            <?php endif; ?>

                            <!-- Override modal trigger -->
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#overrideModal<?= (int)$r['id'] ?>" title="Nadpisz limity"><i class="bi bi-sliders"></i></button>
                        </div>

                        <!-- Override Modal -->
                        <div class="modal fade" id="overrideModal<?= (int)$r['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="<?= url('admin/subscriptions/' . (int)$r['id'] . '/override') ?>">
                                        <?= csrf_field() ?>
                                        <div class="modal-header">
                                            <h5 class="modal-title">Nadpisz limity: <?= View::e($r['club_name']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Max członkowie (override)</label>
                                                <input type="number" name="max_members_override" class="form-control" value="<?= View::e($r['max_members_override'] ?? '') ?>" placeholder="Puste = wg planu">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Max sporty (override)</label>
                                                <input type="number" name="max_sports_override" class="form-control" value="<?= View::e($r['max_sports_override'] ?? '') ?>" placeholder="Puste = wg planu">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                                            <button type="submit" class="btn btn-primary">Zapisz</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (($lastPage ?? 1) > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($i = 1; $i <= $lastPage; $i++): ?>
            <li class="page-item <?= $i === ($page ?? 1) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status ?? '') ?>&q=<?= urlencode($q ?? '') ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
