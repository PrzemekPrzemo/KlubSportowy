<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Mecze — Floorball</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#matchModal">
        <i class="bi bi-plus-circle"></i> Zaplanuj mecz
    </button>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Harmonogram</h6></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Gospodarze</th><th class="text-center">Wynik</th><th>Goście</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php if (empty($matches)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Brak meczów.</td></tr>
                    <?php else: ?>
                        <?php foreach ($matches as $m):
                            $statusBadge=['zaplanowany'=>['secondary','Planowany'],'w_trakcie'=>['primary','W trakcie'],'zakonczony'=>['success','Zakończony'],'odwolany'=>['danger','Odwołany']];
                            [$sc,$sl] = $statusBadge[$m['status']] ?? ['secondary',$m['status']];
                        ?>
                        <tr>
                            <td><?= date('d.m.Y H:i', strtotime($m['match_date'])) ?></td>
                            <td><?= View::e($m['home_team_name'] ?? '—') ?></td>
                            <td class="text-center fw-bold">
                                <?php if ($m['status'] === 'zakonczony'): ?>
                                    <?= (int)$m['home_score'] ?> : <?= (int)$m['away_score'] ?>
                                <?php else: ?>
                                    <?php if ($m['status'] === 'zaplanowany'): ?>
                                    <button class="btn btn-sm btn-outline-secondary"
                                            data-bs-toggle="modal" data-bs-target="#resultModal"
                                            data-mid="<?= (int)$m['id'] ?>">Wpisz wynik</button>
                                    <?php else: ?>—<?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><?= View::e($m['away_team_name'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= $sc ?>"><?= $sl ?></span></td>
                            <td>
                                <form method="POST" action="<?= url('floorball/matches/' . (int)$m['id'] . '/delete') ?>"
                                      onsubmit="return confirm('Usunąć mecz?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <?php foreach ($teams as $t): ?>
        <?php $teamScorers = $scorers[$t['id']] ?? []; if (empty($teamScorers)) continue; ?>
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Strzelcy: <?= View::e($t['name']) ?></h6></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Zawodnik</th><th class="text-center">G</th><th class="text-center">A</th><th class="text-center">PIM</th></tr></thead>
                    <tbody>
                    <?php foreach ($teamScorers as $s): ?>
                        <tr>
                            <td><?= View::e($s['last_name']) ?> <?= View::e($s['first_name']) ?></td>
                            <td class="text-center"><?= (int)$s['goals'] ?></td>
                            <td class="text-center"><?= (int)$s['assists'] ?></td>
                            <td class="text-center"><?= (int)$s['pim'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal: Zaplanuj mecz -->
<div class="modal fade" id="matchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('floorball/matches/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-calendar2-check me-1"></i> Zaplanuj mecz</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Data i godzina</label>
                        <input type="datetime-local" name="match_date" class="form-control" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Drużyna goszcząca</label>
                            <select name="home_team_id" class="form-select">
                                <option value="">— zewnętrzna —</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>"><?= View::e($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Drużyna gości</label>
                            <select name="away_team_id" class="form-select">
                                <option value="">— zewnętrzna —</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>"><?= View::e($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lokalizacja</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Zaplanuj</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Wpisz wynik -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="resultForm" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Wpisz wynik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 text-center">
                        <div class="col-5">
                            <label class="form-label">Gospodarz</label>
                            <input type="number" name="home_score" class="form-control text-center fs-4" min="0" value="0">
                        </div>
                        <div class="col-2 align-self-end pb-2 fs-4">:</div>
                        <div class="col-5">
                            <label class="form-label">Gość</label>
                            <input type="number" name="away_score" class="form-control text-center fs-4" min="0" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Zapisz wynik</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('resultModal').addEventListener('show.bs.modal', function(e) {
    var mid = e.relatedTarget.dataset.mid;
    document.getElementById('resultForm').action = '<?= url('floorball/matches/') ?>' + mid + '/result';
});
</script>
