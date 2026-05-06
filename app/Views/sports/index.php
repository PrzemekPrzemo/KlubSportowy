<?php use App\Helpers\View; ?>
<p class="text-muted">Każdy klub może prowadzić wiele sekcji sportowych jednocześnie (np. piłka nożna + koszykówka + sekcja wrotkarska). Aktywuj tutaj te sporty, które chcesz obsługiwać w panelu.</p>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-check2-square"></i> Aktywne sekcje (<?= count($current) ?>)</h5>
            <?php if (empty($current)): ?>
                <div class="text-muted">Brak aktywnych sekcji. Dodaj pierwszy sport z prawej strony.</div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($current as $cs): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong style="color: <?= View::e($cs['color']) ?>">
                                    <i class="bi <?= View::e($cs['icon']) ?>"></i>
                                    <?= View::e($cs['cs_name'] ?: $cs['name']) ?>
                                </strong>
                                <small class="text-muted d-block">
                                    <?= View::e($cs['name']) ?>
                                    <?php if (!empty($cs['federation_code'])): ?>
                                        • <?= View::e($cs['federation_code']) ?>
                                    <?php endif; ?>
                                    <?= $cs['team_sport'] ? '• drużynowy' : '• indywidualny' ?>
                                </small>
                            </div>
                            <div class="d-flex gap-1">
                                <a href="<?= url('sports/' . (int)$cs['club_sport_id'] . '/logos') ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Logo sekcji (3 sloty na PDF)">
                                    <i class="bi bi-images"></i>
                                </a>
                                <form method="POST" action="<?= url('sports/disable/' . (int)$cs['club_sport_id']) ?>"
                                      onsubmit="return confirm('Wyłączyć sekcję?')" class="m-0">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" type="submit">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-plus-circle"></i> Dostępne sporty</h5>
            <?php if (empty($available)): ?>
                <div class="text-muted">Wszystkie sporty z katalogu są już aktywne w klubie.</div>
            <?php else: ?>
                <?php foreach ($available as $s): ?>
                    <form method="POST" action="<?= url('sports/enable') ?>" class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="sport_id" value="<?= (int)$s['id'] ?>">
                        <div>
                            <strong style="color: <?= View::e($s['color']) ?>">
                                <i class="bi <?= View::e($s['icon']) ?>"></i>
                                <?= View::e($s['name']) ?>
                            </strong>
                            <small class="text-muted d-block">
                                <?php if (!empty($s['federation_code'])): ?>
                                    <?= View::e($s['federation_code']) ?> •
                                <?php endif; ?>
                                <?= $s['team_sport'] ? 'drużynowy' : 'indywidualny' ?>
                            </small>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus"></i> Dodaj
                        </button>
                    </form>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
