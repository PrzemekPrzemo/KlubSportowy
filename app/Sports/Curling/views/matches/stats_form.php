<?php use App\Helpers\View; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-grid-3x3-gap text-primary me-2"></i>Endy — Curling</h4>
    <a href="<?= url($backUrl) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Wróć</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <p class="mb-1"><strong>Data:</strong> <?= date('Y-m-d H:i', strtotime($match['match_date'])) ?></p>
        <p class="mb-1"><strong>Wynik kumulatywny:</strong>
            <span class="font-monospace fw-bold"><?= (int)$totals['home'] ?> : <?= (int)$totals['away'] ?></span></p>
        <p class="mb-0 small text-muted">Hammer w endzie 1: <?= View::e($match['hammer_start']) ?> · Planowane endy: <?= (int)$match['ends_planned'] ?></p>
    </div>
</div>

<form method="POST" action="<?= url($submitUrl) ?>">
    <?= csrf_field() ?>

    <?php
        $existing  = [];
        foreach ($ends as $row) { $existing[(int)$row['end_number']] = $row; }
        $endsCount = max((int)$match['ends_planned'], count($existing));
    ?>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>End</th><th>Gospodarze pkt</th><th>Goście pkt</th><th>Hammer</th>
                </tr>
            </thead>
            <tbody>
            <?php for ($n = 1; $n <= $endsCount; $n++):
                $row    = $existing[$n] ?? null;
                $h      = $row['home_score'] ?? 0;
                $a      = $row['away_score'] ?? 0;
                $hammer = $row['hammer_side'] ?? '—';
            ?>
                <tr>
                    <td class="fw-bold"><?= $n ?>
                        <input type="hidden" name="ends[<?= $n ?>][end_number]" value="<?= $n ?>">
                    </td>
                    <td><input type="number" name="ends[<?= $n ?>][home_score]" class="form-control form-control-sm" value="<?= (int)$h ?>" min="0" max="8"></td>
                    <td><input type="number" name="ends[<?= $n ?>][away_score]" class="form-control form-control-sm" value="<?= (int)$a ?>" min="0" max="8"></td>
                    <td class="small text-muted"><?= View::e($hammer) ?></td>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="<?= url($backUrl) ?>" class="btn btn-secondary">Anuluj</a>
        <button class="btn btn-primary"><i class="bi bi-save"></i> Zapisz endy</button>
    </div>
</form>
