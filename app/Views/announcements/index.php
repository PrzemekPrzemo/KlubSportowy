<?php use App\Helpers\View; ?>
<div class="mb-3 text-end">
    <a href="<?= url('announcements/create') ?>" class="btn btn-success">
        <i class="bi bi-plus"></i> Nowe ogłoszenie
    </a>
</div>

<?php if (empty($pagination['data'])): ?>
    <div class="card p-4 text-center text-muted">Brak ogłoszeń.</div>
<?php else: ?>
    <?php foreach ($pagination['data'] as $a):
        $cls = match($a['priority']) { 'urgent' => 'danger', 'important' => 'warning', default => 'secondary' };
    ?>
        <div class="card p-3 mb-2 border-start border-4 border-<?= $cls ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">
                        <span class="badge bg-<?= $cls ?>"><?= View::e($a['priority']) ?></span>
                        <?= View::e($a['title']) ?>
                    </h5>
                    <small class="text-muted">
                        <?= format_datetime($a['created_at']) ?>
                        • <?= View::e($a['author_name'] ?? 'system') ?>
                        • dla: <?= View::e($a['target']) ?>
                        <?php if (!empty($a['sport_name'])): ?>
                            • sport: <?= View::e($a['sport_name']) ?>
                        <?php endif; ?>
                        <?php if (!$a['published']): ?>
                            • <span class="badge bg-dark">szkic</span>
                        <?php endif; ?>
                    </small>
                </div>
                <div>
                    <a href="<?= url('announcements/' . (int)$a['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="<?= url('announcements/' . (int)$a['id'] . '/delete') ?>" onsubmit="return confirm('Usunąć?')" class="d-inline">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
            <div class="mt-2"><?= nl2br(View::e($a['content'])) ?></div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
