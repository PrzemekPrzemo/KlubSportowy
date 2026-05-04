<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Łodzie i jachty — Żeglarstwo</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#boatModal">
        <i class="bi bi-plus-circle"></i> Dodaj łódź
    </button>
</div>

<?php if (!empty($expiring)): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong><?= count($expiring) ?></strong> łodzi/jachtów ma ubezpieczenie wygasające w ciągu 30 dni:
    <?= implode(', ', array_map(fn($b) => View::e($b['name']), $expiring)) ?>
</div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($boats as $b): ?>
    <?php
    $insExpiry = $b['insurance_expiry'];
    $daysToIns = $insExpiry ? (int)floor((strtotime($insExpiry) - time()) / 86400) : null;
    $insBadge  = $daysToIns === null ? 'secondary' : ($daysToIns < 0 ? 'danger' : ($daysToIns <= 30 ? 'warning' : 'success'));
    ?>
    <div class="col-md-6">
        <div class="card <?= $insBadge === 'danger' || $insBadge === 'warning' ? 'border-' . $insBadge : '' ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-water me-1"></i><?= View::e($b['name']) ?>
                    <?php if ($b['class']): ?><small class="text-muted">(<?= View::e($b['class']) ?>)</small><?php endif; ?>
                </h6>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#crewModal"
                            data-boatid="<?= (int)$b['id'] ?>" data-boatname="<?= View::e($b['name']) ?>">
                        <i class="bi bi-person-plus"></i>
                    </button>
                    <form method="POST" action="<?= url('sailing/boats/' . (int)$b['id'] . '/delete') ?>"
                          onsubmit="return confirm('Usunąć łódź?')">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
            <div class="card-body py-2 px-3">
                <div class="row g-1 small text-muted mb-2">
                    <?php if ($b['registration_number']): ?><div class="col-auto"><i class="bi bi-hash"></i> <?= View::e($b['registration_number']) ?></div><?php endif; ?>
                    <?php if ($b['length_m']): ?><div class="col-auto"><i class="bi bi-rulers"></i> <?= $b['length_m'] ?>m</div><?php endif; ?>
                    <?php if ($b['year_built']): ?><div class="col-auto"><?= (int)$b['year_built'] ?> r.</div><?php endif; ?>
                </div>
                <div class="d-flex gap-2 mb-2">
                    <?php if ($insExpiry): ?>
                    <span class="badge bg-<?= $insBadge ?>">
                        OC: <?= $insExpiry ?>
                        <?= $daysToIns < 0 ? '(wygasłe)' : "({$daysToIns} dni)" ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($b['owner_last']): ?>
                    <span class="badge bg-light text-dark border">Właściciel: <?= View::e($b['owner_last'] . ' ' . $b['owner_first']) ?></span>
                    <?php endif; ?>
                </div>
                <?php $crew = $crews[$b['id']] ?? []; ?>
                <?php if (!empty($crew)): ?>
                <div class="small">
                    <?php foreach ($crew as $c): ?>
                        <span class="badge bg-secondary me-1"><?= View::e($c['role']) ?>: <?= View::e($c['last_name']) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php if (empty($boats)): ?>
    <div class="col"><div class="alert alert-info">Brak łodzi. Dodaj pierwszą.</div></div>
<?php endif; ?>
</div>

<!-- Modal: Dodaj łódź -->
<div class="modal fade" id="boatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('sailing/boats/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-water me-1"></i> Dodaj łódź / jacht</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nazwa</label><input type="text" name="name" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Nr rejestr.</label><input type="text" name="registration_number" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Klasa</label><input type="text" name="class" class="form-control" placeholder="np. Laser"></div>
                        <div class="col-md-2"><label class="form-label">Dł. (m)</label><input type="number" name="length_m" class="form-control" step="0.1" min="1"></div>
                        <div class="col-md-2"><label class="form-label">Rok</label><input type="number" name="year_built" class="form-control" min="1900"></div>
                        <div class="col-md-3"><label class="form-label">Materiał kadłuba</label><input type="text" name="hull_material" class="form-control" placeholder="np. GRP"></div>
                        <div class="col-md-3"><label class="form-label">OC do</label><input type="date" name="insurance_expiry" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Przegląd</label><input type="date" name="next_inspection" class="form-control"></div>
                        <div class="col-md-5"><label class="form-label">Właściciel</label>
                            <select name="owner_member_id" class="form-select">
                                <option value="">— wybierz —</option>
                                <?php foreach ($members as $m): ?>
                                    <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12"><label class="form-label">Uwagi</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Dodaj do załogi -->
<div class="modal fade" id="crewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="crewForm" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Dodaj do załogi: <span id="modal-boat-name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Zawodnik</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>"><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-7"><label class="form-label">Rola</label>
                            <select name="role" class="form-select">
                                <option value="crew">Crew</option>
                                <option value="skipper">Skipper</option>
                                <option value="navigator">Nawigator</option>
                                <option value="tactician">Taktyk</option>
                                <option value="trimmer">Trimmer</option>
                            </select>
                        </div>
                        <div class="col-5 align-self-end">
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="is_permanent" value="1">
                                <label class="form-check-label">Stała załoga</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Dodaj</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('crewModal').addEventListener('show.bs.modal', function(e) {
    var btn = e.relatedTarget;
    document.getElementById('crewForm').action = '<?= url('sailing/boats/') ?>' + btn.dataset.boatid + '/crew/add';
    document.getElementById('modal-boat-name').textContent = btn.dataset.boatname;
});
</script>
