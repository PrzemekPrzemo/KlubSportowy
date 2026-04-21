<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-shield-check text-primary me-2"></i>Zgodność regulacyjna</h4>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#adModal">
        <i class="bi bi-plus-circle"></i> Nowa deklaracja anti-doping
    </button>
</div>

<!-- Alerts -->
<?php if (!empty($requiringWada)): ?>
<div class="alert alert-danger mb-3">
    <strong><i class="bi bi-exclamation-octagon me-1"></i> <?= count($requiringWada) ?> zawodników wymaga deklaracji WADA</strong> (aktywni w sportach: weightlifting, boxing, swimming, taekwondo, cycling i innych WADA).
    <ul class="mb-0 mt-2 small">
        <?php foreach (array_slice($requiringWada, 0, 10) as $m): ?>
            <li><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?> (<?= View::e($m['sports']) ?>)</li>
        <?php endforeach; ?>
        <?php if (count($requiringWada) > 10): ?><li>...i <?= count($requiringWada) - 10 ?> więcej</li><?php endif; ?>
    </ul>
</div>
<?php endif; ?>

<?php if (!empty($expiringSoon)): ?>
<div class="alert alert-warning mb-3">
    <strong><i class="bi bi-clock me-1"></i> <?= count($expiringSoon) ?> deklaracji wygasa w ciągu 30 dni</strong>
</div>
<?php endif; ?>

<?php if (!empty($minorsMissing)): ?>
<div class="alert alert-warning mb-3">
    <strong><i class="bi bi-person-exclamation me-1"></i> <?= count($minorsMissing) ?> małoletnich bez podpisanej zgody opiekuna</strong>
    <ul class="mb-0 mt-2 small">
        <?php foreach (array_slice($minorsMissing, 0, 10) as $m):
            $age = floor((time() - strtotime($m['birth_date'])) / (365.25 * 86400));
        ?>
            <li><?= View::e($m['last_name'] . ' ' . $m['first_name']) ?> — <?= (int)$age ?> lat
                <a href="#" data-bs-toggle="modal" data-bs-target="#consentModal<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-primary ms-2">Dodaj zgodę</a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- Modals: add consent per minor -->
<?php foreach ($minorsMissing as $mm): ?>
<div class="modal fade" id="consentModal<?= (int)$mm['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('admin/compliance/minor-consent/' . (int)$mm['id'] . '/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Zgoda opiekuna: <?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-8"><label class="form-label">Imię i nazwisko opiekuna</label><input type="text" name="guardian_name" class="form-control" required></div>
                        <div class="col-4"><label class="form-label">PESEL/nr dow.</label><input type="text" name="guardian_id_number" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Telefon</label><input type="tel" name="guardian_phone" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Email</label><input type="email" name="guardian_email" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Data podpisu</label><input type="date" name="signed_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-6"><label class="form-label">Ważne do</label><input type="date" name="valid_until" class="form-control"></div>
                    </div>
                    <div class="form-check"><input type="checkbox" name="photo_consent" id="p<?=(int)$mm['id']?>" class="form-check-input"><label class="form-check-label" for="p<?=(int)$mm['id']?>">Zgoda na publikację zdjęć</label></div>
                    <div class="form-check"><input type="checkbox" name="media_consent" id="m<?=(int)$mm['id']?>" class="form-check-input"><label class="form-check-label" for="m<?=(int)$mm['id']?>">Zgoda na media społecznościowe</label></div>
                    <div class="form-check"><input type="checkbox" name="travel_consent" id="t<?=(int)$mm['id']?>" class="form-check-input"><label class="form-check-label" for="t<?=(int)$mm['id']?>">Zgoda na wyjazdy klubowe</label></div>
                    <div class="form-check"><input type="checkbox" name="medical_decisions" id="d<?=(int)$mm['id']?>" class="form-check-input"><label class="form-check-label" for="d<?=(int)$mm['id']?>">Zgoda na decyzje medyczne w sytuacji awaryjnej</label></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Zapisz zgodę</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Declarations list -->
<div class="card shadow-sm">
    <div class="card-header"><i class="bi bi-file-earmark-text me-1"></i> Deklaracje anti-dopingowe</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Zawodnik</th><th>Typ</th><th>Data podpisu</th><th>Ważne do</th><th>Status</th><th>Świadek</th><th></th></tr>
            </thead>
            <tbody>
            <?php if (empty($declarations)): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">Brak deklaracji.</td></tr>
            <?php else: foreach ($declarations as $d):
                $days = (int)($d['days_remaining'] ?? 0);
            ?>
                <tr>
                    <td><strong><?= View::e($d['last_name'] . ' ' . $d['first_name']) ?></strong> <small class="text-muted">#<?= View::e($d['member_number']) ?></small></td>
                    <td><span class="badge bg-primary"><?= View::e($declarationTypes[$d['declaration_type']] ?? $d['declaration_type']) ?></span></td>
                    <td class="small"><?= View::e($d['signed_date']) ?></td>
                    <td class="small"><?= View::e($d['valid_until']) ?></td>
                    <td>
                        <?php if ($days < 0): ?>
                            <span class="badge bg-danger">Wygasła (<?= abs($days) ?> dni temu)</span>
                        <?php elseif ($days <= 30): ?>
                            <span class="badge bg-warning text-dark">Kończy się za <?= $days ?> dni</span>
                        <?php else: ?>
                            <span class="badge bg-success">Aktywna (<?= $days ?> dni)</span>
                        <?php endif; ?>
                    </td>
                    <td class="small text-muted"><?= View::e($d['witness'] ?? '—') ?></td>
                    <td>
                        <form method="POST" action="<?= url('admin/compliance/declaration/' . (int)$d['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: add declaration -->
<div class="modal fade" id="adModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('admin/compliance/declaration/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Deklaracja anti-dopingowa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawodnik</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $mm): ?>
                                <option value="<?= (int)$mm['id'] ?>"><?= View::e($mm['last_name'] . ' ' . $mm['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Typ deklaracji</label>
                        <select name="declaration_type" class="form-select">
                            <?php foreach ($declarationTypes as $k => $v): ?>
                                <option value="<?= $k ?>"><?= View::e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Data podpisu</label><input type="date" name="signed_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-6"><label class="form-label">Ważna do</label><input type="date" name="valid_until" class="form-control" value="<?= date('Y-m-d', strtotime('+1 year')) ?>" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Świadek / osoba zbierająca</label><input type="text" name="witness" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Uwagi</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success">Zapisz</button>
                </div>
            </form>
        </div>
    </div>
</div>
