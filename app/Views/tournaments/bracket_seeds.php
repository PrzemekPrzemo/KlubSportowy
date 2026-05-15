<?php use App\Helpers\View; ?>
<?php $tid = (int)($tournament['id'] ?? 0); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-list-ol me-2"></i>Seedy — <?= View::e($tournament['name']) ?></h4>
    <a href="<?= url('tournaments/' . $tid . '/bracket') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Powrót
    </a>
</div>

<?php if (!empty($isLocked)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-lock me-2"></i>Drabinka jest zablokowana. Edycja seedów wyłączona.
    </div>
<?php endif; ?>

<form method="POST" action="<?= url('tournaments/' . $tid . '/bracket/seeds') ?>" class="card">
    <?= csrf_field() ?>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Przypisz numer seeda (1 = najwyższy) każdemu zawodnikowi. Każda liczba unikalna w obrębie turnieju.
        </p>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th style="width:80px">Seed #</th>
                        <th>Zawodnik</th>
                        <th>Nr klubowy</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($participants as $i => $p): ?>
                    <?php $current = $seedsByPartId[(int)$p['id']] ?? null; ?>
                    <tr>
                        <td>
                            <input type="number" min="1" max="9999"
                                   name="seeds[<?= (int)$p['id'] ?>]"
                                   value="<?= $current !== null ? (int)$current : '' ?>"
                                   class="form-control form-control-sm"
                                   <?= !empty($isLocked) ? 'disabled' : '' ?>>
                        </td>
                        <td><?= View::e(($p['last_name'] ?? '') . ' ' . ($p['first_name'] ?? '')) ?></td>
                        <td class="text-muted small"><?= View::e($p['member_number'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer text-end">
        <button type="submit" class="btn btn-primary" <?= !empty($isLocked) ? 'disabled' : '' ?>>
            <i class="bi bi-check2"></i> Zapisz seedy
        </button>
    </div>
</form>
