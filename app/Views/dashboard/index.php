<?php use App\Helpers\View; ?>
<?php
// Build a lookup of widget visibility
$widgetVisible = [];
$widgetOrder   = [];
foreach (($widgets ?? []) as $w) {
    $widgetVisible[$w['widget_key']] = (int)$w['is_visible'];
    $widgetOrder[] = $w['widget_key'];
}
// Ensure all defaults present
foreach (\App\Models\DashboardWidgetModel::DEFAULTS as $dk) {
    if (!isset($widgetVisible[$dk])) {
        $widgetVisible[$dk] = 1;
        $widgetOrder[] = $dk;
    }
}

$widgetLabels = [
    'stats'              => __('widget.stats'),
    'upcoming_events'    => __('widget.upcoming_events'),
    'upcoming_trainings' => __('widget.upcoming_trainings'),
    'medical_alerts'     => __('widget.medical_alerts'),
    'club_info'          => __('widget.club_info'),
    'announcements'      => __('widget.announcements'),
];
?>

<div class="mb-3 text-end">
    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#widgetConfigModal">
        <i class="bi bi-gear"></i> <?= __('dash.configure_widgets') ?>
    </button>
</div>

<?php foreach ($widgetOrder as $wKey): ?>
<?php if (empty($widgetVisible[$wKey])) continue; ?>

<?php if ($wKey === 'stats'): ?>
<div data-widget="stats">
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3">
            <div class="text-muted small"><?= __('dash.active_members') ?></div>
            <div class="display-6"><?= (int)($stats['members'] ?? 0) ?></div>
            <a href="<?= url('members') ?>" class="stretched-link small"><?= __('dash.view') ?> &rarr;</a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3">
            <div class="text-muted small"><?= __('dash.sports_sections') ?></div>
            <div class="display-6"><?= (int)($stats['sports'] ?? 0) ?></div>
            <a href="<?= url('sports') ?>" class="stretched-link small"><?= __('dash.manage') ?> &rarr;</a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3">
            <div class="text-muted small"><?= __('dash.upcoming_events') ?></div>
            <div class="display-6"><?= (int)($stats['events_upcoming'] ?? 0) ?></div>
            <a href="<?= url('events') ?>" class="stretched-link small"><?= __('dash.calendar') ?> &rarr;</a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card p-3">
            <div class="text-muted small"><?= __('dash.revenue_year') ?></div>
            <div class="display-6"><?= format_money($stats['payments_total'] ?? 0) ?></div>
            <a href="<?= url('fees') ?>" class="stretched-link small"><?= __('dash.finances') ?> &rarr;</a>
        </div>
    </div>
</div>
</div>
<?php endif; ?>

<?php if ($wKey === 'medical_alerts'): ?>
<div data-widget="medical_alerts">
<?php if (!empty($expiringMedical)): ?>
<div class="alert alert-warning">
    <strong><i class="bi bi-heart-pulse"></i> <?= __('dash.medical_attention', ['count' => count($expiringMedical)]) ?></strong>
    <a href="<?= url('medical') ?>" class="float-end"><?= __('dash.view_all') ?> &rarr;</a>
</div>
<?php endif; ?>
</div>
<?php endif; ?>

<?php if ($wKey === 'upcoming_events'): ?>
<div data-widget="upcoming_events">
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-calendar-event"></i> <?= __('dash.upcoming_events_title') ?></h5>
            <?php if (empty($upcoming)): ?>
                <div class="text-muted"><?= __('dash.no_events') ?> <a href="<?= url('events/create') ?>"><?= __('dash.add_first') ?></a>.</div>
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
</div>
</div>
<?php endif; ?>

<?php if ($wKey === 'upcoming_trainings'): ?>
<div data-widget="upcoming_trainings" class="mt-3">
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-stopwatch"></i> <?= __('dash.upcoming_trainings') ?></h5>
            <?php if (empty($upcomingTrainings)): ?>
                <div class="text-muted"><?= __('dash.no_trainings') ?></div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($upcomingTrainings as $t): ?>
                        <div class="list-group-item d-flex justify-content-between">
                            <div>
                                <strong><?= View::e($t['name'] ?? $t['title'] ?? '') ?></strong>
                                <small class="text-muted d-block">
                                    <?php if (!empty($t['sport_name'])): ?>
                                        <?= View::e($t['sport_name']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($t['location'])): ?>
                                        • <?= View::e($t['location']) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <span class="text-muted small"><?= View::e(format_datetime($t['training_date'] ?? $t['start_time'] ?? '')) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
<?php endif; ?>

<?php if ($wKey === 'club_info'): ?>
<div data-widget="club_info" class="mt-3">
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card p-3">
            <h5 class="mb-3"><i class="bi bi-bookmark-star"></i> <?= __('dash.your_club') ?></h5>
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
                    <strong><?= __('dash.subscription') ?>:</strong> <?= View::e($subscription['plan_name']) ?><br>
                    <strong><?= __('dash.status') ?>:</strong> <?= View::e($subscription['status']) ?><br>
                    <strong><?= __('dash.valid_until') ?>:</strong> <?= format_date($subscription['valid_until']) ?>
                </div>
            <?php endif; ?>

            <hr>
            <h6><?= __('dash.active_sections') ?></h6>
            <?php if (empty($clubSports)): ?>
                <div class="text-muted small"><?= __('dash.no_sections') ?> <a href="<?= url('sports') ?>"><?= __('dash.add_sport') ?></a>.</div>
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
</div>
<?php endif; ?>

<?php if ($wKey === 'announcements'): ?>
<div data-widget="announcements">
<!-- Announcements widget placeholder -->
</div>
<?php endif; ?>

<?php endforeach; ?>

<!-- Widget Configuration Modal -->
<div class="modal fade" id="widgetConfigModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= url('dashboard/widgets') ?>">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><?= __('dash.widget_settings') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small"><?= __('dash.configure_widgets') ?></p>
                    <ul class="list-group" id="widget-sort-list">
                        <?php foreach ($widgetOrder as $wk): ?>
                            <li class="list-group-item d-flex align-items-center gap-2">
                                <i class="bi bi-grip-vertical text-muted" style="cursor:grab;"></i>
                                <input type="hidden" name="widget_key[]" value="<?= View::e($wk) ?>">
                                <div class="form-check mb-0 flex-grow-1">
                                    <input type="checkbox" class="form-check-input" name="widget_visible[<?= View::e($wk) ?>]" value="1"
                                        id="wv_<?= View::e($wk) ?>"
                                        <?= !empty($widgetVisible[$wk]) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="wv_<?= View::e($wk) ?>">
                                        <?= View::e($widgetLabels[$wk] ?? $wk) ?>
                                    </label>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('btn.close') ?></button>
                    <button type="submit" class="btn btn-primary"><?= __('dash.save_layout') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Simple drag-and-drop reordering for widget list
document.addEventListener('DOMContentLoaded', function() {
    var list = document.getElementById('widget-sort-list');
    if (!list) return;
    var dragItem = null;
    list.querySelectorAll('li').forEach(function(item) {
        item.setAttribute('draggable', 'true');
        item.addEventListener('dragstart', function(e) {
            dragItem = item;
            item.style.opacity = '0.5';
        });
        item.addEventListener('dragend', function() {
            item.style.opacity = '1';
            dragItem = null;
        });
        item.addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        item.addEventListener('drop', function(e) {
            e.preventDefault();
            if (dragItem && dragItem !== item) {
                var allItems = Array.from(list.children);
                var dragIdx = allItems.indexOf(dragItem);
                var dropIdx = allItems.indexOf(item);
                if (dragIdx < dropIdx) {
                    list.insertBefore(dragItem, item.nextSibling);
                } else {
                    list.insertBefore(dragItem, item);
                }
            }
        });
    });
});
</script>
