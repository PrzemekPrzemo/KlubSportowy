<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Rezerwacje kortów — Padel</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#reserveModal">
        <i class="bi bi-plus-circle"></i> Nowa rezerwacja
    </button>
</div>

<!-- Tabela rezerwacji -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Lista rezerwacji</h6></div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Zawodnik</th><th>Kort</th><th>Termin</th><th>Status</th><th>Opłacono</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($reservations)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Brak rezerwacji.</td></tr>
            <?php else: ?>
                <?php foreach ($reservations as $r): ?>
                <tr>
                    <td><?= View::e($r['last_name']) ?> <?= View::e($r['first_name']) ?></td>
                    <td><?= View::e($r['court_name']) ?></td>
                    <td><?= date('d.m H:i', strtotime($r['start_datetime'])) ?> – <?= date('H:i', strtotime($r['end_datetime'])) ?></td>
                    <td>
                        <?php $sBadge=['pending'=>['warning','Oczekuje'],'confirmed'=>['success','Potwierdzona'],'cancelled'=>['secondary','Anulowana']]; ?>
                        <?php [$sc,$sl]=$sBadge[$r['status']]??['secondary',$r['status']]; ?>
                        <span class="badge bg-<?= $sc ?> text-dark"><?= $sl ?></span>
                    </td>
                    <td><i class="bi bi-<?= $r['paid'] ? 'check-circle-fill text-success' : 'x-circle text-muted' ?>"></i></td>
                    <td>
                        <div class="d-flex gap-1">
                            <?php if ($r['status'] === 'pending'): ?>
                            <form method="POST" action="<?= url('padel/reservations/' . (int)$r['id'] . '/confirm') ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-success" title="Potwierdź"><i class="bi bi-check2"></i></button>
                            </form>
                            <?php endif; ?>
                            <?php if ($r['status'] !== 'cancelled'): ?>
                            <form method="POST" action="<?= url('padel/reservations/' . (int)$r['id'] . '/cancel') ?>"
                                  onsubmit="return confirm('Anulować rezerwację?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" title="Anuluj"><i class="bi bi-x"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Nowa rezerwacja -->
<div class="modal fade" id="reserveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('padel/reservations/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-calendar3 me-1"></i> Nowa rezerwacja</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kort</label>
                        <select name="court_id" class="form-select" required>
                            <option value="">— wybierz kort —</option>
                            <?php foreach ($courts as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"><?= View::e($c['name']) ?> (<?= $c['indoor'] ? 'Hala' : 'Zewnętrzny' ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Zawodnik</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Początek</label>
                            <input type="datetime-local" name="start_datetime" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Koniec</label>
                            <input type="datetime-local" name="end_datetime" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Uwagi</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Zarezerwuj</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($courts)): ?>
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Korty</h6>
    </div>
    <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Kort</th><th>Nawierzchnia</th><th>Typ</th></tr></thead>
        <tbody>
        <?php foreach ($courts as $c): ?>
            <tr>
                <td><?= View::e($c['name']) ?></td>
                <td><?= View::e($c['surface']) ?></td>
                <td><?= $c['indoor'] ? 'Hala' : 'Zewnętrzny' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
