<?php use App\Helpers\View; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>WOD Library — CrossFit</h4>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#wodModal">
        <i class="bi bi-plus-circle"></i> Dodaj WOD
    </button>
</div>

<!-- Filter bar -->
<div class="mb-3 d-flex flex-wrap gap-2">
    <a href="<?= url('crossfit/wods') ?>" class="btn btn-sm <?= $filterType === null ? 'btn-primary' : 'btn-outline-secondary' ?>">Wszystkie</a>
    <?php foreach ($wodTypes as $key => $label): ?>
        <a href="<?= url('crossfit/wods?type=' . $key) ?>"
           class="btn btn-sm <?= $filterType === $key ? 'btn-primary' : 'btn-outline-secondary' ?>">
            <?= View::e($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (empty($wods)): ?>
    <div class="alert alert-secondary">Brak WOD-ów<?= $filterType ? ' dla wybranego typu' : '' ?>.</div>
<?php else: ?>
    <div class="row g-3">
    <?php foreach ($wods as $w):
        $board = $boards[$w['id']] ?? [];
        $typeLabel = $wodTypes[$w['wod_type']] ?? $w['wod_type'];
        $typeBadgeClass = match($w['wod_type']) {
            'amrap'    => 'bg-primary',
            'emom'     => 'bg-info text-dark',
            'for_time' => 'bg-success',
            'max_reps' => 'bg-warning text-dark',
            'for_load' => 'bg-danger',
            'chipper'  => 'bg-secondary',
            'ladder'   => 'bg-dark',
            'tabata'   => 'bg-purple',
            default    => 'bg-secondary',
        };
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-start">
                <div>
                    <strong><?= View::e($w['name']) ?></strong>
                    <?php if (!empty($w['benchmark_name'])): ?>
                        <div class="small text-muted"><i class="bi bi-star-fill text-warning"></i> <?= View::e($w['benchmark_name']) ?></div>
                    <?php endif; ?>
                </div>
                <span class="badge <?= $typeBadgeClass ?>"><?= View::e($typeLabel) ?></span>
            </div>
            <div class="card-body">
                <?php if (!empty($w['description'])): ?>
                    <p class="small text-muted mb-2"><?= nl2br(View::e($w['description'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($w['time_cap'])): ?>
                    <div class="small mb-2"><i class="bi bi-clock me-1"></i>Time cap: <strong><?= (int)$w['time_cap'] ?> min</strong></div>
                <?php endif; ?>

                <!-- Mini leaderboard -->
                <?php if (!empty($board)): ?>
                <div class="mt-2">
                    <div class="small fw-bold text-muted mb-1"><i class="bi bi-bar-chart me-1"></i>Top wyniki</div>
                    <?php foreach ($board as $i => $s): ?>
                        <div class="d-flex justify-content-between small py-1 border-bottom">
                            <span>
                                <span class="text-muted me-1"><?= $i + 1 ?>.</span>
                                <?= View::e($s['last_name']) ?> <?= View::e($s['first_name']) ?>
                                <?php if ($s['rx']): ?><span class="badge bg-success ms-1" style="font-size:.65rem">RX</span><?php endif; ?>
                                <?php if ($s['scaled']): ?><span class="badge bg-warning text-dark ms-1" style="font-size:.65rem">Scaled</span><?php endif; ?>
                            </span>
                            <strong><?= View::e($s['score']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <button class="btn btn-sm btn-outline-primary"
                        data-bs-toggle="modal" data-bs-target="#scoreModal"
                        data-wod-id="<?= (int)$w['id'] ?>"
                        data-wod-name="<?= View::e($w['name']) ?>">
                    <i class="bi bi-plus"></i> Dodaj wynik
                </button>
                <form method="POST" action="<?= url('crossfit/wods/' . (int)$w['id'] . '/delete') ?>"
                      onsubmit="return confirm('Usunąć WOD i wszystkie wyniki?')">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal: Dodaj WOD -->
<div class="modal fade" id="wodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('crossfit/wods/store') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-lightning-charge me-1"></i> Nowy WOD</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nazwa WOD *</label>
                        <input type="text" name="name" class="form-control" required placeholder="np. Fran, Cindy, 21-15-9...">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Typ *</label>
                            <select name="wod_type" class="form-select" required>
                                <?php foreach ($wodTypes as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= View::e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Time cap (min)</label>
                            <input type="number" name="time_cap" class="form-control" min="1" max="120" placeholder="opcjonalnie">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opis / ćwiczenia</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="3 rundy: 21 Thrusters, 21 Pull-ups..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nazwa benchmark (opcjonalnie)</label>
                        <input type="text" name="benchmark_name" class="form-control" placeholder="np. Girl, Hero WOD, Open...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Zapisz WOD</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Dodaj wynik -->
<div class="modal fade" id="scoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('crossfit/wods/__WOD_ID__/score') ?>" id="scoreForm">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i> Dodaj wynik: <span id="scoreWodName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Zawodnik *</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">— wybierz —</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?= (int)$m['id'] ?>">
                                    <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wynik *</label>
                        <input type="text" name="score" class="form-control" required
                               placeholder="np. 4:32, 157 reps, 100kg">
                        <div class="form-text">Podaj wynik w formacie odpowiednim dla typu WOD (czas, liczba powtórzeń, ciężar).</div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="rx" id="rxCheck" value="1">
                                <label class="form-check-label" for="rxCheck">RX (bez skalowania)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="scaled" id="scaledCheck" value="1">
                                <label class="form-check-label" for="scaledCheck">Scaled</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data *</label>
                        <input type="date" name="score_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notatki</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i> Zapisz wynik</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('scoreModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    const wodId = btn.dataset.wodId;
    const wodName = btn.dataset.wodName;
    document.getElementById('scoreWodName').textContent = wodName;
    document.getElementById('scoreForm').action = '<?= url('crossfit/wods/') ?>' + wodId + '/score';
});
</script>
