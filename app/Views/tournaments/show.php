<?php use App\Helpers\View; ?>
<?php
    $formatLabels = [
        'single_elimination' => 'Puchar (drabinka)',
        'double_elimination' => 'Podwójna eliminacja',
        'round_robin'        => 'Każdy z każdym',
    ];
    $statusLabels = [
        'draft'    => ['label' => 'Szkic',      'class' => 'bg-secondary'],
        'active'   => ['label' => 'Aktywny',    'class' => 'bg-success'],
        'finished' => ['label' => 'Zakończony', 'class' => 'bg-primary'],
    ];
    $sportName = $sports[$tournament['sport_key']]['name'] ?? $tournament['sport_key'];
    $st = $statusLabels[$tournament['status']] ?? ['label' => View::e($tournament['status']), 'class' => 'bg-secondary'];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-trophy me-2"></i><?= View::e($tournament['name']) ?></h4>
    <a href="<?= url('tournaments') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<!-- Info -->
<div class="card mb-4">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Sport</dt>
            <dd class="col-sm-9"><?= View::e($sportName) ?></dd>
            <dt class="col-sm-3">Format</dt>
            <dd class="col-sm-9"><?= View::e($formatLabels[$tournament['format']] ?? $tournament['format']) ?></dd>
            <dt class="col-sm-3">Data rozpoczęcia</dt>
            <dd class="col-sm-9"><?= format_date($tournament['date_start']) ?></dd>
            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9"><span class="badge <?= $st['class'] ?>"><?= $st['label'] ?></span></dd>
        </dl>
    </div>
</div>

<?php if ($tournament['status'] === 'draft'): ?>
<!-- Participants management (draft only) -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-people me-1"></i>Uczestnicy (<?= count($tournament['participants']) ?>)</strong>
    </div>
    <div class="card-body">
        <!-- Add participant -->
        <form method="POST" action="<?= url('tournaments/' . (int)$tournament['id'] . '/participant') ?>" class="d-flex gap-2 mb-3">
            <?= csrf_field() ?>
            <select name="member_id" class="form-select" required>
                <option value="">— wybierz zawodnika —</option>
                <?php
                    $existingIds = array_column($tournament['participants'], 'member_id');
                    foreach ($members as $m):
                        if (in_array($m['id'], $existingIds)) continue;
                ?>
                    <option value="<?= (int)$m['id'] ?>">
                        <?= View::e($m['last_name']) ?> <?= View::e($m['first_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm text-nowrap">
                <i class="bi bi-plus"></i> Dodaj
            </button>
        </form>

        <?php if (empty($tournament['participants'])): ?>
            <div class="text-muted">Brak uczestników. Dodaj zawodników powyżej.</div>
        <?php else: ?>
            <table class="table table-sm mb-0">
                <thead><tr><th>#</th><th>Zawodnik</th><th>Nr</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($tournament['participants'] as $i => $p): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= View::e($p['last_name']) ?> <?= View::e($p['first_name']) ?></td>
                        <td class="text-muted small"><?= View::e($p['member_number'] ?? '') ?></td>
                        <td class="text-end">
                            <form method="POST" action="<?= url('tournaments/' . (int)$tournament['id'] . '/participant-remove') ?>"
                                  onsubmit="return confirm('Usunąć uczestnika?')" class="m-0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="member_id" value="<?= (int)$p['member_id'] ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if (count($tournament['participants']) >= 2): ?>
    <div class="card-footer">
        <form method="POST" action="<?= url('tournaments/' . (int)$tournament['id'] . '/generate') ?>"
              onsubmit="return confirm('Wygenerować drabinkę? Spowoduje to usunięcie istniejących meczy.')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-warning">
                <i class="bi bi-diagram-3 me-1"></i> Generuj drabinkę
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Bracket display -->
<?php if (!empty($byRound)): ?>
<div class="mb-2">
    <h5><i class="bi bi-diagram-3 me-1"></i>Drabinka turniejowa</h5>
</div>

<?php foreach ($byRound as $round => $matches): ?>
<div class="mb-4">
    <h6 class="text-muted text-uppercase small mb-2">Runda <?= (int)$round ?></h6>
    <div class="d-flex flex-wrap gap-3">
        <?php foreach ($matches as $match): ?>
            <?php
                $hasResult   = $match['winner_id'] !== null;
                $bothPlayers = $match['player1_id'] !== null && $match['player2_id'] !== null;
                $isBye       = $match['player1_id'] === null || $match['player2_id'] === null;
            ?>
            <div class="card shadow-sm" style="min-width:240px;max-width:300px">
                <div class="card-header py-1 px-2 small text-muted">
                    Mecz #<?= (int)$match['match_number'] ?>
                    <?php if ($isBye && !$hasResult): ?>
                        <span class="badge bg-secondary ms-1">BYE</span>
                    <?php endif; ?>
                </div>
                <div class="card-body py-2 px-3">
                    <!-- Player 1 -->
                    <div class="d-flex justify-content-between align-items-center mb-1
                        <?= ($hasResult && (int)$match['winner_id'] === (int)$match['player1_id']) ? 'fw-bold text-success' : '' ?>
                        <?= ($hasResult && (int)$match['winner_id'] !== (int)$match['player1_id'] && $match['player1_id'] !== null) ? 'text-muted text-decoration-line-through' : '' ?>">
                        <span><?= $match['player1_id'] ? View::e($match['player1_name']) : '<em class="text-muted">BYE</em>' ?></span>
                        <?php if ($hasResult && $match['score1'] !== null): ?>
                            <span class="badge bg-light text-dark border"><?= View::e($match['score1']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="border-top my-1"></div>
                    <!-- Player 2 -->
                    <div class="d-flex justify-content-between align-items-center mt-1
                        <?= ($hasResult && (int)$match['winner_id'] === (int)$match['player2_id']) ? 'fw-bold text-success' : '' ?>
                        <?= ($hasResult && (int)$match['winner_id'] !== (int)$match['player2_id'] && $match['player2_id'] !== null) ? 'text-muted text-decoration-line-through' : '' ?>">
                        <span><?= $match['player2_id'] ? View::e($match['player2_name']) : '<em class="text-muted">BYE</em>' ?></span>
                        <?php if ($hasResult && $match['score2'] !== null): ?>
                            <span class="badge bg-light text-dark border"><?= View::e($match['score2']) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($hasResult): ?>
                        <div class="mt-2 small text-success">
                            <i class="bi bi-trophy-fill me-1"></i>Zwycięzca: <strong><?= View::e($match['winner_name']) ?></strong>
                        </div>
                    <?php elseif ($bothPlayers && $tournament['status'] === 'active'): ?>
                        <!-- Result entry form -->
                        <form method="POST" action="<?= url('tournaments/match/' . (int)$match['id'] . '/result') ?>" class="mt-2">
                            <?= csrf_field() ?>
                            <div class="row g-1 mb-1">
                                <div class="col-6">
                                    <input type="text" name="score1" class="form-control form-control-sm"
                                           placeholder="Wynik 1" maxlength="20">
                                </div>
                                <div class="col-6">
                                    <input type="text" name="score2" class="form-control form-control-sm"
                                           placeholder="Wynik 2" maxlength="20">
                                </div>
                            </div>
                            <select name="winner_id" class="form-select form-select-sm mb-1" required>
                                <option value="">— zwycięzca —</option>
                                <option value="<?= (int)$match['player1_id'] ?>"><?= View::e($match['player1_name']) ?></option>
                                <option value="<?= (int)$match['player2_id'] ?>"><?= View::e($match['player2_name']) ?></option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="bi bi-check2"></i> Zapisz wynik
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php elseif ($tournament['status'] !== 'draft'): ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Brak meczy w turnieju.</div>
<?php endif; ?>
