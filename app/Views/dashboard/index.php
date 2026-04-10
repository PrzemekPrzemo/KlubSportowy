<?php use App\Helpers\View; ?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3">
            <div class="text-muted small">Zawodnicy aktywni</div>
            <div class="display-6"><?= (int)($stats['members'] ?? 0) ?></div>
            <a href="<?= url('members') ?>" class="stretched-link small">Zobacz &rarr;</a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3">
            <div class="text-muted small">Sekcje sportowe</div>
            <div class="display-6"><?= (int)($stats['sports'] ?? 0) ?></div>
            <a href="<?= url('sports') ?>" class="stretched-link small">Zarządzaj &rarr;</a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3">
            <div class="text-muted small">Nadchodzące wydarzenia</div>
            <div class="display-6"><?= (int)($stats['events_upcoming'] ?? 0) ?></div>
            <a href="<?= url('events') ?>" class="stretched-link small">Kalendarz &rarr;</a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3">
            <div class="text-muted small">Wpływy tego roku</div>
            <div class="display-6"><?= format_money($stats['payments_total'] ?? 0) ?></div>
            <a href="<?= url('fees') ?>" class="stretched-link small">Finanse &rarr;</a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-calendar-event"></i> Nadchodzące wydarzenia</h5>
            <?php if (empty($upcoming)): ?>
                <div class="text-muted">Brak zaplanowanych wydarzeń. <a href="<?= url('events/create') ?>">Dodaj pierwsze</a>.</div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($upcoming as $e): ?>
                        <div class="list-group-item d-flex justify-content-between">
                            <div>
                                <strong><?= View::e($e['name']) ?></strong>
                                <small class="text-muted d-block">
                                    <?= View::e($e['type']) ?>
                                    <?php if (!empty($e['sport_name'])): ?>
                                        • <?= View::e($e['sport_name']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($e['location'])): ?>
                                        • <?= View::e($e['location']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <span class="text-muted small"><?= View::e(format_datetime($e['event_date'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-bookmark-star"></i> Twój klub</h5>
            <?php if (!empty($currentClub)): ?>
                <strong><?= View::e($currentClub['name']) ?></strong>
                <?php if (!empty($currentClub['city'])): ?>
                    <div class="text-muted small"><?= View::e($currentClub['city']) ?></div>
                <?php endif; ?>
                <?php if (!empty($currentClub['email'])): ?>
                    <div class="text-muted small"><?= View::e($currentClub['email']) ?></div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($subscription)): ?>
                <hr>
                <div class="small">
                    <strong>Subskrypcja:</strong> <?= View::e($subscription['plan_name']) ?><br>
                    <strong>Status:</strong> <?= View::e($subscription['status']) ?><br>
                    <strong>Ważna do:</strong> <?= format_date($subscription['valid_until']) ?>
                </div>
            <?php endif; ?>

            <hr>
            <h6>Aktywne sekcje</h6>
            <?php if (empty($clubSports)): ?>
                <div class="text-muted small">Brak sekcji. <a href="<?= url('sports') ?>">Dodaj sport</a>.</div>
            <?php else: ?>
                <?php foreach ($clubSports as $cs): ?>
                    <span class="sport-badge me-1 mb-1" style="background: <?= View::e($cs['color']) ?>">
                        <i class="bi <?= View::e($cs['icon']) ?>"></i> <?= View::e($cs['name']) ?>
                    </span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
