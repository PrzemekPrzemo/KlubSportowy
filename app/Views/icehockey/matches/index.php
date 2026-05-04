<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-snow text-primary me-2"></i>Mecze — Hokej</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#matchModal">
        <i class="bi bi-plus-circle"></i> Dodaj mecz
    </button>
</div>

<form method="GET" class="mb-3 d-flex gap-2">
    <select name="team" class="form-select form-select-sm">
        <option value="">Wszystkie drużyny</option>
        <?php foreach ($teams as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= $teamFilter === (int)$t['id'] ? 'selected' : '' ?>><?= View::e($t['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i></button>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Data</th><th>Gospodarze</th><th>Goście</th>
                    <th class="text-center">1T</th><th class="text-center">2T</th><th class="text-center">3T</th>
                    <th class="text-center">OT</th><th class="text-center">SO</th>
                    <th class="text-center">Wynik</th><th>Status</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($matches)): ?>
                <tr><td colspan="11" class="text-center text-muted py-4">Brak meczów.</td></tr>
            <?php else: foreach ($matches as $m):
                $si = $statuses[$m['status']] ?? ['label' => $m['status'], 'class' => 'secondary'];
            ?>
                <tr>
                    <td class="small"><?= date('Y-m-d H:i', strtotime($m['match_date'])) ?></td>
                    <td><strong><?= View::e($m['home_team_name']) ?></strong></td>
                    <td><?= View::e($m['away_team_name'] ?? '—') ?></td>
                    <td class="text-center small"><?= (int)$m['p1_home'] ?>:<?= (int)$m['p1_away'] ?></td>
                    <td class="text-center small"><?= (int)$m['p2_home'] ?>:<?= (int)$m['p2_away'] ?></td>
                    <td class="text-center small"><?= (int)$m['p3_home'] ?>:<?= (int)$m['p3_away'] ?></td>
                    <td class="text-center small text-warning">
                        <?php if ($m['ot_home'] || $m['ot_away']): ?>
                            <?= (int)$m['ot_home'] ?>:<?= (int)$m['ot_away'] ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-center small text-danger">
                        <?php if ($m['shootout']): ?>
                            <?= (int)$m['so_home'] ?>:<?= (int)$m['so_away'] ?>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-center font-monospace fw-bold">
                        <?= (int)$m['total_home'] ?> : <?= (int)$m['total_away'] ?>
                        <?php if ($m['shootout']): ?><small class="text-muted">(SO)</small>
                        <?php elseif ($m['ot_home'] || $m['ot_away']): ?><small class="text-muted">(OT)</small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-<?= $si['class'] ?>"><?= View::e($si['label']) ?></span></td>
                    <td>
                        <form method="POST" action="<?= url('icehockey/matches/' . (int)$m['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')">
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

<div class="modal fade" id="matchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= url('icehockey/matches/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Dodaj mecz hokeja</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Gospodarze</label>
                            <select name="home_team_id" class="form-select" required>
                                <option value="">— wybierz —</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>"><?= View::e($t['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Goście (nazwa)</label>
                            <input type="text" name="away_team_name" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Data</label>
                            <input type="datetime-local" name="match_date" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Lodowisko</label>
                            <input type="text" name="arena" class="form-control">
                        </div>
                        <div class="col-12"><hr><strong>Wyniki tercji</strong></div>
                        <?php foreach ([['p1_','1. tercja'],['p2_','2. tercja'],['p3_','3. tercja']] as [$pref,$label]): ?>
                            <div class="col-6">
                                <label class="form-label"><?= $label ?> — gospodarze</label>
                                <input type="number" name="<?= $pref ?>home" class="form-control" min="0" max="15" value="0">
                            </div>
                            <div class="col-6">
                                <label class="form-label"><?= $label ?> — goście</label>
                                <input type="number" name="<?= $pref ?>away" class="form-control" min="0" max="15" value="0">
                            </div>
                        <?php endforeach; ?>
                        <div class="col-3">
                            <label class="form-label">OT — gospodarze</label>
                            <input type="number" name="ot_home" class="form-control" min="0" max="3" value="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label">OT — goście</label>
                            <input type="number" name="ot_away" class="form-control" min="0" max="3" value="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label">SO — gospodarze</label>
                            <input type="number" name="so_home" class="form-control" min="0" max="1" value="0">
                        </div>
                        <div class="col-3">
                            <label class="form-label">SO — goście</label>
                            <input type="number" name="so_away" class="form-control" min="0" max="1" value="0">
                        </div>
                        <div class="col-6">
                            <div class="form-check mt-3">
                                <input type="checkbox" name="shootout" class="form-check-input" id="soChk">
                                <label class="form-check-label" for="soChk">Karne (shootout)</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach ($statuses as $k => $s): ?>
                                    <option value="<?= $k ?>"><?= View::e($s['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Uwagi</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
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
