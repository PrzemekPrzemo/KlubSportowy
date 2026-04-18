<?php use App\Helpers\View; ?>
<div class="card p-3 mb-3">
    <h5>#<?= (int)$ticket['id'] ?> <?= View::e($ticket['subject']) ?></h5>
    <span class="badge bg-<?= $ticket['status']==='open'?'danger':'info' ?>"><?= View::e($ticket['status']) ?></span>
    <span class="badge bg-<?= $ticket['priority']==='urgent'?'danger':'secondary' ?>"><?= View::e($ticket['priority']) ?></span>
    <small class="text-muted ms-2"><?= format_datetime($ticket['created_at']) ?></small>
    <hr>
    <div><?= nl2br(View::e($ticket['body'])) ?></div>
</div>

<div class="card p-3">
    <h5>Odpowiedzi</h5>
    <?php foreach ($replies as $r): ?>
        <div class="border rounded p-2 mb-2">
            <strong class="small"><?= View::e($r['author_name'] ?? 'Support') ?></strong>
            <small class="text-muted float-end"><?= format_datetime($r['created_at']) ?></small>
            <div class="mt-1"><?= nl2br(View::e($r['body'])) ?></div>
        </div>
    <?php endforeach; ?>
    <?php if ($ticket['status'] !== 'closed'): ?>
    <hr>
    <form method="POST" action="<?= url('support/' . (int)$ticket['id'] . '/reply') ?>">
        <?= csrf_field() ?>
        <div class="mb-2"><textarea name="body" class="form-control" rows="3" required placeholder="Twoja odpowiedź..."></textarea></div>
        <button class="btn btn-primary"><i class="bi bi-send"></i> ODPOWIEDZ</button>
    </form>
    <?php endif; ?>
</div>
